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


		if(!file_exists($this->sourceFile->getPathToLocalFile())) {
			return JOB_FAILED;
		}

		$source = $this->sourceFile->getPathToLocalFile();
		$sourceFile = fopen($source, "rb");

		fseek($sourceFile, 370);
		$metadataStart = current(unpack("v", fread($sourceFile, 2)));
		fseek($sourceFile, 374);
		$metadataLength = current(unpack("v", fread($sourceFile, 2)));


		fseek($sourceFile, $metadataStart);
		$contents = fread($sourceFile, $metadataLength);
		$fileObject->metadata = $this->parseMetadata($contents);

		fseek($sourceFile, 596);
		$fileObject->metadata["Number of Scans"] = current(unpack("v", fread($sourceFile, 2)));


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


		$sourceFile = $this->swapLocalForPNG();
		if(!$sourceFile) {
			return JOB_FAILED;
		}

		foreach($args as $key=>$derivativeSetting) {
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
			if(compressImageAndSave($sourceFile, $derivativeContainer, $width, $height,100)) { // use no compresson (100% quality) for lines
				$derivativeContainer->ready = true;

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

		$this->triggerReindex();
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



		$resolution = explode(" ", $this->sourceFile->metadata["Resolution"]);
		$start = round($resolution[2]);
		$end = round($resolution[4]);

		$rScriptPath = $source . "_r_script";

		$search = array("START_RANGE", "END_RANGE", "X_LABEL", "Y_LABEL", "OUTPUT_PATH","SOURCE_PATH");
		$replace = array($start, $end, "Wavenumbers (cm-1)", $this->sourceFile->metadata["Final format"], $dest, $source);

		$rScript = str_replace($search, $replace, $this->rScript);

		file_put_contents($rScriptPath, $rScript);

		exec("/usr/local/bin/r-lang < " . $rScriptPath . " --no-save", $errorText);
		unlink($rScriptPath);
		if(!file_exists($dest)) {
			$this->logging->processingInfo("createDerivative","spaHandler",$errorText,$this->getObjectId(),$this->job->getId());
			return false;
		}

		return new FileContainer($dest);

	}

	function parseMetadata($dataBlock) {

		$metadataArray = array();
		$stopString = hex2bin("0D0A");
		$stopStringDecoded = join("", unpack("v",$stopString));
		$currentEntry = null;
		for($i=0; $i<strlen($dataBlock)-2; $i++) {
			$substring = substr($dataBlock, $i, 2);
			$targetStringCollapsed = join("", unpack("v", $substring));

			if($targetStringCollapsed == $stopStringDecoded) {
				$metadataArray[] = $currentEntry;
				$currentEntry = null;
				$i = $i+1;
			}
			else {
				$currentEntry = $currentEntry . (string)$dataBlock[$i];
			}

		}

		if($currentEntry) {
			$metadataArray[] = $currentEntry;
		}

		$cleanedArray = array();
		foreach($metadataArray as $entry) {
			if(strstr($entry, " on ") && !strpos($entry, ":")) {
				$cleanedArray[] = trim(htmlentities($entry,  ENT_COMPAT , "ISO-8859-15"));
			}
			else {
				$entry = array_map('trim', explode(':', htmlentities($entry,  ENT_COMPAT , "ISO-8859-15"), 2));
				if(count($entry)>1) {
					$cleanedArray[$entry[0]] = $entry[1];
				}
				else {
					$cleanedArray[] = $entry[0];
				}

			}


		}

		return $cleanedArray;

	}


	private $rScript = <<< EOD
library(ggplot2)

startRange <- START_RANGE;
endRange <- END_RANGE;
xLabel <- "X_LABEL";
yLabel <- "Y_LABEL";
outputFile = "OUTPUT_PATH";

pathToSource <- "SOURCE_PATH";
to.read = file(pathToSource, "rb");

# Read the start offset
seek(to.read, 386, origin="start");
startOffset <- readBin(to.read, "int", n=1, size=2);
# Read the length
seek(to.read, 390, origin="start");
readLength <- readBin(to.read, "int", n=1, size=2);

# seek to the start
seek(to.read, startOffset, origin="start");

# we'll read four byte chunks
floatCount <- readLength/4;

spacing <- (endRange - startRange) / floatCount;

# read all our floats
floatData <- c(readBin(to.read,"double",floatCount, size=4))
sequence <- seq(from = startRange, to = endRange, by = spacing)

if(length(sequence) < length(floatData)) {
  while(length(sequence) < length(floatData)) {
    sequence <- append(sequence, tail(sequence, n=1) + spacing);
    print(length(sequence));
  }


} else if(length(sequence) > length(floatData)) {
  while(length(sequence) > length(floatData)) {
      sequence <- sequence[-length(sequence)];
  }
}

floatDataFrame <- as.data.frame(floatData)
floatDataFrame\$ID<-rev(sequence)

png(outputFile, height=600, width=600)
p.plot <- ggplot(data = floatDataFrame,aes(x=ID, y=floatData))  + xlab(xLabel)+ ylab(yLabel)
p.plot + geom_line(aes(group=1), colour="red") + theme_bw()  + scale_x_reverse(lim=c(endRange, startRange))
dev.off()

EOD;

}

/* End of file spaHandler.php */
/* Location: ./application/models/spaHandler.php */