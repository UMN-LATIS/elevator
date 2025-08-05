<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class AudioHandler extends FileHandlerBase {

	protected $supportedTypes = array("mp3","aiff", "aif", "m4a", "wav", "wave", "wma");
	protected $noDerivatives = false;
	public $videoTTR = 900;
	public $icon = "mp3.png";

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>array()],
	 					 1=>["taskType"=>"waitForCompletion", "config"=>array()],
						  2=>["taskType"=>"createDerivatives", "config"=>array()],
						  3=>["taskType"=>"waitForCompletion", "config"=>array()],
						  4=>["taskType"=>"extractWaveform", "config"=>array()],
						  5=>["taskType"=>"waitForCompletion", "config"=>array()],
						  6=>["taskType"=>"cleanupOriginal", "config"=>array()],
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
			return array();

		}
		else {
			return parent::highestQualityDerivativeForAccessLevel($accessLevel, $stillOnly);
		}
	}

	public function allDerivativesForAccessLevel($accessLevel) {
		$derivative=array();

		if($accessLevel>=$this->getPermission()) {
			$derivative[] = "mp3";
			$derivative[] = "m4a";
		}
		if($accessLevel>PERM_NOPERM) {
			$derivative[] = "thumbnail";
			$derivative[] = "thumbnail2x";
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


	public function getPreviewThumbnail($retina=false) {

		if($targetFileObject = $this->getAltWidget()) {
			return $targetFileObject->getPreviewThumbnail($retina);
		}

		return parent::getPreviewThumbnail($retina);
	}

	public function getPreviewTiny($retina=false) {
		
		if($targetFileObject = $this->getAltWidget()) {
			return $targetFileObject->getPreviewTiny($retina);
		}

		return parent::getPreviewTiny($retina);

	}

	public function getAltWidget() {
		$widgetContents = $this->getUploadWidget();
		$widget = $widgetContents->parentWidget;
		if(isset($widget->thumbnailTarget)) {
			$targetField = $widget->thumbnailTarget;
			if(!isset($this->parentObject->assetObjects[$targetField])) {
				return false;
			}
			$targetWidget = $this->parentObject->assetObjects[$targetField];
			return $targetWidget->fieldContentsArray[0]->getFileHandler();
		}
		else {
			return false;
		}

	}


	public function extractWaveform($args) {

		$jobId = $this->getTranscodeCommand()->extractWaveform($this->getObjectId());

		$this->save();
		$this->queueTask(5, ["jobId"=>$jobId, "previousTask"=>"extractWaveform"]);
		return JOB_SUCCESS;

	}

	public function getTranscodeCommand() {
		$transcodeCommands = new TranscoderCommandsAWS($this);
		
		return $transcodeCommands;
	}

	public function extractMetadata($args) {

		$jobId = $this->getTranscodeCommand()->extractMetadata($this->getObjectId());

		$this->save();
		$this->queueTask(1, ["jobId"=>$jobId, "previousTask"=>"metadata"]);
		return JOB_SUCCESS;

	}


	public function createDerivatives($args) {
		if(isset($args['pendingDerivatives'])) {
			$targetDerivatives = $args['pendingDerivatives'];
		}
		else {
			$targetDerivatives = ["mp3", "m4a"];
		}

		$nextDerivative = array_shift($targetDerivatives);

		$jobId = null;
		if($nextDerivative) {
			$jobId = $this->getTranscodeCommand()->createDerivative($this->getObjectId(), $nextDerivative);	
		}
	

		if($jobId) {
			$this->queueTask(3, ["jobId"=>$jobId, "pendingDerivatives"=>$targetDerivatives, "previousTask"=>"createDerivatives"]);
			return JOB_SUCCESS;
		}
		else {
			$this->logging->processingInfo("createDerivative","audioHandler","Enqueuing jobs failed",$this->getObjectId(),0);
			return JOB_FAILED;
		}

	}

	public function generateCaptions() {

		$derivative = $this->derivatives["mp3"];
		$derivative->makeLocal();
		$localPath = $derivative->getPathToLocalFile();
		$localPathParts = pathinfo($localPath);


		chmod($localPathParts['dirname'] , 0777);
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
				$uploadWidget = $this->getUploadWidget(true);
				$uploadWidget->sidecars['captions'] = $srtContents;
				$this->parentObject->save(true,false);
			}
		}
		else {
			echo "No captions found for " . $this->getObjectId() . "\n";
			// dump the contents of /tmp/whisperx.log
			if(file_exists("/tmp/whisperx.log")) {
				$logContents = file_get_contents("/tmp/whisperx.log");
				echo "WhisperX log contents:\n" . $logContents . "\n";
			}
			// list all the files in the directory
			$files = scandir($localPathParts['dirname']);
			echo "Files in " . $localPathParts['dirname'] . ":\n";
			foreach($files as $file) {
				if($file != "." && $file != "..") {
					echo " - " . $file . "\n";
				}
			}
		}
		

	}




}
