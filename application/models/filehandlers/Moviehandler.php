<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH.'controllers/Transcoder.php');

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


	public function __construct()
	{
		parent::__construct();
		//Do your magic here
		$this->load->library("TranscoderCommands");
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

		$this->postponeTime = 60;

		$transcodeCommands = new TranscoderCommands($this->pheanstalk, $this->videoTTR);
		$response = $transcodeCommands->checkCompletion($jobId);
		if($response == "working") {
			return JOB_POSTPONE;
		}
		if($response == "error") {
			$this->logging->processingInfo("job", "movieHandler", "Error returned from transcoder", $this->getObjectId(), $jobId);
			return JOB_FAILED;
		}

		if($args['previousTask'] == "metadata") {
			$this->queueTask(2, [], false);
		}
		elseif($args['previousTask'] == "completeDerivatives") {
			return JOB_SUCCESS;
		}
		elseif($args['previousTask'] == "createDerivatives") {
			if(isset($args['pendingDerivatives']) && count($args['pendingDerivatives'])>0) {

				$this->queueTask(2, ["pendingDerivatives"=>$args['pendingDerivatives']], false);
			}
			else {
				$this->queueTask(4, [], false);
			}

		}

		return JOB_SUCCESS;

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


		$transcodeCommands = new TranscoderCommands($this->pheanstalk, $this->videoTTR);
		$jobId = $transcodeCommands->extractMetadata($this->getObjectId());

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

			if($$this->instance && $this->instance->getEnableHLSStreaming()) {
				$targetDerivatives[] = "hls";
			}
			
		}
		$nextDerivative = array_shift($targetDerivatives);

		$jobId = null;
		$transcodeCommands = new TranscoderCommands($this->pheanstalk, $this->videoTTR);
		switch($nextDerivative) {
			case "thumbnail":
				$jobId = $transcodeCommands->createThumbnail($this->getObjectId());
				break;
			case "tiny":
				$jobId = $transcodeCommands->createTiny($this->getObjectId());
				break;
			case "vtt":
				$jobId = $transcodeCommands->createVTT($this->getObjectId());
				break;
			case "sequence":
				$jobId = $transcodeCommands->createSequence($this->getObjectId());
				break;
			case "sd":
				$jobId = $transcodeCommands->createDerivative($this->getObjectId(), "SD");
				break;
			case "hls":
				$jobId = $transcodeCommands->createDerivative($this->getObjectId(), "HLS");
				break;
			case "hd":
				$jobId = $transcodeCommands->createDerivative($this->getObjectId(), "HD");
				break;
			case "hd1080":
				$jobId = $transcodeCommands->createDerivative($this->getObjectId(), "HD1080");
				break;
		}

		if($jobId) {
			$this->queueTask(3, ["jobId"=>$jobId, "pendingDerivatives"=>$targetDerivatives, "previousTask"=>"createDerivatives"], false);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	public function cleanupOriginal($args) {

		$transcodeCommands = new TranscoderCommands($this->pheanstalk, $this->videoTTR);
		$jobId = $transcodeCommands->cleanup($this->getObjectId());

		if($jobId) {
			$this->queueTask(5, ["jobId"=>$jobId, "previousTask"=>"completeDerivatives"], false);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}
	}





}
