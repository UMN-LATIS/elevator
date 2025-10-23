<?php
/**
* OU Helper
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
	// public $authTypes = [UNIT_TYPE=>["name"=>UNIT_TYPE, "label"=>UNIT_TYPE], JOB_TYPE=>["name"=>JOB_TYPE, "label"=>"Job Code"], COURSE_TYPE=>["name"=>COURSE_TYPE, "label"=>COURSE_TYPE], DEPT_COURSE_TYPE=>["name"=>DEPT_COURSE_TYPE, "label"=>DEPT_COURSE_TYPE, "helpText"=>"Use % for wildcard, like DEPT.NUMBER% to include all sections."], STATUS_TYPE=>["name"=>STATUS_TYPE, "label"=>"Student Status"], EMPLOYEE_TYPE=>["name"=>EMPLOYEE_TYPE, "label"=>"Employee Type"]];

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
		$coursesTaught = array();
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
			// if($this->shibboleth->getAttributeValue('eduCourseMember')) {
			// 	$courseArray = explode(";",$this->shibboleth->getAttributeValue('eduCourseMember'));

			// 	// hacky stuff to deal with the way this info is passed in
			// 	// todo: learn about the actual standard for eduCourseMember
			// 	foreach($courseArray as $course) {
			// 		$explodedString = explode("@", $course);
			// 		if(count($explodedString)>0) {
			// 			$role = $explodedString[0];
			// 		}
			// 		$courseString = explode("/", $course);
			// 		$courseId = $courseString[8];
			// 		$deptCourse = $courseString[6];
			// 		if($role == "Instructor") {
						
			// 			$courseName = $courseString[6];
			// 			$coursesTaught[$courseId + 0] = $courseName;
			// 			$deptCoursesTaught[$courseName] = $courseName;
			// 		}
			// 		$courses[] = $courseId + 0;
			// 		$deptCourses[] = $deptCourse;
			// 	}
			// }
			
			// if($this->shibboleth->getAttributeValue('umnJobSummary')) {
			// 	$jobCodeSummary = explode(";",$this->shibboleth->getAttributeValue('umnJobSummary'));
			// 	foreach($jobCodeSummary as $jobCode) {
			// 		$jobCodeArray = explode(":", $jobCode);
			// 		if(isset($jobCodeArray[2])) {
			// 			$jobCodes[] = $jobCodeArray[2] + 0;
			// 		}
			// 		if(isset($jobCodeArray[10])) {
			// 			$units[] = $jobCodeArray[10];
			// 		}

			// 	}
			// }
			
			// if($this->shibboleth->getAttributeValue('umnRegSummary')) {
			// 	$regSummary = explode(";",$this->shibboleth->getAttributeValue('umnRegSummary'));
			// 	foreach($regSummary as $studentCode) {
			// 		$studentStatusArray = explode(":", $studentCode);
			// 		if(isset($studentStatusArray[12]) && strlen($studentStatusArray[12]) == 4) {
			// 			$studentStatus[] = $studentStatusArray[12];
			// 		}
			// 	}	
			// }

			// if($this->shibboleth->getAttributeValue('eduPersonAffiliation')) {
			// 	$employeeType = explode(";",$this->shibboleth->getAttributeValue('eduPersonAffiliation'));
			// }
			
		}


		// $userData[COURSE_TYPE] = ["values"=>$courses, "hints"=>$coursesTaught];
		// $userData[DEPT_COURSE_TYPE] = ["values"=>$deptCourses, "hints"=>$deptCoursesTaught];
		// $userData[JOB_TYPE] = ["values"=>$jobCodes, "hints"=>[]];

		// $unitHints = array();
		// foreach($units as $unit) {
		// 	$unitHints[$unit] = $unit;
		// }
		// $userData[UNIT_TYPE] = ["values"=>$units, "hints"=>$unitHints];
		// $userData[STATUS_TYPE] = ["values"=>array_unique($studentStatus), "hints"=>["UGRD"=>"Undergraduate", "GRAD"=>"Graduate"]];

		// $userData[EMPLOYEE_TYPE] = ["values"=>array_unique($employeeType), "hints"=>["Faculty" => "Faculty","Student" => "Student","Staff" => "Staff","Alum " => "Alum" ,"Member" => "Member","Affiliate" => "Affiliate"]];

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

	public function remoteLogout() {
		
		
	}
}