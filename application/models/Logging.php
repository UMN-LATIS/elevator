<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Logging extends CI_Model {



	public function __construct()
	{
		parent::__construct();

	}


	public function processingInfo($task, $type, $message, $asset, $jobId) {
		if(is_array($message) || is_object($message)) {
			$message = json_encode($message);
		}
		$log = new Entity\JobLog();
		$log->setMessage($message);
		$log->setCreatedAt(new \DateTime("now"));
		$log->setTask($task);
		$log->setType($type);
		$log->setAsset($asset);
		$log->setJobId($jobId);
		$this->doctrine->em->persist($log);
		$this->doctrine->em->flush();
	}

	public function logError($task=null,$message=null, $asset=null, $collection=null)
	{
		\Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($message, $task): void {
  			$scope->setExtra('error.message', $message);
  			$scope->setExtra('error.task', $task);
			$scope->setExtra('error.type', "manual");
		});
		
		if(is_a($message, "Exception")) {
			\Sentry\captureException($message);
		}
		else {
			// \Sentry\captureMessage($task);
		}
		
		if(is_array($message) || is_object($message)) {
			$message = json_encode($message);
		}

		$log = new Entity\Log();
		$log->setMessage($message);
		$log->setCreatedAt(new \DateTime("now"));
		$log->setTask($task);
		$log->setAsset($asset);
		if(isset($this->instance) && is_object($this->instance)) {
			$log->setInstance($this->instance);
		}
		if(isset($this->user_model->user)) {
			$log->setUser($this->user_model->user);
		}
		$log->setCollection($collection);
		$this->doctrine->em->persist($log);
		$this->doctrine->em->flush();
	}

}

/* End of file  */
/* Location: ./application/models/ */