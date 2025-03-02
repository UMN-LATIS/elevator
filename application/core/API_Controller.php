<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class API_Controller extends MY_Controller {

	public $isAuthenticated;
	public $instance = null;
	public $apiKey;

	public function __construct()
	{

		parent::__construct();

		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			header('Access-Control-Allow-Credentials: true');    
			header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); 
		}   
		header("Access-Control-Allow-Headers: authorization-key, authorization-hash, authorization-timestamp, authorization-user");
		// Access-Control headers are received during OPTIONS requests
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

			echo "true";
			die;
		}
		if(isset($_SERVER['HTTP_AUTHORIZATION_KEY'])) {

			$authKey = $_SERVER['HTTP_AUTHORIZATION_KEY'];
			$authTimestamp = $_SERVER['HTTP_AUTHORIZATION_TIMESTAMP'];
			$authHash = $_SERVER['HTTP_AUTHORIZATION_HASH'];
			if(isset($_SERVER['HTTP_AUTHORIZATION_USER'])) {
				$authUser = $_SERVER['HTTP_AUTHORIZATION_USER'];
			}
			else {
				$apiKey = $this->doctrine->em->getRepository("Entity\ApiKey")->findOneBy(["apiKey"=>$authKey]);
				if($apiKey) {
					$authUser = $apiKey->getOwner()->getId();
				}
				else {
					$authUser = false;
				}
				
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
			if($this->config->item('enableCaching')) {
				if($storedObject = $this->userCache->get($authUser)) {
					$user_model = $storedObject;
					if(!$user_model) {
						$this->user_model->loadUser($authUser);
					}
					else {
						$this->user_model = $user_model;
					}
				}
				else {
					// $this->logging->logError("cache fail" . $authUser);
					$this->user_model->loadUser($authUser); // we'll give it a try, but we may not have perms if we rely on external auth stuffs.
					if($this->user_model->getUserType() == "Remote") { // bail
						$this->user_model->userLoaded = false;
					}
				}
			}
			else {
				$this->user_model->loadUser($authUser); // we'll give it a try, but we may not have perms if we rely on external auth stuffs.
				if($this->user_model->getUserType() == "Remote") { // bail
					$this->user_model->userLoaded = false;
				}
			}


			if($this->user_model && $this->user_model->userLoaded) {
				$this->isAuthenticated = true;

				// extend cache (if we move to a sane caching library we can remove this)
				if($this->config->item('enableCaching')) {
					$this->userCache->set($this->user_model->getId(), $this->user_model, 900);
				}


				if(!$this->user_model->getApiInstance()) {
					// TODO
					// force instance set
				}
				else {
					$this->instance = $this->user_model->getApiInstance();
				}
			}
		}

		if(!$this->instance) {
			require_once("Instance_Controller.php");
			Instance_Controller::setInstance();
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