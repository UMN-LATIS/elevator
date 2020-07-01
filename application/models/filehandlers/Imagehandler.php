<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class ImageHandler extends FileHandlerBase {

	protected $supportedTypes = array("jpg","jpeg", "gif","png","tiff", "tif", "tga", "crw", "cr2", "nef", "svs", "psd", "cr2", "heic", "jfif", "jp2", 'ndpi');
	protected $noDerivatives = false;

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true, "ttr"=>600]],
						  1=>["taskType"=>"createDerivative", "config"=>["ttr"=>600, ["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>75, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>150, "type"=>"tiny2x", "path"=>"thumbnail"],
						  											    ["width"=>2048, "height"=>2048, "type"=>"screen", "path"=>"derivative"]]],
							// 2=>["taskType"=>"clarifyTag", "config"=>array()],
							2=>["taskType"=>"tileImage", "config"=>array("ttr"=>1800, "minimumMegapixels"=>30)],
							3=>["taskType"=>"cleanupOriginal", "config"=>array()]
							];

	public $sphericalTaskArray = [
						  1=>["taskType"=>"createDerivative", "config"=>["ttr"=>600, ["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>75, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>150, "type"=>"tiny2x", "path"=>"thumbnail"],
						  											    ["width"=>4096, "height"=>2048, "type"=>"screen", "path"=>"derivative"]]],
						2=>["taskType"=>"tileImage", "config"=>array("ttr"=>1800, "minimumMegapixels"=>30)],
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
			$derivative[] = "screen";
			if(array_key_exists("tiled", $this->derivatives)) {
				$derivative[] = "tiled";
			}
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
		$analyzingDerivative = false;
		if(!isset($args['fileObject'])) {
			$fileObject = $this->sourceFile;
		}
		else {
			$fileObject = $args['fileObject'];
			$analyzingDerivative = true;
		}

		$fileObject->metadata = array(); // clear metadata in case we're regenerating.

		if(!$analyzingDerivative) {
			$fileStatus = $fileObject->makeLocal();

			if($fileStatus == FILE_GLACIER_RESTORING) {
				$this->postponeTime = 900;
				return JOB_POSTPONE;
			}
			elseif($fileStatus == FILE_ERROR) {
				return JOB_FAILED;
			}
		}

		$this->pheanstalk->touch($this->job);

		if(!file_exists($fileObject->getPathToLocalFile())) {
			return JOB_FAILED;
		}


		$fileObject->metadata = getImageMetadata($fileObject);

		if($analyzingDerivative) { 
			$sourceFile = $fileObject;
		}
		else {
			$sourceFile = $this->swapLocalForPNG();
		}
		


		if(!$fileObject->metadata) {
			if($fileFormat = identifyImage($sourceFile)) {
				$originalName = $sourceFile->originalFilename;
				$originalExtension = pathinfo($originalName, PATHINFO_EXTENSION);
				$originalName = str_replace($originalExtension, $fileFormat, $originalName);

				$sourceFile->originalFilename = $originalName;
				if(false === ($fileObject->metadata = getImageMetadata($sourceFile))) {
					return JOB_FAILED;
				}
			}
			else {
				return JOB_FAILED;
			}
		}

		/**
		 * As these standards evolve this should be refactored
		 */
		$uploadWidget = $this->getUploadWidget();
		if((isset($fileObject->metadata["exif"]) && isset($fileObject->metadata["exif"]["XMP"]) && isset($fileObject->metadata["exif"]["XMP"]["UsePanoramaViewer"]) && $fileObject->metadata["exif"]["XMP"]["UsePanoramaViewer"] == true) || stristr($uploadWidget->fileDescription, "spherical")) {
			$fileObject->metadata["spherical"] = true;
			$this->taskArray = $this->sphericalTaskArray; // swap out our task array to get a bigger max size for our derivatives in this case.
			
			if(stristr($uploadWidget->fileDescription, "stereo")) {
				$fileObject->metadata["stereo"] = true;
			}
		}


		if(!$fileObject->metadata) {

			return JOB_FAILED;
		}

		$fileObject->metadata["filesize"] = $sourceFile->getFileSize();



		if($args['continue'] == true) {
			$this->queueTask(1, ["ttr"=>1200]);
		}

		return JOB_SUCCESS;
	}

	public function createDerivative($args) {
		$success = true;

		$sourceFile = $this->swapLocalForPNG();


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
				$this->logging->processingInfo("createDerivative","imageHandler","Local File Not Found",$this->getObjectId(),$this->job->getId());
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
			echo "Compressing " . $width . " x " . $height . "\n";
			if(compressImageAndSave($sourceFile, $derivativeContainer, $width, $height)) {
				$derivativeContainer->ready = true;
				$this->extractMetadata(['fileObject'=>$derivativeContainer, "continue"=>false]);
				if(!$derivativeContainer->copyToRemoteStorage()) {
					//TODO: log
					//TODO: remove derivative
					echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
					$this->logging->processingInfo("createDerivative","imageHandler","Error copying to remote",$this->getObjectId(),$this->job->getId());
					$success=false;
				}
				else {
					if(!unlink($derivativeContainer->getPathToLocalFile())) {
						$this->logging->processingInfo("createDerivative","imageHandler","Error deleting source",$this->getObjectId(),$this->job->getId());
						echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
						$success=false;
					}
				}
				$this->derivatives[$derivativeType] = $derivativeContainer;
			}
			else {
				$this->logging->processingInfo("createDerivative","imageHandler","Error generating derivative",$this->getObjectId(),$this->job->getId());
				echo "Error generating deriative" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$this->triggerReindex();
		if($success) {
			$this->queueTask(2, ["ttr"=>1800]);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	public function tileImage($args) {
		$uploadWidget = $this->getUploadWidget();

		if(!$uploadWidget->parentWidget->enableTiling) {
			$this->queueTask(3);
			return JOB_SUCCESS;
		}

		$megapixels = ($this->sourceFile->metadata["width"] * $this->sourceFile->metadata["height"]) / 1000000;

		if($megapixels < $args["minimumMegapixels"] && !$this->forceTiling()) {
			$this->queueTask(3);
			return JOB_SUCCESS;
		}
		// don't swap, VIPS can handle SVS
		$sourceFile = $this->sourceFile;



		$localPath = $sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);

		$derivativeType = "tiled";

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = $derivativeType;
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $derivativeType;


		$outputPath = $localPath . "-tiled";
		//TODO: catch errors here
		if(!file_exists($outputPath)) {
			mkdir($outputPath);
		}

		$outputFile = $outputPath ."/tiledBase";

		$extractString = $this->config->item('vipsBinary') . " dzsave " . $localPath . " " . $outputFile;
		$process = new Cocur\BackgroundProcess\BackgroundProcess($extractString);
		$process->run();
		while($process->isRunning()) {
			sleep(5);
			$this->pheanstalk->touch($this->job);
			echo ".";
		}


		if(!file_exists($outputFile . ".dzi")) {
			$this->logging->processingInfo("createDerivative","imageHandler","Tiling failed",$this->getObjectId(),$this->job->getId());
			return JOB_FAILED;
		}


		$dziContents = file_get_contents($outputFile . ".dzi");
		$dzi = new SimpleXMLElement($dziContents);
		$dziHeight = (int)$dzi->Size[0]["Height"];
		$dziWidth = (int)$dzi->Size[0]["Width"];
		$overlap = (int)$dzi["Overlap"];
		$tilesize = (int)$dzi["TileSize"];
		$this->sourceFile->metadata["dziWidth"] = $dziWidth;
		$this->sourceFile->metadata["dziHeight"] = $dziHeight;
		$this->sourceFile->metadata["dziOverlap"] = $overlap;
		$this->sourceFile->metadata["dziTilesize"] = $tilesize;

		$zoomContents = scandir($outputPath . "/tiledBase_files/");
		$zoomLevels = count($zoomContents) - 2;
		$this->sourceFile->metadata["dziMaxZoom"] = $zoomLevels;

		echo "\n";
		$this->pheanstalk->touch($this->job);
		
		if($this->s3model->putDirectory($outputPath, "derivative/". $this->getReversedObjectId() . "-tiled", null, $this->job)) {
			$this->load->helper('file');
			delete_files($outputPath, true);
		}

		$this->pheanstalk->touch($this->job);

		$this->derivatives[$derivativeType] = $derivativeContainer;
		$this->unlinkLocalSwap();

		$this->queueTask(3);
		return JOB_SUCCESS;

	}


	// public function clarifyTag($args) {


	// 	if(strlen($this->instance->getClarifaiId())<5) {
	// 		$this->queueTask(3);
	// 		return JOB_SUCCESS;
	// 	}
	// 	$this->load->library("Clarifai");

	// 	$clarifai = new Clarifai($this->instance->getClarifaiId(), $this->instance->getClarifaiSecret(), $this->instance->getDomain());
	// 	if(!$clarifai->collectionExists($this->instance->getDomain())) {
	// 		$clarifai->addCollection($this->instance->getDomain());
	// 	}

	// 	if(!isset($this->derivatives["screen"])) {
	// 		return JOB_FAILED;
	// 	}

	// 	$targetURL= $this->derivatives["screen"]->getProtectedURLForFile();

	// 	if(!$clarifai->addDocument($targetURL, $this->getObjectId())) {
	// 		return JOB_POSTPONE;
	// 	}

	// 	$document = $clarifai->getDocument($this->getObjectId());

	// 	$resultTags = array();
	// 	foreach($document->document->annotation_sets[0]->annotations as $tagCluster) {

	// 		$resultTags[] = ["tag"=>$tagCluster->tag->cname, "score"=>$tagCluster->score];

	// 	}

	// 	$this->globalMetadata["tags"] = $resultTags;

	// 	$this->queueTask(3);
	// 	return JOB_SUCCESS;

	// }

	function unlinkLocalSwap() {
		if(isWholeSlideImage($this->sourceFile)) {
			$source = $this->sourceFile->getPathToLocalFile();
			$dest = $this->sourceFile->getPathToLocalFile() . ".png";
			if(file_exists($dest)) {
				unlink($dest);
			}
		}
		return true;
	}

	function forceTiling() {
		if(isWholeSlideImage($this->sourceFile)) {
			return TRUE;
		 }
		 $uploadWidget = $this->getUploadWidget();
		 if((isset($uploadWidget->parentWidget->enableDendro) && $uploadWidget->parentWidget->enableDendro) || (isset($uploadWidget->parentWidget->enableAnnotation) && $uploadWidget->parentWidget->enableAnnotation) || (isset($uploadWidget->parentWidget->forceTiling) && $uploadWidget->parentWidget->forceTiling)) {
			 return TRUE;
		 }

 		return FALSE;

	}

	function swapLocalForPNG() {
		if(isWholeSlideImage($this->sourceFile)) {
			$source = $this->sourceFile->getPathToLocalFile();
			$dest = $this->sourceFile->getPathToLocalFile() . ".png";
			if(file_exists($dest)) {
				return new FileContainer($dest);
			}
			$convertString = $this->config->item('vipsBinary') . " shrink " . $source . " " . $dest . " " . "10 10";
			$process = new Cocur\BackgroundProcess\BackgroundProcess($convertString);
			$process->run();
			while($process->isRunning()) {
				sleep(5);
				$this->pheanstalk->touch($this->job);
				echo ".";
			}

			return new FileContainer($dest);
		}

		$megapixels = 0;
		if(isset($this->sourceFile->metadata) && isset($this->sourceFile->metadata["width"])) {
			$megapixels = ($this->sourceFile->metadata["width"] * $this->sourceFile->metadata["height"]) / 1000000;
		}
		
		if($megapixels > 100) {
			$source = $this->sourceFile->getPathToLocalFile();
			$dest = $this->sourceFile->getPathToLocalFile() . ".png";
			if(file_exists($dest)) {
				return new FileContainer($dest);
			}
			$convertString = $this->config->item('vipsBinary') . " shrink " . $source . " " . $dest . " " . "10 10";
			$process = new Cocur\BackgroundProcess\BackgroundProcess($convertString);
			$process->run();
			while($process->isRunning()) {
				sleep(5);
				$this->pheanstalk->touch($this->job);
				echo ".";
			}

			return new FileContainer($dest);


		}


		
		return $this->sourceFile;
	}



}

/* End of file imageHandler.php */
/* Location: ./application/models/imageHandler.php */
