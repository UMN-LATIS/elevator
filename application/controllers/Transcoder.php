<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class is intended to be called via the web, but for the moment
 * will be called as class methods from another model.
 * Returns job ids linked against beanstalkd for the moment, but in the long run it'll use
 * its own data sources
 */
class Transcoder extends CI_Controller {
	public $qb;
	public $pheanstalk;
	public $videoTTR = 900;
	public $serverId = null;
	public $instance = null;

	function getMacLinux() {
		exec('netstat -ie', $result);
		if(is_array($result)) {
			$iface = array();
			foreach($result as $key => $line) {
				if($key > 0) {
					$tmp = str_replace(" ", "", substr($line, 0, 10));
					if($tmp <> "") {
						$macpos = strpos($line, "HWaddr");
						if($macpos !== false) {
							$iface[] = array('iface' => $tmp, 'mac' => strtolower(substr($line, $macpos+7, 17)));
						}
					}
				}
			}
			return $iface[0]['mac'];
		} else {
			return "notfound";
		}
	}


	public function __construct()
	{
		parent::__construct();
		$this->pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
		$this->serverId = $this->getMacLinux();
		$this->config->set_item("enableCaching", false);
		$this->doctrine->extendTimeout();
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


	public function transcodeTask() {

		$cnt=0;
		while(1) {
			$job = $this->pheanstalk->watch('transcoder')->ignore('default')->reserve();
			if(!is_object($job)) {
				usleep(5);
				continue;
			}
			$job_encoded = json_decode($job->getData(), true);
			$stats = $this->pheanstalk->statsJob($job);
			$jobPriority = $stats->pri;

			if($stats->reserves > 200) { // if we've tried to process it 200 times, it's probably not gonna work
				$this->logging->processingInfo("job", "transcoder", "file attempted 200 times", $job_encoded['fileHandlerId'], $job->getId());
				$this->pheanstalk->bury($job);
				continue;
			}

			$fileHandler = $this->filehandler_router->getHandlerForObject($job_encoded["fileHandlerId"]);

			if(!$fileHandler) {
				$this->logging->processingInfo("job", "transcoder", "file handle not found", $job_encoded['fileHandlerId'], $job->getId());
				echo "File not found, burying " . $job->getId() . "\n";
				$this->pheanstalk->bury($job);
				continue;
			}

			if(!$job_encoded["fileHandlerId"]) {
				$this->pheanstalk->release($job, $jobPriority, 15);
				continue;
			}
			$fileHandler->loadByObjectId($job_encoded["fileHandlerId"]);


			if($job_encoded['task'] != "extractMetadata" && !$fileHandler->sourceFile->isLocal()) {

				$stats = $this->pheanstalk->statsJob($job);
				$reserves = $stats->reserves;
				if($reserves < 30) {
					// we're not the machine that launched this job, let's let someone else try for a while before we do
					$this->pheanstalk->release($job, $jobPriority, 1);
					continue;
				}
			}


			echo "Processing " . $fileHandler->sourceFile->originalFilename . " : " . $job_encoded['task'] . "\n";

			$success=false;
			$this->load->model("transcoder_model");
			$transcoder = new Transcoder_model;
			$result = $transcoder->performTask($fileHandler, $job);

			if($result == JOB_SUCCESS) {
				if($fileHandler->save() != false) {
					$this->pheanstalk->delete($job);
				}
				else {
					echo "Failed updating " . $fileHandler->getObjectId() . "\n";
					$this->pheanstalk->bury($job);
				}
			}
			else if($result == JOB_FAILED) {
				echo "Job Failed: " . $job->getId() . " " .  $fileHandler->sourceFile->originalFilename . " : " . $job_encoded['task'] . "\n";
				$this->pheanstalk->bury($job);
			}
			else if($result == JOB_POSTPONE) {
				echo "Postponing " . $fileHandler->sourceFile->originalFilename . " : " . $job_encoded['task'] . "\n";
				$this->pheanstalk->release($job, $jobPriority, 15);
			}

			echo "Job Complete\n";

			$cnt++;

			$memory = memory_get_usage();

			if($memory > 100000000) {
				echo "exiting run due to memory limit";
				exit;
			}
			$this->doctrine->em->clear();
			sleep(1);
		}
	}


}

/* End of file transcoder.php */
/* Location: ./application/controllers/transcoder.php */
