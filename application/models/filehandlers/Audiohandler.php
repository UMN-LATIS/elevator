<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH.'controllers/transcoder.php');

class AudioHandler extends FileHandlerBase {

	protected $supportedTypes = array("mp3","aiff", "aif", "m4a", "wav", "wave");
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



	public function waitForCompletion($args) {
		if(!$args['jobId']) {
			return JOB_FAILED;
		}

		$jobId = $args['jobId'];

		if(is_array($jobId)) {
			foreach($jobId as $singleJobId) {
				$response = Transcoder::checkCompletion($singleJobId);
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
			$response = Transcoder::checkCompletion($jobId);
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
			$this->queueTask(4, []);
		}
		else {
			$this->queueTask(6, []);
		}
		return JOB_SUCCESS;

	}

	public function extractWaveform($args) {

		$jobId = Transcoder::extractWaveform($this->getObjectId());

		$this->save();
		$this->queueTask(5, ["jobId"=>$jobId, "previousTask"=>"extractWaveform"]);
		return JOB_SUCCESS;

	}

	public function extractMetadata($args) {

		$jobId = Transcoder::extractMetadata($this->getObjectId());

		$this->save();
		$this->queueTask(1, ["jobId"=>$jobId, "previousTask"=>"metadata"]);
		return JOB_SUCCESS;

	}


	public function createDerivatives($args) {

		$jobIdArray = array();

		$jobIdArray[] = Transcoder::createDerivative($this->getObjectId(), "mp3");

		if(count($jobIdArray)>0) {
			$this->queueTask(3, ["jobId"=>$jobIdArray, "previousTask"=>"createDerivatives"]);
			return JOB_SUCCESS;
		}
		else {
			$this->logging->processingInfo("createDerivative","audioHandler","Enqueuing jobs failed",$this->getObjectId(),$this->job->getId());
			return JOB_FAILED;
		}

	}




}
