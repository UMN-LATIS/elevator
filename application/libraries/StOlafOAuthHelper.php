<?php
/**
* St. Olaf OAuth Helper
*/

require_once("AuthHelper.php");
class StOlafOAuthHelper extends AuthHelper
{

	private $userId;
	private $name;
	private $email;
	public function __construct()
	{
		parent::__construct();
	}


	public function remoteLogin($redirectURL, $noForcedAuth=false) {
		$client = new Google_Client();
		$client->setApplicationName($this->CI->config->item("oAuthApplication"));
		$client->setClientId($this->CI->config->item("oAuthClient"));
		$client->setClientSecret($this->CI->config->item("oAuthSecret"));
		if($noForcedAuth == "true") {
			$client->setRedirectUri(site_url("/loginManager/remoteLogin/true"));
		}
		else {
			$client->setRedirectUri(site_url("/loginManager/remoteLogin"));	
			$client->setPrompt("select_account");
		}
		
		$client->setState($redirectURL);
		$client->setScopes(['email', 'profile']);
		$authURL = $client->createAuthUrl();

		if($this->CI->input->get("code")) {
			$client->authenticate($this->CI->input->get("code"));
			if($token = $client->getAccessToken()) {
				$tokenVerify = $client->verifyIdToken();
				if(!$tokenVerify) {
					$this->CI->errorhandler_helper->callError("tokenError");
					return false;
				}
				$this->email = $tokenVerify["email"];
				$this->userId = str_replace("@" . $tokenVerify["hd"], "", $this->email);
				$this->name = $tokenVerify["name"];
				$this->CI->input->set_cookie(["name"=>"LoginHint", "value"=>$this->email, "expire" => 60*60*24*365]);				// die;
			}
			else {
				$this->CI->errorhandler_helper->callError("tokenError");
				return false;
			}

			return false;
		}
		else {

			if($noForcedAuth == "true") {
				if($redirectURL) {
					redirect($redirectURL);
				}
				elseif($this->getDestination()) {
					redirect($this->getDestination());
				}
				else {
					instance_redirect("/");
				}
				return true;
			}
			redirect($authURL);
			return false;
		}
		return true;
	}

	public function getDestination() {
		if($this->CI->input->get("state")) {
			return $this->CI->input->get("state");
		}
		return null;

	}
	public function remoteLogout() {
	
	}

	public function createUserFromRemote($usernameOverride = null) {
		$user = new Entity\User;	
		
		if(!$usernameOverride) {
			$username = $this->getUserIdFromRemote($shibHelper);
			$user->setDisplayName($this->name);
			$user->setEmail($this->email);
		}
		else {
			$username = $usernameOverride;
		}

		$user->setUsername($username);
		$user->setHasExpiry(false);
		$user->setCreatedAt(new \DateTime("now"));
		$user->setUserType("Remote");
		$user->setInstance($CI->instance);
		$user->setIsSuperAdmin(false);
		$user->setFastUpload(false);
		$this->CI->doctrine->em->persist($user);
		$this->CI->doctrine->em->flush();
		return $user;
	}

	public function getUserIdFromRemote($shibHelper) {
		return $this->userId;
	}

	public function updateUserFromRemote($shibHelper, $user) {
		

	}

	public function findById($key, $createMissing=false) {
		if($createMissing) {
			$user = new Entity\User;	
			$user->setUsername($key);
			return [$user];
		}

		return array();
	}

	public function findUserByUsername($key, $createMissing=false) {
		return array();
	}

	public function findUserByName($key, $createMissing = false) {
		return array();
	}

	public function findUser($key, $field, $createMissing = false) {
		return array();
	}

	public function templateView() {
		$hintableURL = false;
		$client = new Google_Client();
		$client->setApplicationName($this->CI->config->item("oAuthApplication"));
		$client->setClientId($this->CI->config->item("oAuthClient"));
		$client->setClientSecret($this->CI->config->item("oAuthSecret"));
		$client->setRedirectUri(site_url("/loginManager/remoteLogin/true"));
		$client->setState(current_url());
		$client->setPrompt("none");
		if($this->CI->input->cookie("LoginHint")) {
			$client->setLoginHint($this->CI->input->cookie("LoginHint"));
			$hintableURL = true;
		}
		$client->setScopes(['email', 'profile']);
		$authURL = $client->createAuthUrl();
		return $this->CI->load->view("authHelpers/googleRedirect", ["hintableURL"=>$hintableURL, "redirectURL"=>$authURL], true);
	}

}