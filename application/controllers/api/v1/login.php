<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class login extends Instance_Controller {

	var $instance;

	public function __construct()
	{
		parent::__construct();
	}

	public function loginAndRedirect($authKey, $authTimestamp, $authHash)
	{
		if(abs(time() - $authTimestamp) > 1000) {
			header('HTTP/1.0 401 Unauthorized');
			exit;
		}

		$apiKey = $this->doctrine->em->getRepository("Entity\ApiKey")->findOneBy(["apiKey"=>$authKey]);
		if(!$apiKey) {
			header('HTTP/1.0 401 Unauthorized');
			exit;
		}
		$secret = $apiKey->getApiSecret();
		if(sha1($authTimestamp . $secret) != $authHash) {
			header('HTTP/1.0 401 Unauthorized');
			exit;
		}

		if($this->user_model->userLoaded) {
			if(!$this->user_model->getApiInstance()) {
				if(isset($_GET['callback'])) {
					$callback = $_GET['callback'];
				}
				elseif($this->session->userdata['callback']) {
					$callback = $this->session->userdata('callback');
				}
				$this->session->set_userdata("callback", $callback);
				$this->errorhandler_helper->callError("setAPIInstance");
			}
			else {
				$callback = null;
				if(isset($_GET['callback'])) {
					$callback = $_GET['callback'];
				}
				elseif($this->session->userdata['callback']) {
					$callback = $this->session->userdata('callback');
				}
				$callback = $callback . "&userId=" . $this->user_model->getId() . "&instanceId=" . $this->user_model->getApiInstance()->getId();

				header("Location: " . $callback);
			}

		}
		else {
			$this->session->set_userdata("callback", $_GET['callback']);
			$this->errorhandler_helper->callError("noPermission");
		}

	}

	public function editUser($userId) {
		instance_redirect("permissions/editUser/" . $userId);
	}

}

/* End of file  */
/* Location: ./application/controllers/ */