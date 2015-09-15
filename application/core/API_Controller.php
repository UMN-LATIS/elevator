<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class API_Controller extends MY_Controller {

	public $isAuthenticated;
	public $instance = null;
	public $apiKey;

	public function __construct()
	{
		parent::__construct();

		// DOES THIS DO ANYTHING??
		// if($this->input->get("authKey")) {

		// 	$authKey = $this->input->get("w");
		// 	$authTimestamp = $this->input->get("timestamp");
		// 	$authHash = $this->input->get("hash");
		// 	$authUser = null;
		// }
		// else
		if(isset($_SERVER['HTTP_AUTHORIZATION_KEY'])) {

			$authKey = $_SERVER['HTTP_AUTHORIZATION_KEY'];
			$authTimestamp = $_SERVER['HTTP_AUTHORIZATION_TIMESTAMP'];
			$authHash = $_SERVER['HTTP_AUTHORIZATION_HASH'];
			if(isset($_SERVER['HTTP_AUTHORIZATION_USER'])) {
				$authUser = $_SERVER['HTTP_AUTHORIZATION_USER'];
			}
			else {
				$authUser = false;
			}

		}
		else {
			header('HTTP/1.0 401 Unauthorized');
			exit;
		}

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



		$this->apiKey = $apiKey;

		if($authUser) {
			$this->doctrineCache->setNamespace('userCache_');
			if($storedObject =  $this->doctrineCache->fetch($authUser)) {
				$user_model = unserialize($storedObject);
				if(!$user_model) {
					$this->user_model->loadUser($authUser);
				}
				else {
					$this->user_model = $user_model;
				}
			}
			else {
				$this->logging->logError("cache fail" . $authUser);
				$this->user_model->loadUser($authUser); // we'll give it a try, but we may not have perms if we rely on external auth stuffs.
				if($this->user_model->getUserType() == "Remote") { // bail
					$this->user_model->userLoaded = false;
				}
			}


			if($this->user_model && $this->user_model->userLoaded) {
				$this->isAuthenticated = true;

				// extend cache (if we move to a sane caching library we can remove this)
				$this->doctrineCache->save($this->user_model->getId(), serialize($this->user_model), 900);


				if(!$this->user_model->getApiInstance()) {
					// TODO
					// force instance set
				}
				else {
					$this->instance = $this->user_model->getApiInstance();
				}
			}
		}

	}

	function var_error_log( $object=null ){
		ob_start();                    // start buffer capture
		var_dump( $object );           // dump the values
		$contents = ob_get_contents(); // put the buffer into a variable
		ob_end_clean();                // end capture
		error_log( $contents );        // log contents of the result of var_dump( $object )
	}

}

/* End of file  */
/* Location: ./application/controllers/ */