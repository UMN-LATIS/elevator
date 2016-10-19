<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


//require_once("fileHandlerBase.php");
class BoxHandler extends FileHandlerBase {

	protected $supportedTypes = array();
	protected $noDerivatives = true;

	// public $icon = "doc.png";

	public $taskArray = [0=>["taskType"=>"extractMetadataAndRequestConversion", "config"=>array()],
						 1=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												],
						 2=>["taskType"=>"extractText", "config"=>array("docId"=>null)],
						 3=>["taskType"=>"updateParent", "config"=>array()]];



	public function __construct()
	{
		parent::__construct();
		$this->load->helper("media");
		$this->load->library("BoxHelper");
		//Do your magic here
	}

	public function supportsType($fileType) {

		if($this->instance !== NULL && $this->instance->getBoxKey() && strlen($this->instance->getBoxKey())>5) {
			if(in_array(strtolower($fileType), $this->supportedTypes)) {
				return TRUE;
			}
		}

		return false;

	}


	public function allDerivativesForAccessLevel($accessLevel) {
			$derivative = array();

		if($accessLevel>=$this->getPermission()) {
			$derivative[] = "boxView";
			$derivative[] = "pdf";
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


	public function extractMetadataAndRequestConversion($args) {

		if(!isset($args['fileObject'])) {
			$fileObject = $this->sourceFile;
		}
		else {
			$fileObject = $args['fileObject'];
		}


		if($this->sourceFile->isArchived()) {
			$this->sourceFile->restoreFromArchive();
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}

		$this->pheanstalk->touch($this->job);


		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		if(!$this->instance->getBoxKey()) {
			return JOB_SUCCESS;
		}

		$boxClient = new BoxHelper($this->instance->getBoxKey());
		if(!$boxClient->createDocumentFromURL($this->sourceFile->getProtectedURLForFile())) {
			$this->logging->logError("box error", $boxClient->error);
			return JOB_FAILED;
		}


		$this->queueTask(1, ["docId"=>$boxClient->getDocumentId()]);

		return JOB_SUCCESS;
	}

	public function createThumbnails($args) {
		if(!isset($args['docId'])) {
			return JOB_FAILED;
		}

		$boxClient = new BoxHelper($this->instance->getBoxKey());
		if(!$boxClient->setDocumentId($args['docId'])) {
			$this->logging->logError("box error", "failed to load document: " . $boxClient->error);
			return JOB_FAILED;
		}

		if(!$boxClient->checkIfReady()) {
			return JOB_POSTPONE;
		}

		$sourcePath = $this->sourceFile->getPathToLocalFile() . "_image";

		if(!$boxClient->getThumbnail($sourcePath)) {
			return JOB_FAILED;
		}

		$sourceContainer = new FileContainer($sourcePath);


		ini_set('memory_limit', '512M');
		$success = true;
		foreach($args as $derivativeSetting) {
			if(!is_array($derivativeSetting)) {
				continue;
			}

			$derivativeType = $derivativeSetting["type"];
			$width = $derivativeSetting["width"];
			$height = $derivativeSetting["height"];

			$localPath = $sourcePath;
			$pathparts = pathinfo($localPath);

			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = $derivativeType;
			$derivativeContainer->path = $derivativeSetting['path'];
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $derivativeType . '.jpg';

			if(compressImageAndSave($sourceContainer, $derivativeContainer, $width, $height)) {
				$derivativeContainer->ready = true;

				if(!$derivativeContainer->copyToRemoteStorage()) {
					//TODO: log
					//TODO: remove derivative
					$this->logging->processingInfo("createThumbnails", "boxhandler", "Could not upload thumbnail", $this->getObjectId(), $this->job->getId());
					echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
					$success=false;
				}
				else {
					if(!unlink($derivativeContainer->getPathToLocalFile())) {
						$this->logging->processingInfo("createThumbnails", "boxhandler", "Could not delete source file", $this->getObjectId(), $this->job->getId());
						echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
						$success=false;
					}
				}
				$this->derivatives[$derivativeType] = $derivativeContainer;
			}
			else {
				$this->logging->processingInfo("createThumbnails", "boxhandler", "Could not create derivative", $this->getObjectId(), $this->job->getId());
				echo "Error generating deriative" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}

		$this->triggerReindex();

		if($success) {
			unlink($sourcePath);
			$this->queueTask(2, ["docId"=>$args["docId"]]);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	public function extractText($args) {

		if(!isset($args['docId'])) {
			return JOB_FAILED;
		}

		$boxClient = new BoxHelper($this->instance->getBoxKey());
		if(!$boxClient->setDocumentId($args['docId'])) {
			$this->logging->logError("box error", "failed to load document: ". $boxClient->error);
			return JOB_FAILED;
		}

		if(!$boxClient->checkIfReady()) {
			return JOB_POSTPONE;
		}


		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "pdf";
		$derivativeContainer->path = "derivative";
		$derivativeContainer->originalFilename = $this->sourceFile->getPathToLocalFile() . "_pdf";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$this->derivatives['pdf'] = $derivativeContainer;
		$localPath = $boxClient->getAsPDF($derivativeContainer->getPathToLocalFile());
		if($localPath) {
			if($derivativeContainer->copyToRemoteStorage()) {
				$derivativeContainer->ready = true;
				$derivativeContainer->removeLocalFile();
			}
		}


		$sourcePath = $this->sourceFile->getPathToLocalFile() . "_boxView";

		$pathparts = pathinfo($sourcePath);
		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "boxView";
		$derivativeContainer->path = "derivative";
		$derivativeContainer->originalFilename = $pathparts['filename'];
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$this->derivatives['boxView'] = $derivativeContainer;


		$pathToContent = $boxClient->getZippedContents($derivativeContainer->getPathToLocalFile());
		if($pathToContent) {
			$summedText = $boxClient->extractText();

			$this->globalMetadata["text"] = $summedText;
		}

		$this->load->helper("file");
 		if($this->putAllFilesInFolderToKey($derivativeContainer->getPathToLocalFile() ."/assets", "derivative/". $this->getReversedObjectId() . "-boxView", null)) {
 			$derivativeContainer->ready = true;
        	delete_files($derivativeContainer->getPathToLocalFile(), true);
        }
        else {
        	return JOB_FAILED;
        }

		$boxClient->deleteDocument();
		$this->queueTask(3);
		return JOB_SUCCESS;
	}

	private function putAllFilesInFolderToKey($folder, $destKey, $mimeType=null) {
		$files =  array_diff(scandir( $folder),array('..', '.'));
		foreach($files as $file) {
			if(substr($file, 0,1) == ".") {
				continue;
			}
			$this->pheanstalk->touch($this->job);
        	$pathToFile = $folder . "/" . $file;

        	if(!$this->s3model->putObject($pathToFile, $destKey . "/" . $file)) {
        		$this->logging->processingInfo("putAllFilesInFolderToKey", "uploading file failed","", $pathToFile, $this->job->getId());
        		continue;
        	}
        	if($mimeType) {
        		$this->s3model->setContentType($destKey . "/" . $file, $mimeType);
        	}
        	else {
        		$finfo = finfo_open(FILEINFO_MIME_TYPE);
        		$mimeType = finfo_file($finfo, $pathToFile);
        		finfo_close($finfo);
        		$this->s3model->setContentType($destKey . "/" . $file, $mimeType);
        	}
        }
        return TRUE;

	}

}

/* End of file boxHandler.php */
/* Location: ./application/models/boxHandler.php */