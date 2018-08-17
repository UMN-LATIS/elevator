<?php
/**
* UMN Helper
*/


define("COURSE_TYPE", "Class Number");
define("DEPT_COURSE_TYPE", "Dept/Course Number");
define("JOB_TYPE", "JobCode");
define("UNIT_TYPE", "Unit");
define("STATUS_TYPE", "StudentStatus");
define("EMPLOYEE_TYPE", "EmployeeType");


require_once("AuthHelper.php");
class UMNHelper extends AuthHelper
{
	public $authTypes = [UNIT_TYPE=>["name"=>UNIT_TYPE, "label"=>UNIT_TYPE], JOB_TYPE=>["name"=>JOB_TYPE, "label"=>"Job Code"], COURSE_TYPE=>["name"=>COURSE_TYPE, "label"=>COURSE_TYPE], DEPT_COURSE_TYPE=>["name"=>DEPT_COURSE_TYPE, "label"=>DEPT_COURSE_TYPE, "helpText"=>"Use % for wildcard, like DEPT.NUMBER% to include all sections."], STATUS_TYPE=>["name"=>STATUS_TYPE, "label"=>"Student Status"], EMPLOYEE_TYPE=>["name"=>EMPLOYEE_TYPE, "label"=>"Employee Type"]];

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

		$user = $this->findById($username);

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
		$user->setInstance($CI->instance);
		$user->setIsSuperAdmin(false);
		$user->setFastUpload(false);
		$CI->doctrine->em->persist($user);
		$CI->doctrine->em->flush();
		return $user;
	}

	public function getUserIdFromRemote() {
		return $this->shibboleth->getAttributeValue('umnDID');
	}

	public function updateUserFromRemote($user) {


	}

	public function autocompleteUsername($partialUsername) {
		$CI =& get_instance();
		
		$outputArray = parent::autocompleteUsername($partialUsername);

		$userMatches = $this->findUserByUsername($partialUsername);
		foreach($userMatches as $user) {
			$tempArray = ["name"=>$user->getDisplayName(), "email"=>$user->getEmail(), "completionId"=>$user->getId(), "username"=>$user->getUsername()];

			$duplicate = false;
			foreach($outputArray as $entry) {
				if($entry["username"] == $user->getUsername()) {
					$duplicate = true;
				}
			}

			if(!$duplicate) {
				array_unshift($outputArray, $tempArray);
			}
		}

		// now wildcard names
		$userMatches = $this->findUserByName($partialUsername);

		$i = 0;
		foreach($userMatches as $user) {

			$tempArray = ["name"=>$user->getDisplayName(), "email"=>$user->getEmail(), "completionId"=>$user->getId(), "username"=>$user->getUsername()];

			$duplicate = false;
			foreach($outputArray as $entry) {
				if($entry["username"] == $user->getUsername()) {
					$duplicate = true;
				}
			}

			if(!$duplicate) {
				$outputArray[] = $tempArray;
			}

			if($i > 10) {
				break;
			}
			$i++;
		}

		return $outputArray;

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
			if($this->shibboleth->getAttributeValue('eduCourseMember')) {
				$courseArray = explode(";",$this->shibboleth->getAttributeValue('eduCourseMember'));

				// hacky stuff to deal with the way this info is passed in
				// todo: learn about the actual standard for eduCourseMember
				foreach($courseArray as $course) {
					$explodedString = explode("@", $course);
					if(count($explodedString)>0) {
						$role = $explodedString[0];
					}
					$courseString = explode("/", $course);
					$courseId = $courseString[8];
					$deptCourse = $courseString[6];
					if($role == "Instructor") {
						
						$courseName = $courseString[6];
						$coursesTaught[$courseId + 0] = $courseName;
						$deptCoursesTaught[$courseName] = $courseName;
					}
					$courses[] = $courseId + 0;
					$deptCourses[] = $deptCourse;
				}
			}
			
			if($this->shibboleth->getAttributeValue('umnJobSummary')) {
				$jobCodeSummary = explode(";",$this->shibboleth->getAttributeValue('umnJobSummary'));
				foreach($jobCodeSummary as $jobCode) {
					$jobCodeArray = explode(":", $jobCode);
					if(isset($jobCodeArray[2])) {
						$jobCodes[] = $jobCodeArray[2] + 0;
					}
					if(isset($jobCodeArray[10])) {
						$units[] = $jobCodeArray[10];
					}

				}
			}
			
			if($this->shibboleth->getAttributeValue('umnRegSummary')) {
				$regSummary = explode(";",$this->shibboleth->getAttributeValue('umnRegSummary'));
				foreach($regSummary as $studentCode) {
					$studentStatusArray = explode(":", $studentCode);
					if(isset($studentStatusArray[12]) && strlen($studentStatusArray[12]) == 4) {
						$studentStatus[] = $studentStatusArray[12];
					}
				}	
			}

			if($this->shibboleth->getAttributeValue('eduPersonAffiliation')) {
				$employeeType = explode(";",$this->shibboleth->getAttributeValue('eduPersonAffiliation'));
			}
			
		}


		$userData[COURSE_TYPE] = ["values"=>$courses, "hints"=>$coursesTaught];
		$userData[DEPT_COURSE_TYPE] = ["values"=>$deptCourses, "hints"=>$deptCoursesTaught];
		$userData[JOB_TYPE] = ["values"=>$jobCodes, "hints"=>[]];

		$unitHints = array();
		foreach($units as $unit) {
			$unitHints[$unit] = $unit;
		}
		$userData[UNIT_TYPE] = ["values"=>$units, "hints"=>$unitHints];
		$userData[STATUS_TYPE] = ["values"=>array_unique($studentStatus), "hints"=>["UGRD"=>"Undergraduate", "GRAD"=>"Graduate"]];

		$userData[EMPLOYEE_TYPE] = ["values"=>array_unique($employeeType), "hints"=>["Faculty" => "Faculty","Student" => "Student","Staff" => "Staff","Alum " => "Alum" ,"Member" => "Member","Affiliate" => "Affiliate"]];

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
		return $this->findUser($key, "umndid", $createMissing);
	}

	public function findUserByUsername($key, $createMissing=false) {
		return $this->findUser($key, "cn", $createMissing);
	}

	public function findUserByName($key, $createMissing = false) {
		return $this->findUser("*".str_replace(" ", "* ", $key) . "*", "displayname", $createMissing);
	}

	public function findUser($key, $field, $createMissing = false) {
		$CI =& get_instance();
		$ldap_host = $CI->config->item('ldapURI');
		$base_dn = array($CI->config->item('ldapSearchBase'),);
		$filter = "($field=" . $key. ")";
		$connect = ldap_connect( $ldap_host);

		ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);
		
		if($CI->config->item('ldapUsername') != "") {
			$r=ldap_bind($connect, $CI->config->item('ldapUsername'), $CI->config->item('ldapPassword'));
		}
		else {
			$r=ldap_bind($connect);
		}

		$search = ldap_search([$connect], $base_dn, $filter, [], 0, 10)
		      or exit(">>Unable to search ldap server<<");
		$returnArray = array();


		foreach($search as $readItem) {

			$info = ldap_get_entries($connect, $readItem);
			if($info["count"] == 0) {
				break;
			}
			foreach($info as $entry) {
				if(!isset($entry["umndid"])) {
					continue;
				}
				$user = new Entity\User;
				$user->setUsername($entry["umndid"][0]);
				if(!isset($entry["displayname"])) {
					$user->setDisplayName(@$entry["umndisplaymail"][0]);
				}
				else {
					$user->setDisplayName($entry["displayname"][0]);
				}

				$user->setEmail(@$entry["umndisplaymail"][0]);

				$returnArray[] = $user;
			}

		}

		// hacky fallback temporarily
		if(count($returnArray) == 0) {
			ldap_unbind($connect);
			ldap_close($connect);
			$connect = ldap_connect( $ldap_host);
			$r=ldap_bind($connect);
			$search = ldap_search([$connect], $base_dn, $filter, [], 0, 10)
		      or exit(">>Unable to search ldap server<<");
			
			foreach($search as $readItem) {

				$info = ldap_get_entries($connect, $readItem);
				if($info["count"] == 0) {
					break;
				}
				foreach($info as $entry) {
					if(!isset($entry["umndid"])) {
						continue;
					}
					$user = new Entity\User;
					$user->setUsername($entry["umndid"][0]);
					if(!isset($entry["displayname"])) {
						$user->setDisplayName(@$entry["umndisplaymail"][0]);
					}
					else {
						$user->setDisplayName($entry["displayname"][0]);
					}

					$user->setEmail(@$entry["umndisplaymail"][0]);

					$returnArray[] = $user;
				}

			}

		}

		


		return $returnArray;
	}

	public function templateView() {
		return $this->CI->load->view("authHelpers/autoRedirect", null, true);
	}

}