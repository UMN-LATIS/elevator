<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class SPAHandler extends FileHandlerBase {

	protected $supportedTypes = array("spa");
	protected $noDerivatives = false;

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
						  1=>["taskType"=>"createDerivative", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  											    ["width"=>640, "height"=>640, "type"=>"screen", "path"=>"derivative"]]],
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
			$derivative[] = "screen";
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

		$this->pheanstalk->touch($this->job);

		$sourceFile = $this->swapLocalForPNG();
		if(!$sourceFile) {
			return JOB_FAILED;
		}

		foreach($args as $key=>$derivativeSetting) {
			$this->pheanstalk->touch($this->job);
			if(!is_numeric($key)) {
				continue;
			}
			$derivativeType = $derivativeSetting['type'];
			$width = $derivativeSetting['width'];
			$height = $derivativeSetting['height'];

			$fileStatus = $sourceFile->makeLocal();

			if($fileStatus == FILE_GLACIER_RESTORING) {
				$this->postponeTime = 900;
				return JOB_POSTPONE;
			}
			elseif($fileStatus == FILE_ERROR) {
				return JOB_FAILED;
			}

			$this->pheanstalk->touch($this->job);

			if(!file_exists($sourceFile->getPathToLocalFile())) {
				$this->logging->processingInfo("createDerivative","spaHandler","Local File Not Found",$this->getObjectId(),$this->job->getId());
				return JOB_FAILED;
			}

			$localPath = $sourceFile->getPathToLocalFile();
			$pathparts = pathinfo($localPath);

			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = $derivativeType;
			$derivativeContainer->path = $derivativeSetting['path'];
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $derivativeType . '.jpg';
			//TODO: catch errors here
			if(compressImageAndSave($sourceFile, $derivativeContainer, $width, $height)) {
				$derivativeContainer->ready = true;
				$this->extractMetadata(['fileObject'=>$derivativeContainer, "continue"=>false]);
				if(!$derivativeContainer->copyToRemoteStorage()) {
					//TODO: log
					//TODO: remove derivative
					echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
					$this->logging->processingInfo("createDerivative","spaHandler","Error copying to remote",$this->getObjectId(),$this->job->getId());
					$success=false;
				}
				else {
					if(!unlink($derivativeContainer->getPathToLocalFile())) {
						$this->logging->processingInfo("createDerivative","spaHandler","Error deleting source",$this->getObjectId(),$this->job->getId());
						echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
						$success=false;
					}
				}
				$this->derivatives[$derivativeType] = $derivativeContainer;
			}
			else {
				$this->logging->processingInfo("createDerivative","spaHandler","Error generating derivative",$this->getObjectId(),$this->job->getId());
				echo "Error generating deriative" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$this->unlinkLocalSwap();

		if($success) {
			$this->queueTask(2, ["ttr"=>600]);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}


	function unlinkLocalSwap() {
		$source = $this->sourceFile->getPathToLocalFile();
		$dest = $this->sourceFile->getPathToLocalFile() . ".png";
		if(file_exists($dest)) {
			unlink($dest);
		}
		return true;
	}


	function swapLocalForPNG() {

		$source = $this->sourceFile->getPathToLocalFile();
		$dest = $this->sourceFile->getPathToLocalFile() . ".png";
		if(file_exists($dest)) {
			return new FileContainer($dest);
		}

		$sourceFile = fopen($source, "rb");

		fseek($sourceFile, 386);
		$targetOffset = current(unpack("v", fread($sourceFile, 2)));
		if($targetOffset > filesize($source)) {
			return false;
		}
		fseek($sourceFile, 390);
		$dataLength = current(unpack("v", fread($sourceFile, 2)));
		if($dataLength + $targetOffset > filesize($source)) {
			return false;
		}

		fseek($sourceFile, $targetOffset);

		$rawData = fread($sourceFile, $dataLength);
		$rawDataOutputPath = $source . "_raw_data";
		$outputFile = fopen($rawDataOutputPath, "w");
		fwrite($outputFile, $rawData);
		fclose($outputFile);
		$gnuScript = "set terminal png size {width},{height};
			set output '{output}';
set xtics font 'Times-Roman, 30' offset 0,-1;
set ytics font 'Times-Roman, 30' offset 0,-0.5;
set lmargin 10;
set rmargin 5;
set bmargin 3;
unset key;
unset border;

		plot '<cat' binary filetype=bin format='%float32' endian=little array=1:0 with lines lw 3 lt rgb 'red';";

		$targetScript = str_replace("{output}", $dest, $gnuScript);
		$targetScript = str_replace("{width}", 2000, $targetScript);
		$targetScript = str_replace("{height}", 1600, $targetScript);
		$gnuPath = "gnuplot";
		$outputScript = "cat \"" . $rawDataOutputPath . "\" | " . $gnuPath . " -e \"" . $targetScript . "\"";
		exec($outputScript, $errorText);
		if(!file_exists($dest)) {
			$this->logging->processingInfo("createDerivative","spaHandler",$errorText,$this->getObjectId(),$this->job->getId());
			return false;
		}

		return new FileContainer($dest);

	}



}

/* End of file spaHandler.php */
/* Location: ./application/models/spaHandler.php */