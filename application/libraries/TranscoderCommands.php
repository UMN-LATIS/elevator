<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class TranscoderCommands {

	private $pheanstalk;

	public function __construct($pheanstalk = null, $videoTTR = null) {

		if($pheanstalk) {
			$this->pheanstalk = $pheanstalk;
			$this->videoTTR = $videoTTR;
		}
	}


	public function checkCompletion($jobId) {

		try {
			$job = $this->pheanstalk->statsJob($jobId);
			if($job->state == "buried") {
				return "error";
			}
			else {
				return "working";
			}
		}
		catch (Exception $e) {
			return "complete";
		}

	}


	public function extractMetadata($objectId)
	{
		$newTask = json_encode(["task"=>"extractMetadata", "fileHandlerId"=>$objectId, "config"=>[]]);
		$jobId= $this->pheanstalk->useTube('transcoder')->put($newTask, 50, 2, $this->videoTTR);
		return $jobId;
	}


	public function extractWaveform($objectId) {
		$newTask = json_encode(["task"=>"extractWaveform", "fileHandlerId"=>$objectId, "config"=>[]]);
		$jobId= $this->pheanstalk->useTube('transcoder')->put($newTask, 10, 2, $this->videoTTR);
		return $jobId;
	}

	public function createVTT($objectId) {
		$newTask = json_encode(["task"=>"createVTT", "fileHandlerId"=>$objectId, "config"=>[]]);
		$jobId= $this->pheanstalk->useTube('transcoder')->put($newTask, 10, 2, $this->videoTTR);
		return $jobId;
	}


	public function createDerivative($objectId, $type) {
		$newTask = json_encode(["task"=>"createDerivative", "fileHandlerId"=>$objectId, "config"=>["type"=>$type]]);
		$jobId= $this->pheanstalk->useTube('transcoder')->put($newTask, 10, 2, $this->videoTTR);
		return $jobId;
	}


	public function createThumbnail($objectId) {
		$newTask = json_encode(["task"=>"createThumbnail", "fileHandlerId"=>$objectId, "config"=>[]]);
		$jobId= $this->pheanstalk->useTube('transcoder')->put($newTask, 10, 2, $this->videoTTR);
		return $jobId;
	}

	public function createTiny($objectId) {
		$newTask = json_encode(["task"=>"createTiny", "fileHandlerId"=>$objectId, "config"=>[]]);
		$jobId= $this->pheanstalk->useTube('transcoder')->put($newTask, 10, 2, $this->videoTTR);
		return $jobId;
	}


	public function createSequence($objectId) {
		$newTask = json_encode(["task"=>"createSequence", "fileHandlerId"=>$objectId, "config"=>[]]);
		$jobId= $this->pheanstalk->useTube('transcoder')->put($newTask, 10, 2, $this->videoTTR);
		return $jobId;
	}

	public function cleanup($objectId) {
		$newTask = json_encode(["task"=>"cleanup", "fileHandlerId"=>$objectId, "config"=>[]]);
		$jobId= $this->pheanstalk->useTube('transcoder')->put($newTask, 10, 2, $this->videoTTR);
		return $jobId;
	}

}

/* End of file  */
