<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class MovieHandler extends FileHandlerBase {

	protected $supportedTypes = array("mov","mp4", "m4v", "mts", "mkv", "avi", "mpeg", "mpg", "m2t", "m2ts", "dv", "vob", "mxf","wmv");
	protected $noDerivatives = false;
	public $videoTTR = 3600;

	public $postponeTime = 15;

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>array()],
	 					 1=>["taskType"=>"waitForCompletion", "config"=>array()],
						  2=>["taskType"=>"createDerivatives", "config"=>array()],
						  3=>["taskType"=>"waitForCompletion", "config"=>array()],
						  4=>["taskType"=>"cleanupOriginal", "config"=>array()],
						  5=>["taskType"=>"waitForCompletion", "config"=>array()],
						];

	public $gpuTaskArray = [0=>["taskType"=>"generateCaptions", "config"=>array()]];


	public function __construct()
	{
		parent::__construct();
		//Do your magic here
		$this->load->library("TranscoderCommandsAWS");
	}


	public function highestQualityDerivativeForAccessLevel($accessLevel, $stillOnly=false) {
		if($stillOnly) {
			try {
				$allItems = $this->allDerivativesForAccessLevel($accessLevel);
			}
			catch (Exception $e) {
				throw new Exception("Derivative not found");

			}
			if(isset($allItems["imageSequence"])) {
				return $allItems["imageSequence"];
			}
			else {
				return $allItems["thumbnail"];
			}

		}
		else {
			return parent::highestQualityDerivativeForAccessLevel($accessLevel, $stillOnly);
		}
	}

	public function allDerivativesForAccessLevel($accessLevel) {
		$derivative=array();

		if($accessLevel>=$this->getPermission()) {
			$derivative[] = "mp4hd1080";
			$derivative[] = "mp4hd";
			$derivative[] = "mp4sd";
			$derivative[] = "stream";
			$derivative[] = "imageSequence";
			$derivative[] = "thumbnail";
			$derivative[] = "tiny";
			$derivative[] = "vtt";
		}
		if($accessLevel>PERM_NOPERM) {
			$derivative[] = "thumbnail";
		}

		$returnArray = array();
		foreach($derivative as $entry) {
			if(isset($this->derivatives[$entry])) {
				$returnArray[$entry] = $this->derivatives[$entry];
				$returnArray[$entry]->downloadable = true;
				if(in_array($entry, ['imageSequence', 'stream'])) {
					$returnArray[$entry]->downloadable = false;
				}
			}
		}
		if(count($returnArray)>0) {
			return $returnArray;
		}
		else {
			throw new Exception("Derivative not found");
		}

	}


	public function getTranscodeCommand() {
		$transcodeCommands = new TranscoderCommandsAWS($this);
		return $transcodeCommands;
	}

	/**
	 * even though we don't do any processing on the beltdrive side, we want to make sure the file is out of glacier
	 * before we hand it off to the transcoder
	 */
	public function extractMetadata($args) {

		if($this->sourceFile->isArchived()) {
			$this->sourceFile->restoreFromArchive();
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}

		
		$jobId = $this->getTranscodeCommand()->extractMetadata($this->getObjectId());

		$this->save();
		$this->queueTask(1, ["jobId"=>$jobId, "previousTask"=>"metadata"], false);
		return JOB_SUCCESS;

	}


	public function createDerivatives($args) {

		$width = $this->sourceFile->metadata["width"];
		$height = $this->sourceFile->metadata["height"];

		if(isset($args['pendingDerivatives'])) {
			$targetDerivatives = $args['pendingDerivatives'];
		}
		else {
			//$targetDerivatives = ["thumbnail", "tiny","sd","vtt","sequence","hls"];
			$targetDerivatives = ["thumbnail", "tiny","sd","vtt","sequence"]; // disabling HLS for now.
			if($width >= 1280) {
				$targetDerivatives[] = "hd";
			}
			if(isset($this->sourceFile->metadata["spherical"])) {
				$targetDerivatives[] = "hd1080";
			}

			if($this->instance && $this->instance->getEnableHLSStreaming()) {
				$targetDerivatives[] = "hls";
			}
			
		}

		if(isset($args["runInLoop"]) && $args["runInLoop"] == true) {
			$derivativeLoop = $targetDerivatives;
		}
		else {
			$derivativeLoop = [array_shift($targetDerivatives)];
		}

		$jobId = null;
		
		foreach($derivativeLoop as $nextDerivative) {
			echo "Starting derivative for " . $nextDerivative . "\n";
			switch($nextDerivative) {
				case "thumbnail":
					$jobId = $this->getTranscodeCommand()->createThumbnail($this->getObjectId());
					break;
				case "tiny":
					$jobId = $this->getTranscodeCommand()->createTiny($this->getObjectId());
					break;
				case "vtt":
					$jobId = $this->getTranscodeCommand()->createVTT($this->getObjectId());
					break;
				case "sequence":
					$jobId = $this->getTranscodeCommand()->createSequence($this->getObjectId());
					break;
				case "sd":
					$jobId = $this->getTranscodeCommand()->createDerivative($this->getObjectId(), "SD");
					break;
				case "hls":
					$jobId = $this->getTranscodeCommand()->createDerivative($this->getObjectId(), "HLS");
					break;
				case "hd":
					$jobId = $this->getTranscodeCommand()->createDerivative($this->getObjectId(), "HD");
					break;
				case "hd1080":
					$jobId = $this->getTranscodeCommand()->createDerivative($this->getObjectId(), "HD1080");
					break;
			}
		}

		if($this->instance) {
		//&& $this->instance->enableAutomaticAccessibility) {
			echo "Generating captions for " . $this->getObjectId() . "\n";
			$uploadWidget = $this->getUploadWidget();
			if($uploadWidget && isset($uploadWidget->sidecars['captions']) && $uploadWidget->sidecars['captions'] != "") {
				return;
			}
			
			$this->queueBatchItem("gpu");

		}



		if($jobId) {
			$this->queueTask(3, ["jobId"=>$jobId, "pendingDerivatives"=>$targetDerivatives, "previousTask"=>"createDerivatives"], false);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}


	public function generateCaptions() {

		$derivative = $this->derivatives["mp4sd"];
		$derivative->makeLocal();
		$localPath = $derivative->getPathToLocalFile();
		$localPathParts = pathinfo($localPath);

		$captionString = $this->config->item('whipserX') . " --model large-v3 --align_model WAV2VEC2_ASR_LARGE_LV60K_960H --batch_size 4 --output_format srt --output_dir=" . $localPathParts['dirname'] . " " . $localPath;

		$process = new Cocur\BackgroundProcess\BackgroundProcess($captionString);
		$process->run("/tmp/whisperx.log");
		while($process->isRunning()) {
			sleep(5);
			echo ".";
		}

		
		$localPathWithoutExtension = $localPathParts['dirname'] . '/' . $localPathParts['filename'];

		if(file_exists($localPathWithoutExtension . ".srt")) {
			echo "Captions found for " . $this->getObjectId() . "\n";
			$srtContents = file_get_contents( $localPathWithoutExtension . ".srt");
			if($srtContents && $srtContents != "") {
				$uploadWidget = $this->getUploadWidget();
				$uploadWidget->sidecars['captions'] = $srtContents;
				$this->parentObject->save(true,false);
			}
		}
		else {
			echo "No captions found for " . $this->getObjectId() . "\n";
		}
		

	}


	public function cleanupOriginal($args) {

		
		$jobId = $this->getTranscodeCommand()->cleanup($this->getObjectId());

		if($jobId) {
			$this->queueTask(5, ["jobId"=>$jobId, "previousTask"=>"completeDerivatives"], false);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}
	}





}
