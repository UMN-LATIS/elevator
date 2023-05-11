<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class LoginManager extends Instance_Controller {
	public $noAuth = true;

	public function __construct()
	{
		parent::__construct();
		$this->template->loadJavascript(["bootstrap-show-password"]);

	}

	function handleLocalLoginForVueUI() {
		$username = $this->input->post("username");
		$password = $this->input->post("password");

		// check if username and password are set
		if (!$username || !$password) {
			return render_json([
				'status' => 'error',
				'message' => 'invalid username or password'
			], 401);
		}

		$user = getUserBy(["username" => $username, "userType" => "Local"]);

		// check if user exists and password is correct
		if (!$user || !verifyUserPassword($user, $password)) {
			return render_json([
				'status' => 'error',
				'message' => 'invalid username or password'
			], 401);
		}

		// check if account is expired
		if (hasUserAccountExpired($user)) {
			return render_json([
				'status' => 'error',
				'message' => 'account is expired'
			]);
		}

		// success!
		// set session data
		$this->session->set_userdata([
			'userId' => $user->getId()
		]);
		return render_json([
			'status' => 'success',
			'message' => 'login successful'
		]);
	}

	function handleInvalidRequestMethod() {
		header('Content-Type: application/json');
		http_response_code(405);
		echo json_encode([
			'status' => 'error',
			'message' => 'invalid request method'
		]);
	}

	public function localLogin() {
		$requestMethod = $this->input->server('REQUEST_METHOD');
		
		if (isUsingVueUI()) {
			if ($requestMethod == 'GET') {
				return $this->template->publish('vueTemplate');
			}
			if ($requestMethod == 'POST') {
				return $this->handleLocalLoginForVueUI();
			}
			return $this->handleInvalidRequestMethod();
		}

		$this->useUnauthenticatedTemplate = true;

		$redirectURL = null;
		if(isset($_GET['redirect'])) {
			$redirectURL = $_GET['redirect'];
			if(stristr($redirectURL, "errorHandler")) {
				$redirectURL = "/";
			}
		}

		if($this->input->post("username")) {

			$redirectURL = $this->input->post("redirectURL");

			$user = $this->doctrine->em->getRepository("Entity\User")->findOneBy(["username"=>$this->input->post("username"), "userType"=>"Local"]);

			$hashedPass = sha1($this->config->item('encryption_key').$this->input->post("password"));
			$secondHashPass = sha1("monkeybox43049pokdhjaldsjkaf".$this->input->post("password"));
			if($user && $user->getHasExpiry() && $user->getExpires() < new DateTime()) {
				$this->template->content->view("login/expiredAccount");
			}
			else if($user != null && ($hashedPass == $user->getPassword() || $secondHashPass == $user->getPassword())) {
				$array = array(
					'userId' => $user->getId()
				);
				$this->session->set_userdata($array);
				redirect($redirectURL);
				return;
			}
			else {
				$this->template->content->view("login/passwordFail");
			}
		}

		$this->template->content->view("login/login", ["redirectURL"=>$redirectURL, "localOnly"=>true]);
		$this->template->publish();
	}

	public function logout()
	{
		$this->session->sess_destroy();
		if($this->config->item('enableCaching') && isset($this->user_model->userId)) {
			$this->doctrineCache->setNamespace('userCache_');
			$this->doctrineCache->delete($this->user_model->userId);
		}
		$this->input->set_cookie(["name"=>"ApiHandoff", "expire"=>""]);
		$this->input->set_cookie(["name"=>"AuthKey", "expire"=>""]);
		$this->input->set_cookie(["name"=>"Timestamp", "expire"=>""]);
		$this->input->set_cookie(["name"=>"TargetObject", "expire"=>""]);

		// Logout of the shib session, can be used to log out from one account
		// and log into another.
		$authHelper = $this->user_model->getAuthHelper();
		$authHelper->remoteLogout();

		instance_redirect("");
	}

	public function remoteLogin($noForcedAuth=false) {
		$this->useUnauthenticatedTemplate = true;

		$redirectURL = null;
		if(isset($_GET['redirect'])) {
			$redirectURL = $_GET['redirect'];
			if(stristr($redirectURL, "errorHandler")) {
				$redirectURL = "/";
			}
			// we hackily urlencode hashes
			$redirectURL = str_replace("%23", "#", $redirectURL);
		}

		$authHelper = $this->user_model->getAuthHelper();
		if($authHelper->remoteLogin($redirectURL, $noForcedAuth)) {
			return;
		}

		$user = $this->doctrine->em->getRepository("Entity\User")->findOneBy(["userType"=>"Remote","username"=>$authHelper->getUserIdFromRemote()]);
		if(!$user) {
			$user = $authHelper->createUserFromRemote();
		}
		else {
			$authHelper->updateUserFromRemote($user);
		}

		if($authHelper->getDestination()) {
			$redirectURL = $authHelper->getDestination();
		}

		if($user) {
			$array = array(
				'userId' => $user->getId()
			);
			$this->session->set_userdata( $array );
			if($redirectURL) {
				redirect($redirectURL);
			}
			else {
				instance_redirect("/");
			}

			return;
		}



		$this->template->content->view("login/login", ["redirectURL"=>$redirectURL]);
		$this->template->publish();

	}

	public function remoteReturn() {

	}
}

/* End of file loginManager */