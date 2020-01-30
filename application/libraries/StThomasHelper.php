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


require_once("AuthHelper.php");
class StThomasHelper extends AuthHelper
{
	// public $authTypes = [UNIT_TYPE=>["name"=>UNIT_TYPE, "label"=>UNIT_TYPE], JOB_TYPE=>["name"=>JOB_TYPE, "label"=>"Job Code"], COURSE_TYPE=>["name"=>COURSE_TYPE, "label"=>COURSE_TYPE], DEPT_COURSE_TYPE=>["name"=>DEPT_COURSE_TYPE, "label"=>DEPT_COURSE_TYPE, "helpText"=>"Use % for wildcard, like DEPT.NUMBER% to include all sections."], STATUS_TYPE=>["name"=>STATUS_TYPE, "label"=>"Student Status"], EMPLOYEE_TYPE=>["name"=>EMPLOYEE_TYPE, "label"=>"Employee Type"]];

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
		if($this->shibboleth->getAttributeValue("isGuest") == "Y") {
			$user->setUserType("Remote-Guest");
		}
		if($this->shibboleth->getAttributeValue("displayName")) {
			$user->setDisplayName($this->shibboleth->getAttributeValue("displayName"));
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
		return $this->shibboleth->getAttributeValue('uwnetid');
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
		$coursesTaught = array();
		$courses = array();
		$deptCourses = array();
		$jobCodes = array();
		$units = array();
		$studentStatus = array();
		$deptCoursesTaught = array();
		$employeeType = array();
		
		if ($this->shibboleth->hasSession()) {
		
			
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
		return $this->CI->load->view("authHelpers/autoRedirect", null, true);
	}

}