<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ZipObjHandler extends ZipHandler {
	protected $supportedTypes = array("obj.zip");
	protected $noDerivatives = false;
	
	public $taskArray = [0=>["taskType"=>"identifyContents", "config"=>array()],
	1=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
	2=>["taskType"=>"createDerivative", "config"=>array()],
	3=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												],
	4=>["taskType"=>"createNXS", "config"=>["ttr"=>900]],
	5=>["taskType"=>"createSTL", "config"=>[]]
	];


	public function __construct()
	{
		parent::__construct();
	}

	function identifyTypeOfBundle($localFile) {
		foreach($localFile as $fileEntry) {
			$ext = pathinfo($fileEntry, PATHINFO_EXTENSION);
			if(strtolower($ext) == 'obj') {
				return true;
			}
		}
	}

	public function allDerivativesForAccessLevel($accessLevel) {
		$derivative = array();

		/**
		 * normally, this array should be best to worst, but we pack original in here later so that it
		 * doesn't get displayed in the view
		 */
		// if($accessLevel>=PERM_ORIGINALSWITHOUTDERIVATIVES) {
		// 	$derivative[] = "original";
		// }

		if($accessLevel>=$this->getPermission()) {
			$derivative[] = "nxs";
			$derivative[] = "ply";
			$derivative[] = "stl";
			$derivative[] = "glb";
		}
		if($accessLevel>PERM_NOPERM) {
			$derivative[] = "thumbnail";
			$derivative[] = "thumbnail2x";
			$derivative[] = "tiny";
			$derivative[] = "tiny2x";
		}

		$returnArray = array();
		foreach($derivative as $entry) {
			if(isset($this->derivatives[$entry])) {
				$returnArray[$entry] = $this->derivatives[$entry];
			}
		}
		if(count($returnArray)>0) {
			return $returnArray;
		}
		else {
			throw new Exception("Derivative not found");
		}
	}

	public function extractMetadata($args) {

		$fileObject = $this->sourceFile;
		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		if($args['continue'] == true) {
			$this->queueTask(2);
		}

		return JOB_SUCCESS;
	}


	public function createDerivative($args) {

		$fileStatus = $this->sourceFile->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}

		$this->pheanstalk->touch($this->job);

		$zip = new ZipArchive;
		$res = $zip->open($this->sourceFile->getPathToLocalFile());

		$targetPath = $this->sourceFile->getPathToLocalFile() . "_extracted";
		if(!$res) {
			$this->logging->processingInfo("createDerivative","objHandler","Coudl not extract zip",$this->getObjectId(), 0);
			return JOB_FAILED;
		}

		$zip->extractTo($targetPath);
		$zip->close();

		$di = new RecursiveDirectoryIterator($targetPath,RecursiveDirectoryIterator::SKIP_DOTS);
		$it = new RecursiveIteratorIterator($di);
		$baseFolder = "";
		$objFile = "";
		$foundMTL = false;
		foreach($it as $file) {
			$onlyFilename = pathinfo($file, PATHINFO_FILENAME);
			if(substr($onlyFilename, 0,1) == ".") {
				continue;
			}

    		if(strtolower(pathinfo($file,PATHINFO_EXTENSION)) == "obj") {
    			$objFile = $file;
    			$baseFolder = pathinfo($file, PATHINFO_DIRNAME);
    		}
			if(strtolower(pathinfo($file,PATHINFO_EXTENSION)) == "mtl") {
				// if we found an mtl, we need ot apply a script for texutre conversion
				$foundMTL = TRUE;
    		}


		}


		$localPath = $this->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = 'ply';
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . 'ply' . '.ply';

		// we change dir inside docker so we have to pass in two args
		$meshlabCommandLine =  $this->config->item("meshlabPath") . " obj_to_ply " . $objFile . " " . $derivativeContainer->getPathToLocalFile() . ".ply";

		exec($meshlabCommandLine . " 2>/dev/null");
		if(!file_exists($derivativeContainer->getPathToLocalFile() . ".ply")) {
			// failed to process with the texture, let's try without.
			$this->logging->processingInfo("createDerivative","objHandler","Failed to generate PLY",$this->getObjectId(), 0);
			
		}

		rename($derivativeContainer->getPathToLocalFile() . ".ply", $derivativeContainer->getPathToLocalFile());

		$success = true;
		if(!$derivativeContainer->copyToRemoteStorage()) {
			$this->logging->processingInfo("createDerivative", "objHandler", "Could not upload ply", $this->getObjectId(), 0);
			echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
			$success=false;
		}
		else {
			$derivativeContainer->ready = true;
			if(!unlink($derivativeContainer->getPathToLocalFile())) {
				$this->logging->processingInfo("createThumbnails", "objHandler", "Could not delete source file", $this->getObjectId(), 0);
				echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$this->derivatives["ply"] = $derivativeContainer;



		// create a GLB file as well

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = 'glb';
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . 'glb' . '.glb';

		// we change dir inside docker so we have to pass in two args
		$meshlabCommandLine =  $this->config->item("blenderBinary") . "  -P /root/glb.py -- " . $objFile;

		exec($meshlabCommandLine . " 2>/dev/null");
		if(!file_exists($derivativeContainer->getPathToLocalFile() . ".glb")) {
			// failed to process with the texture, let's try without.
			$this->logging->processingInfo("createDerivative","objHandler","Failed to generate GLB",$this->getObjectId(), 0);
			
		}

		rename(str_replace(".obj",".glb", $objFile), $derivativeContainer->getPathToLocalFile());

		$success = true;
		if(!$derivativeContainer->copyToRemoteStorage()) {
			$this->logging->processingInfo("createDerivative", "objHandler", "Could not upload glb", $this->getObjectId(), 0);
			echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
			$success=false;
		}
		else {
			$derivativeContainer->ready = true;
			if(!unlink($derivativeContainer->getPathToLocalFile())) {
				$this->logging->processingInfo("createThumbnails", "objHandler", "Could not delete source file", $this->getObjectId(), 0);
				echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$this->derivatives["glb"] = $derivativeContainer;



		if($success) {
			$this->queueTask(3);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	public function createThumbnails($args) {

		$objHandler = new ObjHandler;
		$objHandler->job = $this->job;
		$objHandler->pheanstalk = $this->pheanstalk;
		$objHandler->sourceFile = $this->sourceFile;

		$result = $objHandler->createThumbInternal($this->derivatives['ply'], $args);
		if($result == JOB_POSTPONE) {
			return JOB_POSTPONE;
		}
		if($result == JOB_FAILED) {
			return JOB_FAILED;
		}
		else {
			$this->derivatives = array_merge($this->derivatives, $result);
		}
		$this->queueTask(4);
		$this->triggerReindex();
		return JOB_SUCCESS;

	}


	public function createNXS($args) {
		$objHandler = new ObjHandler;
		$objHandler->job = $this->job;
		$objHandler->pheanstalk = $this->pheanstalk;
		$objHandler->sourceFile = $this->sourceFile;
		

		$targetDerivative = $this->derivatives['ply'];
		
		$result = $objHandler->createNxsFileInternal($targetDerivative, $args);


		if($result == JOB_POSTPONE) {
			return JOB_POSTPONE;
		}
		if($result == JOB_FAILED) {
			return JOB_FAILED;
		}
		else {
			$this->derivatives = array_merge($this->derivatives, $result);
		}
		$this->queueTask(5);
		return JOB_SUCCESS;

	}

	public function createSTL($args) {
		$objHandler = new ObjHandler;
		$objHandler->job = $this->job;
		$objHandler->pheanstalk = $this->pheanstalk;
		$objHandler->sourceFile = $this->sourceFile;

		$result = $objHandler->createSTLInternal($this->derivatives['ply'], $args);
		if($result == JOB_POSTPONE) {
			return JOB_POSTPONE;
		}
		if($result == JOB_FAILED) {
			return JOB_FAILED;
		}
		else {
			$this->derivatives = array_merge($this->derivatives, $result);
		}
		return JOB_SUCCESS;

	}


	/**
	 * Override the parent handler and return the obj_handler
	 */

	public function getEmbedView($fileContainerArray, $includeOriginal=false, $embedded=false) {

		$uploadWidget = $this->getUploadWidget();

		$embedView = "objhandler";
		if($this->instance->getUseVoyagerViewer() == true && $this->derivatives['glb']->ready) {

			$embedView = "voyagerobjhandler";
			$this->template->set_template("noTemplateNoJS");
		}


		return $this->load->view("fileHandlers/embeds/" . $embedView, ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

	public function getEmbedViewWithFiles($fileContainerArray, $includeOriginal=false, $embedded=false) {

		if(!$this->parentObject && $this->parentObjectId) {
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

		return $this->load->view("fileHandlers/chrome/" . "objhandler_chrome", ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

	public function mungedSidecarData($sidecarData=null, $sidecarType=null) {
		if($sidecarType == "svx") {
			$svxData = $sidecarData['svx'];

			if(isset($svxData["models"]) && isset($svxData["models"][0]) && isset($svxData["models"][0]["derivatives"])) {
				$svxData["models"][0]["derivatives"][0]["assets"][0]["uri"] = $this->derivatives["glb"]->getProtectedURLForFile();

			}

			return json_encode($svxData);
		}
		else {
			return $sidecarData[$sidecarType]?:null;
		}

	}

}

/* End of file  */
/* Location: ./application/controllers/ */