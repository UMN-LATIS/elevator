<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class LoginManager extends Instance_Controller {
	public $noAuth = true;

	public function __construct()
	{
		parent::__construct();

	}

	function force_ssl() {
	    // if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") {
	    //     $url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	    //     redirect($url);
	    //     exit;
	    // }
	}

	public function localLogin() {
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

				$this->session->set_userdata( $array );
				redirect($redirectURL);
				return;
			}
			else {
				$this->template->content->view("login/passwordFail");
			}
		}
		$this->force_ssl();

		$this->template->content->view("login/login", ["redirectURL"=>$redirectURL]);
		$this->template->publish();
	}

	public function logout()
	{
		$this->session->sess_destroy();
		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('userCache_');
			$this->doctrineCache->delete($this->user_model->userId);
		}


		// Logout of the shib session, can be used to log out from one account
		// and log into another.
		$umnshib = new \UMNShib\Basic\BasicAuthenticator(["idpEntity"=>$this->config->item("shibbolethLogin")], ["logoutEntity"=>$this->config->item("shibbolethLogout")]);
		if ($umnshib->hasSession() && $this->config->item("shibbolethLogout")) {
		  $umnshib->redirectToLogout();
		}

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
		}
		$this->force_ssl();

		// Example Object-Oriented instantiation and redirect to login:
		$umnshib = new \UMNShib\Basic\BasicAuthenticator(["idpEntity"=>$this->config->item("shibbolethLogin")], ["logoutEntity"=>$this->config->item("shibbolethLogout")]);
		if (!$umnshib->hasSession()) {
			if($noForcedAuth == "true") {
				if($redirectURL) {
					redirect($redirectURL);
				}
				else {
					instance_redirect("/");
				}
				return;
			}
		  	$umnshib->redirectToLogin();

		}

		$this->load->library($this->config->item("authHelper"));
		$authHelperName = $this->config->item("authHelper");
		$authHelper = new $authHelperName;
		
		$user = $this->doctrine->em->getRepository("Entity\User")->findOneBy(["userType"=>"Remote","username"=>$authHelper->getUserIdFromRemote($umnshib)]);
		if(!$user) {
			$user = $authHelper->createUserFromRemote($umnshib);
		}
		else {
			$authHelper->updateUserFromRemote($umnshib, $user);
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
/* Location: ./application/controllers/loginManager */
