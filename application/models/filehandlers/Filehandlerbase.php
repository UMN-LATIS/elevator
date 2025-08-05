<?php

//use Aws\S3\S3Client;
use Aws\Batch\BatchClient;
use Aws\Bedrock\BedrockClient;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;


if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class FileHandlerBase extends CI_Model {

	/**
	 * this are to be overridden by subclasses
	 */
	protected $supportedTypes = array();
	protected $noDerivatives = true;
	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]]];
	public $gpuTaskArray = [];
	public $icon = "_blank.png"; // icon File to use if we don't have a thumb
	public $collection = null;

	public $asset = null;

	/**
	 * these will be set by subclasses
	 */
	public $metadata;

	public $sourceFile = NULL; // an object of type fileContainer
	public $jobIdArray = array(); //beanstalk jobIds
	public $regenerate = false; // regenerate derivatives
	public $collectionId = null;
	public $parentObjectId = null;
	public $parentObject = null;
	public $s3model = null;
	public $deleted = false;
	public $derivatives = array(); // array of fileContainers
	public $job; // beanstalkd current job
	public $globalMetadata = array();

	public $postponeTime = 10;

	public $overrideHandlerClass = null; // allows us to force a new handler for the next load

	public $nextTask = null;
	public $taskListHasChanged = false;
	

	public function __construct()
	{
		parent::__construct();
		$this->load->model("Filecontainers3");
		$this->load->model("FileContainer");
		//Do your magic here
	}


	/**
	 * does this class support the passed in file, based on extention?
	 * This should not be overridden by children
	 * @param  [STRING] $fileType [a standard 3 char extension]
	 * @return [BOOL]           [yes, we support it]
	 */
	public function supportsType($fileType) {
		if($fileType == "" || $fileType == NULL) {
			return FALSE;
		}
		
		if(in_array(strtolower($fileType), $this->supportedTypes)) {
			return TRUE;
		}
	}

	public function noDerivatives() {
		return $this->noDerivatives;
	}

	public function derivativeList() {

	}

	public function getIcon() {
		if(isset($this->icon)) {
			return $this->icon;
		}
		
		$iconFile = strtolower($this->sourceFile->getType()).".png";
		$iconPath = getIconPath();
		return file_exists($iconPath . $iconFile)
			? $iconFile
			: "_blank.png";
	}

	public function getPreviewThumbnail($retina=false) {
		if(isset($this->derivatives['thumbnail']) && !$retina) {
			return $this->derivatives['thumbnail'];
		}
		elseif(isset($this->derivatives['thumbnail2x']) && $retina) {
			return $this->derivatives['thumbnail2x'];
		}
		elseif(!isset($this->derivatives['thumbnail2x']) && isset($this->derivatives['thumbnail']) && $retina) {
			return $this->derivatives['thumbnail'];
		}
		else {
			if($this->icon) {
				$iconPath = getIconPath();
				$fileContainer = new FileContainer($iconPath . $this->icon);
				return $fileContainer;
			}
			else {
				throw new Exception("No Thumbnail For Asset");
				return FALSE;
			}
		}

	}

	public function getPreviewTiny($retina=false) {
		if(isset($this->derivatives['tiny']) && !$retina) {
			return $this->derivatives['tiny'];
		}
		elseif(isset($this->derivatives['tiny2x']) && $retina) {
			return $this->derivatives['tiny2x'];
		}
		elseif(!isset($this->derivatives['tiny2x']) && isset($this->derivatives['tiny']) && $retina) {
			return $this->derivatives['tiny'];
		}
		else {

			throw new Exception("No Thumbnail For Asset");
			return FALSE;
		}

	}


	public function highestQualityDerivativeForAccessLevel($accessLevel, $stillOnly=false) {
		try {
			$allItems = $this->allDerivativesForAccessLevel($accessLevel);
		}
		catch (Exception $e) {
			throw new Exception("Derivative not found");
		}

		if(is_array($allItems)) {
			return array_shift($allItems);
		}
		else {
			return array();
		}
	}

	/**
	 * this must be overridden by children
	 * @param  [type] $accessLevel [description]
	 * @return [type]              [description]
	 */
	public function allDerivativesForAccessLevel($accessLevel) {
		//if($accessLevel >= PERM_ORIGINALS) {
		//	return ["original"=>$this->sourceFile];
		//}
		//else {
			throw new Exception("Derivative not found");
		//}
	}

	public function getObjectId() {
		if(isset($this->asset)) {
			return $this->asset->getFileObjectId();
		}
		else {
			return false;
		}

	}


	/**
	 * we make this a getter method so that eventually we can turn this off by just returnign the object Id.
	 * this is because s3 wants entropy at the front, but mongo entropy is at the rear.
	 * if we move to postgres, it'll frontload entropy ( I think)
	 * @return [type] [description]
	 */
	public function getReversedObjectId() {
		return strrev($this->getObjectId());
	}

	public function removeJob($jobId) {
		if(($key = array_search($jobId, $this->jobIdArray)) !== false) {
    		unset($this->jobIdArray[$key]);
    		$this->asset->setJobIdArray($this->jobIdArray);
    		$this->doctrine->em->persist($this->asset);
    		$this->doctrine->em->flush();
		}
	}

	public function addJobId($jobId) {
		$this->jobIdArray[] = $jobId;
    	$this->asset->setJobIdArray($this->jobIdArray);
    	$this->doctrine->em->persist($this->asset);
    	$this->doctrine->em->flush();
	}

	public function performTask($job) {
		$this->job = $job; //cache the job so we can touch it if necessary
		$task = json_decode($job->getData(), true);
		if(method_exists($this, $task["task"])) {
			$this->logging->processingInfo("taskStart",get_class($this),"Starting Task " . $task["task"],$this->getObjectId(),0);
			return call_user_func(array($this,$task["task"]), $task['config']);
		}
		else {
			return false;
		}
	}

	public function performTaskByName($taskName, $args) {
		if(method_exists($this, $taskName)) {
			return call_user_func(array($this,$taskName), $args);
		}
		else {
			return false;
		}
	}

	public function triggerReindex() {
		// we may have made some sort of change at this point, let's queue a reindex
		// make sure we save so we don't race the index
		$this->save();
		if($this->parentObjectId != null && $this->config->item("beanstalkd")) {
			$pheanstalk =  Pheanstalk\Pheanstalk::create($this->config->item("beanstalkd"));
			$tube = new Pheanstalk\Values\TubeName('reindex');
			// run a 15 minute TTR because zipping all these could take a while
			$pheanstalk->useTube($tube);

			if(!$this->instance || $this->instance == null) {
				$instanceId = 1; // welp, we're hosed, hope we can find a good one.
				// lookup instance based on this file's collection.
				$collection = $this->collection_model->getCollection($this->collectionId);
				if($collection) {
					$instances = $collection->getInstances();
					if(count($instances) > 0) {
						$instance = $instances[0];
						$instanceId = $instance->getId();
					}
				}
			}
			else {
				$instanceId = $this->instance->getId();
			}

			$newTask = json_encode(["objectId"=>$this->parentObjectId,"instance"=>$instanceId]);
			
			try {
				$jobId = $pheanstalk->put($newTask, Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 2);
			} catch (Exception $e) {
				$ipAddress = gethostbyname(gethostname());
				echo "Error: " . $e->getMessage() . " on IP: " . $ipAddress;
			}
		}
	}


	public function queueTask($taskId, $appendData=array(), $setHostAffinity=true) {

		// if we're injecting a new handler, we can't trust $this
		if($this->overrideHandlerClass) {
			$newHandler = new $this->overrideHandlerClass;
			$nextTask = $newHandler->taskArray[$taskId];
		}
		else {
			$nextTask = $this->taskArray[$taskId];
		}

		$this->nextTask = $nextTask;

	}

	public function getNextTask($taskName) {
		foreach($this->taskArray as $key=>$task) {
			if($task["taskType"] == $taskName) {
				if(array_keys($this->taskArray, $key+1)) {
					return $key+1;
				}
			}
		}
		return FALSE;
	}

	public function cleanupOriginal($args) {
		if($this->sourceFile->removeLocalFile()) {
			if($nextTask = $this->getNextTask("cleanupOriginal")) {
				$this->queueTask($nextTask);
			}

			return JOB_SUCCESS;
		}
		else {
			return JOB_SUCCESS;
		}

	}

	public function save() {


		if($this->getObjectId()) {
			$fileObject = $this->doctrine->em->getRepository("Entity\FileHandler")->findOneBy(["fileObjectId"=>$this->getObjectId()]);
		}
		else {
			$fileObject = new Entity\FileHandler;
		}



		$fileObject->setSourceFile($this->sourceFile->getAsArray());

		$derivativeArray = array();

		foreach($this->derivatives as $type=>$derivative) {
			$derivativeArray[$type] = $derivative->getAsArray();
		}

		$fileObject->setDerivatives($derivativeArray);
		$fileObject->setJobIdArray($this->jobIdArray);
		$fileObject->setFileType($this->sourceFile->getType());
		$fileObject->setCollectionId($this->collectionId);
		$fileObject->setDeleted($this->deleted);
		$fileObject->setGlobalMetadata($this->globalMetadata);

		if($this->overrideHandlerClass) {
			$fileObject->setHandler($this->overrideHandlerClass);
		}
		else {
			$fileObject->setHandler(strtolower(get_class($this)));
		}

		if($this->parentObjectId != null) {
			$fileObject->setParentObjectId($this->parentObjectId);
		}

		if(!$this->getObjectId()) {
			$fileObject->setFileObjectId((string)new MongoDB\BSON\ObjectId());
		}

		$this->doctrine->em->persist($fileObject);
		$this->doctrine->em->flush();

		$this->asset = $fileObject;

   		if($this->regenerate && count($this->taskArray)>0) {

   			// TODO: NEED TO TRASH DERIVATIVES

   			if(count($this->derivatives)>0) {
   				foreach($this->derivatives as $derivative) {
   					$derivative->deleteFile();
   				}
   			}

   			$this->overrideHandlerClass = get_class($this->filehandler_router->getHandlerForType($fileObject->getFileType()));
   			$this->derivatives = array();
   			$this->regenerate = false;

   			$this->save();

		
			if($this->sourceFile->isArchived()) {
				$pheanstalk =  Pheanstalk\Pheanstalk::create($this->config->item("beanstalkd"));
				$tube = new Pheanstalk\Values\TubeName('restoreTube');
				// run a 15 minute TTR because zipping all these could take a while
				$pheanstalk->useTube($tube);


				$pathToFile = instance_url("");
				$newTask = json_encode(["objectId"=>$this->getObjectId(), "userContact"=>$this->user_model->getEmail(), "instance"=>$this->instance->getId(), "pathToFile"=>$pathToFile, "nextTask"=>"create_derivative"]);
				$jobId= $pheanstalk->put($newTask, Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 1);
			}
			else {
				$this->queueBatchItem();
			}



   		}
   		elseif($this->regenerate) {
   			$this->sourceFile->ready = true;
   			$this->regenerate = false;
   			$this->save();
   		}


		return $this->getObjectId();

	}

	public function getCustomJobOverrides() {
		return [];
	}

	public function queueBatchItem($jobType = "cpu") {
		$fileObjectId = $this->asset->getFileObjectId();
		$fileSize = $this->sourceFile->getFileSize();
		$size = "";
		if($fileSize < 50*1024*1024) { 
			// under 100mb, we can safely use our small container
			$size = "small";
		}
		else {
			$size = "large";
		}

		$overrides = $this->getCustomJobOverrides();

		$jobDefinition = $this->config->item('awsQueueJobDefinition');
		$jobQueue = $this->config->item('awsQueueJobQueue');
		if($jobType == "cpu") {
			$jobDefinition = $jobDefinition . "-" . $size;
		}
		else {
			$jobDefinition = $jobDefinition . "-gpu";
			$jobQueue = $jobQueue . "GPU";
		}
		

		

		$batchClient = new BatchClient([
			'region' => $this->config->item('awsQueueRegion'), // e.g., 'us-west-2'
			'version' => 'latest',
			'credentials' => [
				'key'    => $this->config->item('awsQueueAccessKey'),
				'secret' => $this->config->item('awsQueueSecretKey'),
			],
		]);
		$params = [
			'jobName' => 'transcode_' . $fileObjectId,
			'jobQueue' => $jobQueue,
			'jobDefinition' => $jobDefinition,
			'timeout' => [
				"attemptDurationSeconds" => ($size=="small")? 2400 : 28800,
			],
			'containerOverrides' => [
        		'command' => ['bash', 'docker/runJob.sh',  $fileObjectId, $jobType],
				'resourceRequirements' => $overrides
    		],
		];
		try {
			$result = $batchClient->submitJob($params);
		} catch (AwsException $e) {
			$this->logging->processingInfo("taskFailed",get_class($this),"Failed to submit job to AWS Batch" . $e->getMessage(),$fileObjectId,0);
		}
		

	}

	/**
	 * If you add any more params to this, move to a config array!!
	 */
	public function getEmbedViewWithFiles($fileContainerArray, $includeOriginal=false, $embedded=false) {

		$uploadWidget = $this->getUploadWidget();

		return $this->load->view("fileHandlers/chrome/" . strtolower(get_class($this)) . "_chrome", ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

	public function getEmbedView($fileContainerArray, $includeOriginal=false, $embedded=false) {

		$uploadWidget = $this->getUploadWidget();
		return $this->load->view("fileHandlers/embeds/" . strtolower(get_class($this)) . "", ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

	public function getUploadWidget($forceReload = false) {
		if((!$this->parentObject && $this->parentObjectId) || ($forceReload && $this->parentObjectId)) {
			$this->parentObject = new Asset_model($this->parentObjectId);
		}

		$uploadWidget = null;
		if($this->parentObject) {
			$uploadObjects = $this->parentObject->getAllWithinAsset("Upload");
			foreach($uploadObjects as $upload) {
				foreach($upload->fieldContentsArray as $widgetContents) {
					if($widgetContents->fileId == $this->getObjectId()) {
						$uploadWidget = $widgetContents;
					}
				}
			}

		}

		return $uploadWidget;
	}

	public function getSidecarView($sidecars, $formFieldName) {
		if(file_exists("application/views/fileHandlers/sidecars/" . strtolower(get_class($this)) . "_sidecars.php")) {
			return $this->load->view("fileHandlers/sidecars/" . strtolower(get_class($this)) . "_sidecars.php", ["sidecarData"=>$sidecars, "formFieldRoot"=>$formFieldName, "fileHandler"=>$this], true);
		}
		else {
			return "";
		}
	}

	public function loadByObjectId($objectId) {

		$asset = $this->doctrine->em->getRepository('Entity\FileHandler')->findOneBy(["fileObjectId"=>$objectId]);

		if(!isset($asset)) {
			return FALSE;
		}

		return $this->loadFromObject($asset);
	}

	public function setCollectionId($collectionId) {
		$this->collectionId = $collectionId;
		$this->collection = $this->collection_model->getCollection($this->collectionId);
		$this->load->model("S3_model");
		$this->s3model = new S3_model($this->collection);

	}

	public function loadFromObject($asset) {
		$this->asset = $asset;

		$this->setCollectionId($asset->getCollectionId());
		if($asset->getParentObjectId() !== null) {
			$this->parentObjectId = $asset->getParentObjectId();
		}

		$this->sourceFile = new FileContainerS3($asset->getSourceFile());
		$this->sourceFile->derivativeType = "source";
		$this->sourceFile->setParent($this);
		$this->jobIdArray = $asset->getJobIdArray();
		if($asset->getDeleted() !== false) {
			$this->deleted = true;
		}

		$this->globalMetadata = $asset->getGlobalMetadata();

		if($asset->getDerivatives() !== null) {
			foreach($asset->getDerivatives() as $type=>$derivative) {
				$this->derivatives[$type] = new FileContainerS3($derivative);
				$this->derivatives[$type]->derivativeType = $type;
				$this->derivatives[$type]->setParent($this);

			}

		}

		return $this->getObjectId();
	}


	public function extractMetadata($args) {

		if(!isset($args['fileObject'])) {
			$fileObject = $this->sourceFile;
		}
		else {
			$fileObject = $args['fileObject'];
		}

		// if(!$this->sourceFile->isLocal()) {
		// 	$this->sourceFile->makeLocal();
		// 	return JOB_POSTPONE;
		// }

		// if(!file_exists($this->sourceFile->getPathToLocalFile())) {
		// 	return JOB_FAILED;
		// }

		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		return JOB_SUCCESS;
	}

	public function updateParent($args) {

		$parentId = $this->parentObjectId;

		if(!$parentId) {
			return JOB_SUCCESS;
		}
		$this->load->model("asset_model");
		if($this->asset_model->loadAssetById($parentId)) {
			$this->asset_model->save(true,false);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	/**
	 * Presign all the URLs for a given keypath, so we can pass them off to the browser;
	 */
	public function getSignedURLs($derivativeTitle, $stripHTTP=false, $rootFolder = "") {

		if(!isset($this->derivatives[$derivativeTitle])) {
			return array();
		}

		$derivative = $this->derivatives[$derivativeTitle];

		$fileList = $this->s3model->getFilesAtKeyPath($derivative->storageKey . "/" . $rootFolder);	
		

		$signedFileList = array();
		foreach($fileList as $file) {
			$signedURL = $this->s3model->getProtectedURL($file, null, "+120 minutes");
			if($stripHTTP) {
				$signedURL = stripHTTP($signedURL);
			}
			$signedFileList[] = $signedURL;
		}

		return $signedFileList;
	}

	public function getSecurityToken($derivativeTitle) {
		if(!isset($this->derivatives[$derivativeTitle])) {
			return array();
		}

		$derivative = $this->derivatives[$derivativeTitle];

		
		$token = $this->s3model->getSecurityTokenForPath($derivative->storageKey);
		return $token;


	}


	/**
	 * resolve permissions for asset
	 */
    public function getPermission() {
    	$handlerName = get_class($this);
    	$requiredPermissions = false;
    	if($this->config->item('enableCaching') && isset($this->cache) && ($storedObject = $this->cache->get($handlerName . $this->instance->getId()))) {
			$requiredPermissions = $storedObject;
    	}
    	else {
        	$permissions = $this->doctrine->em->getRepository("Entity\InstanceHandlerPermissions")->findOneBy(["handler_name"=>$handlerName, "instance"=>$this->instance]);
        	if($permissions) {
            	$requiredPermissions = $permissions->getPermissionGroup();
        	}
        	if($this->config->item('enableCaching') && isset($this->cache)) {
        		$this->cache->save($handlerName . $this->instance->getId(), $requiredPermissions, 300);
        	}
    	}


        if($requiredPermissions) {
        	return $requiredPermissions;
        }

        return PERM_DERIVATIVES_GROUP_2;
    }

	function getEmbedURL($signURL = false) {
		$append = "";
		if($signURL && $this->user_model && $this->user_model->userLoaded) {
			$apiKey = $this->user_model->getApiKeys()->first();
			if(!$apiKey) {
				$apiKey = $this->user_model->generateKeys();
			}

			if($apiKey) {
				$authKey = $apiKey->getApiKey();
				$timestamp = time();
				$targetObject = $this->parentObjectId;
				$signedString = sha1($timestamp . $targetObject.  $apiKey->getApiSecret());	
				$append = "?" . http_build_query(["apiHandoff"=>$signedString, "authKey"=>$authKey, "timestamp"=>$timestamp, "targetObject"=>$targetObject]);
			}
		}
		$embedLink = instance_url("asset/getEmbed/" . $this->getObjectId() . "/" . $this->parentObjectId. "/true" . $append);
		$embedLink = str_replace("http:", "", $embedLink);
		$embedLink = str_replace("https:", "", $embedLink);
		return $embedLink;
	}

	/**
	 * DELETE EVERYTHING FOR REAL
	 */

	function deleteFile() {
		foreach($this->derivatives as $key=>$derivative) {
			$result = $derivative->deleteFile();
			if(!$result) {
				return FALSE;
			}
			unset($this->derivatives[$key]);
		}
		//if(!$this->sourceFile->deleteFile()) {
		//	return FALSE;
		//}
		$this->deleted = true;
		$this->save();
		return true;
	}

	function undeleteFile() {
		$this->deleted = true;
		$this->save();
		return true;
	}


	function findDeletedItems() {

		return $this->doctrine->em->getRepository("Entity\FileHandler")->findBy(["deleted"=>true]);
	}

	function deleteSource($serial=null,$mfa=null) {

		if($mfa && !$this->collection && strlen($this->getObjectId()) >= 24) {
			$this->logging->logError("purge error", "missing collection " . $this->collectionId . ",  purging " . $this->getObjectId() );
			$asset = $this->doctrine->em->getRepository("Entity\FileHandler")->findOneBy(["fileObjectId"=>$this->getObjectId()]);
			$this->doctrine->em->remove($asset);
			$this->doctrine->em->flush();
			return true;
		}
		else {
			if($mfa && $this->sourceFile->deleteFile($serial, $mfa)) {
				$asset = $this->doctrine->em->getRepository("Entity\FileHandler")->findOneBy(["fileObjectId"=>$this->getObjectId()]);
				$this->doctrine->em->remove($asset);
				$this->doctrine->em->flush();
				return true;
			}
			else {
				return false;
			}	
		}

		
	}

	public function priority() {
		return 0;
	}

	public function mungedSidecarData($sidecarData=null, $sidecarType=null) {

		if(isset($sidecarType) && isset($sidecarData) && isset($sidecarData[$sidecarType])) {
			$sidecarData = $sidecarData[$sidecarType];
			return $sidecarData;
		}
		else {
			return null;	
		}
	}

	protected function getAltTextForMedia($prompt, $metadata, $imagePath) {
		$modelId = 'arn:aws:bedrock:us-east-1:269090599827:inference-profile/us.meta.llama4-maverick-17b-instruct-v1:0';
		$bedrockClient = new BedrockRuntimeClient([
            'region' => 'us-east-1',
			'version' => 'latest',
			'credentials' => [
				'key'    => $this->config->item('awsBedrockKey'),
				'secret' => $this->config->item('awsBedrockSecret'),
			],
        ]);



		$systemPrompt = "Generate a caption appropriate for use as alt text within a digital asset management system. Just output the caption, don't add any additional context like \"here's the caption you asked for\". You can use the associated metadata json to better describe the object, but don't repeat the metadata verbatim as it'll be available to the user as well. Be sure to actually describe the object, not just the metadata, so that someone who's visually impaired would understand what the image represents. If the metadata has a 'type' field, use it to describe the object (e.g. 'a 3d object', 'a word document', etc.). Make sure you follow best practices for generating high quality alt text for images. Make sure to double check your work. Don't repeat content that's already in the metadata like dimensions, unless it's crucial to visibly describe the object.";

		$combinedPrompt = $systemPrompt . "\n\n" . $prompt . "\n\n" . json_encode($metadata);


		$tempImagePath = $this->config->item('scratchSpace') . "/temp_image_" . time() . ".jpg";
		$tempImage = new FileContainer($tempImagePath);

		compressImageAndSave($imagePath, $tempImage, 512, 512, 80);
		$imageBytes = file_get_contents($tempImage->getPathToLocalFile());

		$conversation = [
			[
				"text" => $combinedPrompt
			],
			[
				"image"=> [
					"format"=> "jpeg",
					"source"=> [
						"bytes"=> $imageBytes
					]
				]
			]
		];


		$messages = [
        	[
            	"role"=> "user",
            	"content"=> $conversation
			]
		];

		$response = $bedrockClient->converse([
			'modelId' => $modelId,
			'messages' => $messages,
			'inferenceConfig' => [
				'maxTokens' => 512,
				'temperature' => 0.5
			]
		]);

		$responseText = $response['output']['message']['content'][0]['text'];

        return $responseText;
	}


}

/* End of file modelName.php */
/* Location: ./application/models/modelName.php */
