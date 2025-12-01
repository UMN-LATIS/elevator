<?php
/**
* Wisc Helper
*/


// define("COURSE_TYPE", "Class Number");
// define("DEPT_COURSE_TYPE", "Dept/Course Number");
// define("JOB_TYPE", "JobCode");
// define("UNIT_TYPE", "Unit");
// define("STATUS_TYPE", "StudentStatus");
// define("EMPLOYEE_TYPE", "EmployeeType");


require_once("AuthHelper.php");
class WiscAuthHelper extends AuthHelper
{
	public $authTypes = [GROUP_MEMBERSHIP=>["name"=>GROUP_MEMBERSHIP, "label"=>GROUP_MEMBERSHIP]];

	public function __construct()
	{
		parent::__construct();
		// $this->shibboleth->setCustomIdPEntityId("sso.ou.edu");
	}

	public function createUserFromRemote($userOverride=null, $map = null) {
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
		if($map["isGuest"]== "Y") {
			$user->setUserType("Remote-Guest");
		}
		if($map["first_name"] && $map["last_name"]) {
			$user->setDisplayName($map["first_name"] . " " . $map["last_name"]);
		}
		if($map["email"]) {
			$user->setEmail($map["email"]);
		}
		$user->setInstance($CI->instance);
		$user->setIsSuperAdmin(false);
		$user->setFastUpload(false);
		$CI->doctrine->em->persist($user);
		$CI->doctrine->em->flush();
		return $user;
	}

	public function getUserIdFromRemote($map=null) {
		if(!$map) {
			// try to load from the session and cache
			$userAuthField = $this->CI->session->userdata('userAuthField');
			return $userAuthField;
		}
		
		if($map) {
			return $map["uniqueIdentifier"];
		}
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
		$groupMembership = array();
		$courses = array();
		$deptCourses = array();
		$jobCodes = array();
		$units = array();
		$studentStatus = array();
		$deptCoursesTaught = array();
		$employeeType = array();
		$CI =& get_instance();
		$map = $CI->session->userdata("userAttributesCache");
		if (isset($map) && is_array($map)) {
			if(isset($map['eduPersonAffiliation'])) {
				$groupMembership = is_array($map['eduPersonAffiliation']) ? $map['eduPersonAffiliation'] : [$map['eduPersonAffiliation']];
			}				
		}
		else {
			return false;
		}


		// $userData[COURSE_TYPE] = ["values"=>$courses, "hints"=>$coursesTaught];
		// $userData[DEPT_COURSE_TYPE] = ["values"=>$deptCourses, "hints"=>$deptCoursesTaught];
		// $userData[JOB_TYPE] = ["values"=>$jobCodes, "hints"=>[]];

		// $unitHints = array();
		// foreach($units as $unit) {
		// 	$unitHints[$unit] = $unit;
		// }

		$groupHints = array();
		foreach($groupMembership as $group) {
			$groupHints[$group] = $group;
		}
		// $userData[UNIT_TYPE] = ["values"=>$units, "hints"=>$unitHints];
		// $userData[STATUS_TYPE] = ["values"=>array_unique($studentStatus), "hints"=>["UGRD"=>"Undergraduate", "GRAD"=>"Graduate"]];

		$userData[GROUP_MEMBERSHIP] = ["values"=>array_unique($groupMembership), "hints"=>$groupHints];
		
		$CI =& get_instance();
		$CI->logging->logError($user->getEmail(), $map);
		$CI->logging->logError($user->getEmail(), $userData);
		return $userData;

	}


	public function getGroupMapping($userData) {
		$outputArray = array();
		if(!$userData || !is_array($userData)) {
			return $outputArray;
		}
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
		
		
	}
}