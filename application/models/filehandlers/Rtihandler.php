<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class RTIHandler extends FileHandlerBase {

	protected $supportedTypes = array("rti", "ptm");
	protected $noDerivatives = false;

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
						  1=>["taskType"=>"createThumbnail", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												],
							2=>["taskType"=>"cleanupOriginal", "config"=>array()]
							];




	public function __construct()
	{
		parent::__construct();
		$this->load->helper("media");
		//Do your magic here
	}

	public function allDerivativesForAccessLevel($accessLevel) {
		$derivative = array();

		if($accessLevel>=$this->getPermission()) {
			$derivative[] = "tiled";
		}

		if($accessLevel>PERM_NOPERM) {
			$derivative[] = "thumbnail";
			$derivative[] = "thumbnail2x";
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

		if(!isset($args['fileObject'])) {
			$fileObject = $this->sourceFile;
		}
		else {
			$fileObject = $args['fileObject'];
		}

		$fileStatus = $this->sourceFile->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}

		$this->pheanstalk->touch($this->job);

		if(!file_exists($this->sourceFile->getPathToLocalFile())) {
			return JOB_FAILED;
		}

		$source = $this->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($source);

	
		$extension = $this->asset->getFileType();

		$extractedPath = $source . "-extracted";	
		if(!file_exists($extractedPath)) {
			mkdir($extractedPath);	
		}


		$destinationName = $source . "-extracted" . "." . $extension;	
		rename($source, $destinationName);

	
		$rtiPath =  $this->config->item("rtiBuild");

		
		$options = "cd " . $extractedPath . "; /usr/bin/xvfb-run " . $rtiPath . " " . $destinationName;
		exec($options);
		if(file_exists($extractedPath . "/" . "info.xml")) {
			$fileContents = file_get_contents($extractedPath . "/" . "info.xml");
			$info = new SimpleXMLElement($fileContents);
			$fileObject->metadata["type"] = $info->Content["type"][0];
			$fileObject->metadata["width"] = $info->Content->Size["width"][0];
			$fileObject->metadata["height"] = $info->Content->Size["height"][0];
			$fileObject->metadata["scale"] = $info->Content->Scale[0];
			$fileObject->metadata["bias"] = $info->Content->Bias[0];
		}
		else {
			return JOB_FAILED;
		}

		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();


		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "tiled";
		$derivativeContainer->path = "derivative";
		$derivativeContainer->originalFilename = $pathparts['filename'];
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$this->derivatives['tiled'] = $derivativeContainer;

		$this->load->helper("file");
 		if($this->putAllFilesInFolderToKey($extractedPath, "derivative/". $this->getReversedObjectId() . "-tiled", null)) {
 			$derivativeContainer->ready = true;
 			delete_files($extractedPath, true);
 		}
 		else {
        	return JOB_FAILED;
        }

		if($args['continue'] == true) {
			$this->queueTask(1, ["ttr"=>600]);
		}

		return JOB_SUCCESS;
	}

	public function createThumbnail($args) {
		$success = true;

		$tiled = $this->derivatives['tiled'];
		$targetDerivativeURL = $tiled->getProtectedURLForFile("/1_1.jpg");
		$targetDerivativeFile = $tiled->getPathToLocalFile();
		$targetDerivativeContainer = new FileContainer($targetDerivativeFile);
		file_put_contents($targetDerivativeFile, file_get_contents($targetDerivativeURL));

		foreach($args as $key=>$derivativeSetting) {
			$this->pheanstalk->touch($this->job);
			if(!is_numeric($key)) {
				continue;
			}
			$derivativeType = $derivativeSetting['type'];
			$width = $derivativeSetting['width'];
			$height = $derivativeSetting['height'];

			$this->pheanstalk->touch($this->job);

			if(!file_exists($targetDerivativeFile)) {
				$this->logging->processingInfo("createDerivative","rtiHandler","Local File Not Found",$this->getObjectId(),$this->job->getId());
				return JOB_FAILED;
			}

			$localPath = $this->sourceFile->getPathToLocalFile();
			$pathparts = pathinfo($localPath);

			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = $derivativeType;
			$derivativeContainer->path = $derivativeSetting['path'];
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $derivativeType . '.jpg';
			//TODO: catch errors here
			echo "Compressing " . $width . " x " . $height . "\n";
			if(compressImageAndSave($targetDerivativeContainer, $derivativeContainer, $width, $height)) {
				$derivativeContainer->ready = true;
				if(!$derivativeContainer->copyToRemoteStorage()) {
					//TODO: log
					//TODO: remove derivative
					echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
					$this->logging->processingInfo("createDerivative","rtiHandler","Error copying to remote",$this->getObjectId(),$this->job->getId());
					$success=false;
				}
				else {
					if(!unlink($derivativeContainer->getPathToLocalFile())) {
						$this->logging->processingInfo("createDerivative","rtiHandler","Error deleting source",$this->getObjectId(),$this->job->getId());
						echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
						$success=false;
					}
				}
				$this->derivatives[$derivativeType] = $derivativeContainer;
			}
			else {
				$this->logging->processingInfo("createDerivative","rtiHandler","Error generating derivative",$this->getObjectId(),$this->job->getId());
				echo "Error generating deriative" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$this->triggerReindex();
		if($success) {
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

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

/* End of file spaHandler.php */
/* Location: ./application/models/spaHandler.php */