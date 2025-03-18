<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class ImageSvsHandler extends FileHandlerBase {

	protected $supportedTypes = array("");
	protected $noDerivatives = false;

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
						  1=>["taskType"=>"createDerivative", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>75, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>150, "type"=>"tiny2x", "path"=>"thumbnail"],
						  											    ]],
							2=>["taskType"=>"tileImage", "config"=>array()],
							3=>["taskType"=>"cleanupOriginal", "config"=>array()]
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
			$derivative[] = "svs";
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
				$returnArray[$entry]->downloadable = true;
			}
		}
		if(count($returnArray)>0) {
			return $returnArray;
		}
		else {
			throw new Exception("Derivative not found");
		}
	}


	/**
	 * lame hack because imagick can't handle SVS files at the moment
	 */

	function swapLocalForPNG() {

		$source = $this->sourceFile->getPathToLocalFile();
		$dest = $this->sourceFile->getPathToLocalFile() . ".png";
		if(file_exists($dest)) {
			return $dest;
		}
		$convertString = $this->config->item('vipsBinary') . " shrink " . $source . " " . $dest . " " . "10 10";



		exec($convertString . " 2>/dev/null");

		return $dest;
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

		if(!file_exists($this->sourceFile->getPathToLocalFile())) {
			return JOB_FAILED;
		}


		$fileContainer = new FileContainer($this->swapLocalForPNG());

		$fileObject->metadata = getImageMetadata($fileContainer);

		if(!$fileObject->metadata) {
			if($fileFormat = identifyImage($fileContainer)) {
				$originalName = $this->sourceFile->originalFilename;
				$originalExtension = pathinfo($originalName, PATHINFO_EXTENSION);
				$originalName = str_replace($originalExtension, $fileFormat, $originalName);

				$this->sourceFile->originalFilename = $originalName;
				if(false === ($fileObject->metadata = getImageMetadata($fileContainer))) {
					return JOB_FAILED;
				}
			}
			else {
				return JOB_FAILED;
			}
		}


		if(!$fileObject->metadata) {

			return JOB_FAILED;
		}

		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();



		if($args['continue'] == true) {
			$this->queueTask(1, ["ttr"=>600]);
		}

		return JOB_SUCCESS;
	}

	public function createDerivative($args) {
		$success = true;
		$fileStatus = $this->sourceFile->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}

		if(!file_exists($this->sourceFile->getPathToLocalFile())) {
			$this->logging->processingInfo("createDerivative","imageHandler","Local File Not Found",$this->getObjectId(),0);
			return JOB_FAILED;
		}

		$fileContainer = new FileContainer($this->swapLocalForPNG());

		foreach($args as $key=>$derivativeSetting) {
			if(!is_numeric($key)) {
				continue;
			}
			$derivativeType = $derivativeSetting['type'];
			$width = $derivativeSetting['width'];
			$height = $derivativeSetting['height'];





			$localPath = $fileContainer->getPathToLocalFile();
			$pathparts = pathinfo($localPath);

			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = $derivativeType;
			$derivativeContainer->path = $derivativeSetting['path'];
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $derivativeType . '.jpg';
			//TODO: catch errors here
			if(compressImageAndSave($fileContainer, $derivativeContainer, $width, $height)) {
				$derivativeContainer->ready = true;
				$this->extractMetadata(['fileObject'=>$derivativeContainer, "continue"=>false]);
				if(!$derivativeContainer->copyToRemoteStorage()) {
					//TODO: log
					//TODO: remove derivative
					echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
					$this->logging->processingInfo("createDerivative","imageHandler","Error copying to remote",$this->getObjectId(),0);
					$success=false;
				}
				else {
					if(!unlink($derivativeContainer->getPathToLocalFile())) {
						$this->logging->processingInfo("createDerivative","imageHandler","Error deleting source",$this->getObjectId(),0);
						echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
						$success=false;
					}
				}
				$this->derivatives[$derivativeType] = $derivativeContainer;
			}
			else {
				$this->logging->processingInfo("createDerivative","imageHandler","Error generating derivative",$this->getObjectId(),0);
				echo "Error generating deriative" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}

		$this->triggerReindex();
		if($success) {
			$this->queueTask(2, ["ttr"=>600]);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}


	public function tileImage($args) {
		$fileStatus = $this->sourceFile->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}

		$derivativeType = "svs";

		if(!file_exists($this->sourceFile->getPathToLocalFile())) {
			$this->logging->processingInfo("createDerivative","imageSVSHandler","Local File Not Found",$this->getObjectId(),0);
			return JOB_FAILED;
		}

		$localPath = $this->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = $derivativeType;
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $derivativeType;
		//TODO: catch errors here

		$outputFile = $localPath . ".zip";
		$outputPath = $localPath . "-uncompressed";
		$extractString = $this->config->item('vipsBinary') . " dzsave " . $localPath . " " . $outputFile . " " . "--background 255 255  --layout google";

		exec($extractString . " 2>/dev/null");
		if(!file_exists($outputFile)) {
			$this->logging->processingInfo("createDerivative","imageSVSHandler","Tiling failed",$this->getObjectId(),0);
			return JOB_FAILED;
		}
		$this->load->helper('file');
		$zip = new ZipArchive;
		if ($zip->open($outputFile) === TRUE) {
			if(!file_exists($outputPath)) {
				mkdir($outputPath);
			}

			if($zip->extractTo($outputPath)) {
				$pathToExtractedFiles = $outputPath . "/" . $this->getReversedObjectId() . "-source"; // vips bakes this in.
				$zip->close();
				if($this->s3model->putDirectory($pathToExtractedFiles, "derivative/". $this->getReversedObjectId() . "-svs")) {
					delete_files($outputPath, true);
				}
			}
		}

		$this->derivatives[$derivativeType] = $derivativeContainer;
		unlink($this->swapLocalForPNG());
		$this->queueTask(3);
		return JOB_SUCCESS;



	}



}

/* End of file imageHandler.php */
/* Location: ./application/models/imageHandler.php */
