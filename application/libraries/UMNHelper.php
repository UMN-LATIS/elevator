<?php
/**
* UMN Helper
*/


define("COURSE_TYPE", "Course");
define("JOB_TYPE", "JobCode");
define("UNIT_TYPE", "Unit");
define("STATUS_TYPE", "StudentStatus");


require_once("AuthHelper.php");
class UMNHelper extends AuthHelper
{
	public $authTypes = [UNIT_TYPE=>["name"=>UNIT_TYPE, "label"=>UNIT_TYPE], JOB_TYPE=>["name"=>JOB_TYPE, "label"=>"Job Code"], COURSE_TYPE=>["name"=>COURSE_TYPE, "label"=>COURSE_TYPE], STATUS_TYPE=>["name"=>STATUS_TYPE, "label"=>"Student Status"]];

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
		$jobCodes = array();
		$units = array();
		$studentStatus = array();

		if ($this->shibboleth->hasSession()) {
			$courseArray = explode(";",$this->shibboleth->getAttributeValue('eduCourseMember'));

			// hacky stuff to deal with the way this info is passed in
			// todo: learn about the actual standard for eduCourseMember
			foreach($courseArray as $course) {
				$courseId = substr($course, -6);
				$explodedString = explode("@", $course);
				if(count($explodedString)>0) {
					$role = $explodedString[0];
				}
				if($role == "Instructor") {
					$courseString = explode("/", $course);
					$courseName = $courseString[6];
					$coursesTaught[$courseId + 0] = $courseName;
				}
				$courses[] = $courseId + 0;
			}

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

			$regSummary = explode(";",$this->shibboleth->getAttributeValue('umnRegSummary'));
			foreach($regSummary as $studentCode) {
				$studentStatusArray = explode(":", $studentCode);
				if(isset($studentStatusArray[12]) && strlen($studentStatusArray[12]) == 4) {
					$studentStatus[] = $studentStatusArray[12];
				}
			}
		}

		$userData[COURSE_TYPE] = ["values"=>$courses, "hints"=>$coursesTaught];
		$userData[JOB_TYPE] = ["values"=>$jobCodes, "hints"=>[]];

		$unitHints = array();
		foreach($units as $unit) {
			$unitHints[$unit] = $unit;
		}
		$userData[UNIT_TYPE] = ["values"=>$units, "hints"=>$unitHints];
		$userData[STATUS_TYPE] = ["values"=>array_unique($studentStatus), "hints"=>["UGRD"=>"Undergraduate", "GRAD"=>"Graduate"]];

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