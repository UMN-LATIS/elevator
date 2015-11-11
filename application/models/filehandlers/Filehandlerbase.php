<?php

//use Aws\S3\S3Client;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class FileHandlerBase extends CI_Model {

	/**
	 * this are to be overridden by subclasses
	 */
	protected $supportedTypes = array();
	protected $noDerivatives = true;
	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]]];
	public $icon = "_blank.png"; // icon File to use if we don't have a thumb


	/**
	 * these will be set by subclasses
	 */
	public $metadata;
	public $objectId = NULL;
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
	public $pheanstalk = null;

	public $postponeTime = 10;

	public $overrideHandlerClass = false; // allows us to force a new handler for the next load

	public function __construct()
	{
		parent::__construct();
		$this->load->model("FileContainerS3");
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
		else if(file_exists("assets/icons/512px/".strtolower($this->sourceFile->getType()) . ".png")) {
			return strtolower($this->sourceFile->getType()).".png";
		}
		else {
			return "_blank.png";
		}
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

				$fileContainer = new FileContainer("/assets/icons/512px/".$this->icon);
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
		return $this->objectId;
	}


	/**
	 * we make this a getter method so that eventually we can turn this off by just returnign the object Id.
	 * this is because s3 wants entropy at the front, but mongo entropy is at the rear.
	 * if we move to postgres, it'll frontload entropy ( I think)
	 * @return [type] [description]
	 */
	public function getReversedObjectId() {
		return strrev($this->objectId);
	}

	public function removeJob($jobId) {
		if(($key = array_search($jobId, $this->jobIdArray)) !== false) {
    		unset($this->jobIdArray[$key]);
    		$this->qb->where('_id', new MongoId($this->objectId))->set(["jobIdArray"=>$this->jobIdArray])->update('fileRepository');
		}
	}

	public function addJobId($jobId) {
		$this->jobIdArray[] = $jobId;
		$this->qb->where('_id', new MongoId($this->objectId))->set(["jobIdArray"=>$this->jobIdArray])->update('fileRepository');
	}

	public function performTask($job) {
		$this->job = $job; //cache the job so we can touch it if necessary
		$task = json_decode($job->getData(), true);
		if(method_exists($this, $task["task"])) {
			return call_user_func(array($this,$task["task"]), $task['config']);
		}
		else {
			return false;
		}

	}


	public function queueTask($taskId, $appendData=array(), $setHostAffinity=true) {
		$nextTask = $this->taskArray[$taskId];
		if(!$this->pheanstalk) {
			$this->pheanstalk = new \Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
		}

		$args = array_merge($nextTask['config'], $appendData);

		if($setHostAffinity) {
			$hostAffinity = $this->serverId;
		}
		else {
			$hostAffinity = null;
		}

		$newTask = json_encode(["task"=>$nextTask['taskType'], "config"=>$args, "fileHandlerId"=>$this->getObjectId(), "type"=>$this->sourceFile->getType(), "instance"=>$this->instance->getId(), "host_affinity"=>$hostAffinity]);
		$ttr = 60;
		if(isset($args['ttr'])){
			$ttr = $args['ttr'];
		}
		if($taskId == 0) {
			$priority = 50;
		}
		else {
			$priority = 20;
		}

		$jobId= $this->pheanstalk->useTube('newUploads')->put($newTask, $priority, 2, $ttr);

		$this->addJobId($jobId);
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

		$objectArray = array();

		if(isset($this->objectId)) {
			$objectArray["_id"] = new MongoId($this->objectId);
		}


		$objectArray["sourceFile"] = $this->sourceFile->getAsArray();

		$derivativeArray = array();

		foreach($this->derivatives as $type=>$derivative) {
			$derivativeArray[$type] = $derivative->getAsArray();
		}

		$objectArray["derivatives"] = $derivativeArray;
		$objectArray["jobIdArray"] = $this->jobIdArray;
		$objectArray["type"] = $this->sourceFile->getType();
		$objectArray["collectionId"] = (int)$this->collectionId;
		$objectArray['deleted'] = $this->deleted;
		$objectArray['globalMetadata'] = $this->globalMetadata;
		if($this->overrideHandlerClass) {
			$objectArray['handler'] = $this->overrideHandlerClass;
		}
		else {
			$objectArray['handler'] = strtolower(get_class($this));
		}

		if($this->parentObjectId != null) {
			$objectArray['parentObjectId'] = new MongoId($this->parentObjectId);
		}

   		$this->objectId = (string)$this->qb->save('fileRepository', $objectArray);


   		if($this->regenerate && count($this->taskArray)>0) {

   			// TODO: NEED TO TRASH DERIVATIVES

   			if(count($this->derivatives)>0) {
   				foreach($this->derivatives as $derivative) {
   					$derivative->deleteFile();
   				}
   			}
   			$this->overrideHandlerClass = get_class($this->filehandler_router->getHandlerForType($objectArray['type']));
   			$this->derivatives = array();
   			$this->regenerate = false;

   			$this->save();

   			$this->queueTask(0, [], false);


   		}
   		elseif($this->regenerate) {
   			$this->sourceFile->ready = true;
   			$this->regenerate = false;
   			$this->save();
   		}


		return $this->objectId;

	}

	/**
	 * If you add any more params to this, move to a config array!!
	 */
	public function getEmbedViewWithFiles($fileContainerArray, $includeOriginal=false, $embedded=false) {

		if(!$this->parentObject && $this->parentObjectId) {
			$this->parentObject = new Asset_model($this->parentObjectId);
		}

		$uploadWidget = null;
		if($this->parentObject) {
			$uploadObjects = $this->parentObject->getAllWithinAsset("Upload");
			foreach($uploadObjects as $upload) {
				foreach($upload->fieldContentsArray as $widgetContents) {
					if($widgetContents->fileId == $this->objectId) {
						$uploadWidget = $widgetContents;
					}
				}
			}

		}

		return $this->load->view("fileHandlers/" . strtolower(get_class($this)), ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

	public function getSidecarView($sidecars, $formFieldName) {
		if(file_exists("application/views/fileHandlers/" . strtolower(get_class($this)) . "_sidecars.php")) {
			return $this->load->view("fileHandlers/" . strtolower(get_class($this)) . "_sidecars.php", ["sidecarData"=>$sidecars, "formFieldRoot"=>$formFieldName], true);
		}
		else {
			return "";
		}
	}

	public function loadByObjectId($objectId) {
		$asset = $this->qb->where(['_id'=>new MongoId($objectId)])->getOne("fileRepository");

		if(!isset($asset)) {
			return FALSE;
		}

		return $this->loadFromObject($asset);
	}

	public function setCollectionId($collectionId) {
		if(isset($this->collectionId) && $this->collectionId != $collectionId) {
			throw new Exception('Cannot currently transfer between collections');
			return;
			// TODO :copy files between corects
		}
		$this->collectionId = $collectionId;
		$this->collection = $this->collection_model->getCollection($this->collectionId);
		$this->load->model("S3_model");
		$this->s3model = new S3_model($this->collection);

	}

	public function loadFromObject($asset) {

		$this->objectId = (string)$asset["_id"];
		$this->setCollectionId($asset["collectionId"]);
		if(isset($asset['parentObjectId'])) {
			$this->parentObjectId = $asset['parentObjectId'];
		}

		$this->sourceFile = new FileContainerS3($asset["sourceFile"]);
		$this->sourceFile->derivativeType = "source";
		$this->sourceFile->setParent($this);
		$this->jobIdArray = $asset["jobIdArray"];
		if(isset($asset['deleted'])) {
			$this->deleted = $asset['deleted'];
		}


		$this->globalMetadata = @$asset["globalMetadata"];
		// TODO: remove this on production instance
		if(!is_array($this->globalMetadata)) {
			$this->globalMetadata = array();
			$this->globalMetadata["text"] = @$asset["globalMetadata"];
		}


		if(isset($asset["derivatives"])) {
			foreach($asset["derivatives"] as $type=>$derivative) {
				$this->derivatives[$type] = new FileContainerS3($derivative);
				$this->derivatives[$type]->derivativeType = $type;
				$this->derivatives[$type]->setParent($this);

			}

		}

		return $this->objectId;
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
			$this->asset_model->save();
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

	function findDeletedItems() {
		return $this->qb->where(["deleted"=>true])->get("fileRepository");
	}

	function deleteSource($serial=null,$mfa=null) {

		if($mfa && $this->sourceFile->deleteFile($serial, $mfa)) {
			$this->qb->where(['_id'=>new MongoId($this->getObjectId())])->delete("fileRepository");
			return true;
		}
		else {
			return false;
		}
	}

}

/* End of file modelName.php */
/* Location: ./application/models/modelName.php */
