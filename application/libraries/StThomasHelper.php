<?php
/**
* StThomas Helper
*/


// define("COURSE_TYPE", "Class Number");
// define("DEPT_COURSE_TYPE", "Dept/Course Number");
// define("JOB_TYPE", "JobCode");
// define("UNIT_TYPE", "Unit");
// define("STATUS_TYPE", "StudentStatus");
// define("EMPLOYEE_TYPE", "EmployeeType");
define("GROUP_MEMBER", "AD Group");

require_once("AuthHelper.php");
class StThomasHelper extends AuthHelper
{
	public $authTypes = [GROUP_MEMBER=>["name"=>GROUP_MEMBER, "label"=>GROUP_MEMBER]];

	public function __construct()
	{
		parent::__construct();
	}

	public function createUserFromRemote($userOverride=null) {
		$CI =& get_instance();
		if(!$userOverride) {
			$username = $this->getUserIdFromRemote();
		}
		else {
			$username = $userOverride;
		}

		$user = $this->findById($username,true);

		if(count($user) == 0) {
			return false;
		}
		else {
			$user = $user[0];
		}

		$user->setHasExpiry(false);
		$user->setCreatedAt(new \DateTime("now"));
		$user->setUserType("Remote");

		if($this->shibboleth->getAttributeValue("surName")) {
			$user->setDisplayName($this->shibboleth->getAttributeValue("givenName") . " " . $this->shibboleth->getAttributeValue("surName"));
		}
		if($this->shibboleth->getAttributeValue("email")) {
			$user->setEmail($this->shibboleth->getAttributeValue("email"));
		}
		$user->setInstance($CI->instance);
		$user->setIsSuperAdmin(false);
		$user->setFastUpload(false);
		$CI->doctrine->em->persist($user);
		$CI->doctrine->em->flush();
		return $user;
	}

	public function getUserIdFromRemote() {
		$email = $this->shibboleth->getAttributeValue('email');
		
		return array_shift(explode("@", $email));
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


	public function populateUserData($user) {
		$userData = array();

		
		if ($this->shibboleth->hasSession() && $this->shibboleth->getAttributeValue("groups")) {
			$groups = explode(";", $this->shibboleth->getAttributeValue("groups"));

			foreach($groups as $group) {
				$hintMembership[$group] = $group;
				$groupMembership[$group] = $group;
			}

			$userData[GROUP_MEMBER] = ["values"=>$groupMembership, "hints"=>$hintMembership];

			
		}
	
		return $userData;

	}

	public function getGroupMapping($userData) {
		$outputArray = array();

		foreach($userData as $key=>$value) {
			$outputArray[$key] = $value["values"];
		}

		return $outputArray;

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
		// return $this->CI->load->view("authHelpers/autoRedirect", null, true);
	}

	public function remoteLogout() {
		
		if ($this->shibboleth->hasSession() && $this->CI->config->item("shibbolethLogout")) {
			$this->shibboleth->redirectToLogout(["return"=>instance_redirect("/")]);
		}
	}

}