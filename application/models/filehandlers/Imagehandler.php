<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class ImageHandler extends FileHandlerBase {

	protected $supportedTypes = array("jpg","jpeg", "gif","png","tiff", "tif", "tga", "crw", "cr2", "nef", "svs");
	protected $noDerivatives = false;

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
						  1=>["taskType"=>"createDerivative", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>75, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>150, "type"=>"tiny2x", "path"=>"thumbnail"],
						  											    ["width"=>2048, "height"=>2048, "type"=>"screen", "path"=>"derivative"]]],
							2=>["taskType"=>"clarifyTag", "config"=>array()],
							3=>["taskType"=>"tileImage", "config"=>array("ttr"=>600, "minimumMegapixels"=>30)],
							4=>["taskType"=>"cleanupOriginal", "config"=>array()]
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

		$sourceFile = $this->swapLocalForPNG();


		$fileObject->metadata = getImageMetadata($sourceFile);

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


		if(!$fileObject->metadata) {

			return JOB_FAILED;
		}

		$fileObject->metadata["filesize"] = $sourceFile->getFileSize();



		if($args['continue'] == true) {
			$this->queueTask(1, ["ttr"=>600]);
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
			$this->queueTask(2, ["ttr"=>600]);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	public function tileImage($args) {
		$uploadWidget = $this->getUploadWidget();

		if(!$uploadWidget->parentWidget->enableTiling) {
			$this->queueTask(4);
			return JOB_SUCCESS;
		}

		$megapixels = ($this->sourceFile->metadata["width"] * $this->sourceFile->metadata["height"]) / 1000000;

		if($megapixels < $args["minimumMegapixels"] && !$this->forceTiling()) {
			$this->queueTask(4);
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

		$extractString = $this->config->item('vipsBinary') . " dzsave " . $localPath . "[autorotate] " . $outputFile;

		exec($extractString . " 2>/dev/null");
		if(!file_exists($outputFile . ".dzi")) {
			$this->logging->processingInfo("createDerivative","imageHandler","Tiling failed",$this->getObjectId(),$this->job->getId());
			return JOB_FAILED;
		}


		$dziContents = file_get_contents($outputFile . ".dzi");
		$dzi = new SimpleXMLElement($dziContents);
		$dziHeight = (int)$dzi->Size[0]["Height"];
		$dziWidth = (int)$dzi->Size[0]["Width"];
		$this->sourceFile->metadata["dziWidth"] = $dziWidth;
		$this->sourceFile->metadata["dziHeight"] = $dziHeight;

		if($this->s3model->putDirectory($outputPath, "derivative/". $this->getReversedObjectId() . "-tiled")) {
			$this->load->helper('file');
			delete_files($outputPath, true);
		}

		$this->derivatives[$derivativeType] = $derivativeContainer;
		$this->unlinkLocalSwap();

		$this->queueTask(4);
		return JOB_SUCCESS;

	}


	public function clarifyTag($args) {


		if(strlen($this->instance->getClarifaiId())<5) {
			$this->queueTask(3);
			return JOB_SUCCESS;
		}
		$this->load->library("Clarifai");

		$clarifai = new Clarifai($this->instance->getClarifaiId(), $this->instance->getClarifaiSecret(), $this->instance->getDomain());
		if(!$clarifai->collectionExists($this->instance->getDomain())) {
			$clarifai->addCollection($this->instance->getDomain());
		}

		if(!isset($this->derivatives["screen"])) {
			return JOB_FAILED;
		}

		$targetURL= $this->derivatives["screen"]->getProtectedURLForFile();

		if(!$clarifai->addDocument($targetURL, $this->getObjectId())) {
			return JOB_POSTPONE;
		}

		$document = $clarifai->getDocument($this->getObjectId());

		$resultTags = array();
		foreach($document->document->annotation_sets[0]->annotations as $tagCluster) {

			$resultTags[] = ["tag"=>$tagCluster->tag->cname, "score"=>$tagCluster->score];

		}

		$this->globalMetadata["tags"] = $resultTags;

		$this->queueTask(3);
		return JOB_SUCCESS;

	}

	function unlinkLocalSwap() {
		if($this->sourceFile->getType() == "svs") {
			$source = $this->sourceFile->getPathToLocalFile();
			$dest = $this->sourceFile->getPathToLocalFile() . ".png";
			if(file_exists($dest)) {
				unlink($dest);
			}
		}
		return true;
	}

	function forceTiling() {
		if($this->sourceFile->getType() == "svs") {
			return TRUE;
 		}

 		return FALSE;

	}

	function swapLocalForPNG() {
		if($this->sourceFile->getType() == "svs") {
			$source = $this->sourceFile->getPathToLocalFile();
			$dest = $this->sourceFile->getPathToLocalFile() . ".png";
			if(file_exists($dest)) {
				return new FileContainer($dest);
			}
			$convertString = $this->config->item('vipsBinary') . " shrink " . $source . " " . $dest . " " . "10 10";
			exec($convertString . " 2>/dev/null");

			return new FileContainer($dest);
		}
		else {
			return $this->sourceFile;
		}
	}



}

/* End of file imageHandler.php */
/* Location: ./application/models/imageHandler.php */