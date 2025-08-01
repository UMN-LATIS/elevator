<?php
/**
* St. Olaf OAuth Helper
*/

define("GROUP_MEMBER", "Google Group");

require_once("AuthHelper.php");
class StOlafOAuthHelper extends AuthHelper
{

	private $userId;
	private $name;
	private $email;

	public $authTypes = [GROUP_MEMBER=>["name"=>GROUP_MEMBER, "label"=>GROUP_MEMBER]];


	public function __construct()
	{
		parent::__construct();
	}


	public function remoteLogin($redirectURL, $noForcedAuth=false) {
		$jwt = new \Firebase\JWT\JWT;
		$jwt::$leeway = 5;
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
					$this->CI->errorhandler_helper->callError("invalidToken");
					return false;
				}
				if(!strstr($tokenVerify['email'], $this->CI->config->item("oAuthDomain")) && !strstr($tokenVerify['email'], "mcfa0086@umn.edu")) { // todo
					$this->CI->errorhandler_helper->callError("badSource");
					return false;	
				}
				$this->email = $tokenVerify["email"];
				$this->userId = str_replace("@" . $tokenVerify["hd"], "", $this->email);
				$this->name = $tokenVerify["name"];
				$this->CI->input->set_cookie(["name"=>"LoginHint", "value"=>$this->email, "expire" => 60*60*24*365]);
			}
			else {
				$this->CI->errorhandler_helper->callError("invalidToken");
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


	public function getGroupMapping($userData) {
		$outputArray = array();
		foreach($userData as $key=>$value) {
			$outputArray[$key] = $value["values"];
		}
		return $outputArray;

	}

	public function populateUserData($user = null) {

		$userData = array();
		$groupMembershp = array();
		if($user) {

			// we need to load their group membership from google
			$client = new Google_Client();
			$client->setAuthConfig($this->CI->config->item("oAuthDelegate"));
			$optParams = array(
			  // 'customer' => 'my_customer',
			  // 'domain' => 'stolaf.edu',
			  'userKey' => $user->getEmail()
			);
			$client->setApplicationName("Elevator");
			$client->setScopes(['https://www.googleapis.com/auth/admin.directory.group', 'https://www.googleapis.com/auth/admin.directory.user']);
			$client->setSubject('googleadmin@stolaf.edu');
			$dir = new Google_Service_Directory($client);
			$r = $dir->groups->listGroups($optParams);
			$groupMembership = array();
			$hintMembership = array();
			foreach($r->getGroups() as $group) {
				$groupId = $group->getEmail();
				if(!strstr($groupId, "@stolaf.edu")) {
					continue;
				}
				$groupId = str_replace("@stolaf.edu", "", $groupId);
				$hintMembership[$groupId] = $group->getName();
				$groupMembership[$groupId] = $groupId;
			}

			$optParams = array(
			  // 'customer' => 'my_customer',
			  'domain' => 'stolaf.edu',
			);
			
			// $r = $dir->groups->listGroups($optParams);
			// foreach($r->getGroups() as $group) {
			// 	$hintMembership[$group->getId()] = $group->getName();
			// }
			// var_dump($groupMembership);
			$userData[GROUP_MEMBER] = ["values"=>$groupMembership, "hints"=>$hintMembership];

		}
		
		return $userData;


	}


	public function createUserFromRemote($usernameOverride = null) {
		$user = new Entity\User;	

		if(!$usernameOverride) {
			$username = $this->getUserIdFromRemote();
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
		$user->setInstance($this->CI->instance);
		$user->setIsSuperAdmin(false);
		$user->setFastUpload(false);
		$this->CI->doctrine->em->persist($user);
		$this->CI->doctrine->em->flush();
		return $user;
	}

	public function getUserIdFromRemote() {
		return $this->userId;
	}

	public function updateUserFromRemote($user) {
		if($user->getDisplayName() == "") {
			$user->setDisplayName($this->name);
		}
		if($user->getEmail() == "") {
			$user->setEmail($this->email);
		}
		$this->CI->doctrine->em->persist($user);
		$this->CI->doctrine->em->flush();

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


	public function autocompleteUsername($partialUsername) {
		$CI =& get_instance();
		
		// First, get results from parent method
		$outputArray = parent::autocompleteUsername($partialUsername);
		
		// Set up Google Directory API client
		$client = new Google_Client();
		$client->setAuthConfig($CI->config->item("oAuthDelegate"));
		$client->setApplicationName("Elevator");
		$client->setScopes(['https://www.googleapis.com/auth/admin.directory.user']);
		$client->setSubject('googleadmin@stolaf.edu');
		
		// Create Directory service
		$dir = new Google_Service_Directory($client);
		
		// Set up query parameters - using a simpler query format
		$optParams = array(
			'domain' => 'stolaf.edu',
			'query' => $partialUsername,  // Simplified query
			'maxResults' => 10,
			'orderBy' => 'email',
			'sortOrder' => 'ascending'
		);
		
		try {
			// Execute the API request
			$results = $dir->users->listUsers($optParams);
			$googleResults = $results->getUsers();
			
			// Log the number of results for debugging
			// Process Google Directory results and create User models
			foreach ($googleResults as $googleUser) {
				$email = $googleUser->getPrimaryEmail();
				$username = str_replace('@stolaf.edu', '', $email);
				$name = $googleUser->getName()->getFullName();
				

				// check if this user already exists
				$existingUser = $CI->doctrine->em->getRepository("Entity\User")->findOneBy(["username"=>$username]);
				if($existingUser) {
					// User already exists, skip creating a new one
					continue;
				}

				// Create a new User entity
				$user = new Entity\User;
				$user->setUsername($username);
				$user->setDisplayName($name);
				$user->setEmail($email);
				$user->setHasExpiry(false);
				$user->setCreatedAt(new \DateTime("now"));
				$user->setUserType("Remote");
				$user->setInstance($CI->instance);
				$user->setIsSuperAdmin(false);
				$user->setFastUpload(false);
				
				// Persist the user to the database
				$CI->doctrine->em->persist($user);
				$CI->doctrine->em->flush();
				
				// Add to output array
				$tempArray = [
					"name" => $name,
					"email" => $email,
					"completionId" => $user->getId(),
					"username" => $username
				];
				
				// Check for duplicates
				$duplicate = false;
				foreach ($outputArray as $entry) {
					if ($entry["username"] == $username) {
						$duplicate = true;
						break;
					}
				}
				
				if (!$duplicate) {
					$outputArray[] = $tempArray;
				}
			}
		} catch (Exception $e) {
			// Log the error for debugging
			$this->logging->logError("Google Directory API error", $e->getMessage());
		}
		
		return $outputArray;
	}

}