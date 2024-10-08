<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH.'controllers/Transcoder.php');

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



	public function __construct()
	{
		parent::__construct();
		//Do your magic here
		$this->load->library("TranscoderCommands");
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



	public function waitForCompletion($args) {
		if(!$args['jobId']) {
			return JOB_FAILED;
		}

		$jobId = $args['jobId'];
		$transcodeCommands = new TranscoderCommands($this->pheanstalk, $this->videoTTR);


		if(is_array($jobId)) {
			foreach($jobId as $singleJobId) {
				$response = $transcodeCommands->checkCompletion($singleJobId);
				if($response == "working") {
					return JOB_POSTPONE;
				}
				if($response == "error") {
					$this->logging->processingInfo("create derivatives", "audioHandler", "Error returned from transcoder", $this->getObjectId(), $singleJobId);
					return JOB_FAILED;
				}
			}
		}
		else {
			$response = $transcodeCommands->checkCompletion($jobId);
			if($response == "working") {
				return JOB_POSTPONE;
			}
			if($response == "error") {
				$this->logging->processingInfo("extract metadata", "audioHandler", "Error returned from transcoder", $this->getObjectId(), $jobId);
				return JOB_FAILED;
			}
		}

		if($args['previousTask'] == "metadata") {
			$this->queueTask(2, []);
		}
		elseif($args['previousTask'] == "createDerivatives") {
			if(isset($args['pendingDerivatives']) && count($args['pendingDerivatives'])>0) {

				$this->queueTask(2, ["pendingDerivatives"=>$args['pendingDerivatives']], false);
			}
			else {
				$this->queueTask(4, [], false);
			}

		}
		else {
			$this->queueTask(6, []);
		}
		return JOB_SUCCESS;

	}

	public function extractWaveform($args) {

		$jobId = $this->getTranscodeCommand()->extractWaveform($this->getObjectId());

		$this->save();
		$this->queueTask(5, ["jobId"=>$jobId, "previousTask"=>"extractWaveform"]);
		return JOB_SUCCESS;

	}

	public function getTranscodeCommand() {
		if($this->config->item('fileQueueingMethod') == 'beanstalkd') {
			$transcodeCommands = new TranscoderCommands($this->pheanstalk, $this->videoTTR);
		}
		else {
			$transcodeCommands = new TranscoderCommandsAWS($this->pheanstalk, $this->videoTTR, $this);
		}
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
			$this->logging->processingInfo("createDerivative","audioHandler","Enqueuing jobs failed",$this->getObjectId(),$this->job->getId());
			return JOB_FAILED;
		}

	}




}
