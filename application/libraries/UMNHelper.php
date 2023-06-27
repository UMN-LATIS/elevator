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
			
			$emplId = $this->shibboleth->getAttributeValue('umnEmplId');
			if($emplId) {
				$enrollment = $this->fetchBandaidResult("/api/enrollment/student/" . $emplId);
				if(is_array($enrollment)) {
					foreach($enrollment as $entry) {
						$deptCourses[] = join(".", [$entry->SUBJECT, $entry->CATALOG_NBR, $entry->CLASS_SECTION]);
						$courses[] = $entry->CLASS_NBR;
					}
				}
				

				$instructed = $this->fetchBandaidResult("/api/enrollment/instructor/" . $emplId);
				if(is_array($instructed)) {
					foreach($instructed as $entry) {
						$courseName = join(".", [$entry->SUBJECT, $entry->CATALOG_NBR, $entry->SECTION]);
						$deptCoursesTaught[$courseName] = $entry->DESCRIPTION;
						$coursesTaught[$entry->CLASS_NUMBER] = $courseName; 
					}
				}
				

				$jobs = $this->fetchBandaidResult("/api/employment/employee/" . $emplId);
				if(is_array($jobs)) {
					foreach($jobs as $entry) {

						$jobCodes[] = $entry->JOBCODE;
						$units[] = $entry->DEPTID;
					}
				}

				$reg = $this->fetchBandaidResult("/api/enrollment/regsummary/" . $emplId);
				if($reg) {
					if(isset($reg->ACAD_CAREER)) {
						$studentStatus[] = $reg->ACAD_CAREER;
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
			
		}
		else {
			return false;
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

		// log all our shib data to help debug some random failures
		$shib = ["eduPersonAffiliation","eppn","isGuest","uid","umnDID","umnJobSummary","umnRegSummary", "eduCourseMember"];
		$shibData = [];
		foreach($_SERVER as $key=>$value) {
			if(in_array($key, $shib)) {
				$shibData[$key]= $value;
			}
		}
		$CI =& get_instance();
		$CI->logging->logError($user->getEmail(), $shibData);
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
		return $this->findUser($key, "umndid", $createMissing);
	}

	public function findUserByUsername($key, $createMissing=false) {
		return $this->findUser($key, "uid", $createMissing);
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

		// // hacky fallback temporarily
		// if(count($returnArray) == 0) {
		// 	ldap_unbind($connect);
		// 	$connect = ldap_connect( $ldap_host);
		// 	$r=ldap_bind($connect);
		// 	$search = ldap_search([$connect], $base_dn, $filter, [], 0, 10)
		//       or exit(">>Unable to search ldap server<<");
			
		// 	foreach($search as $readItem) {

		// 		$info = ldap_get_entries($connect, $readItem);
		// 		if($info["count"] == 0) {
		// 			break;
		// 		}
		// 		foreach($info as $entry) {
		// 			if(!isset($entry["umndid"])) {
		// 				continue;
		// 			}
		// 			$user = new Entity\User;
		// 			$user->setUsername($entry["umndid"][0]);
		// 			if(!isset($entry["displayname"])) {
		// 				$user->setDisplayName(@$entry["umndisplaymail"][0]);
		// 			}
		// 			else {
		// 				$user->setDisplayName($entry["displayname"][0]);
		// 			}

		// 			$user->setEmail(@$entry["umndisplaymail"][0]);

		// 			$returnArray[] = $user;
		// 		}

		// 	}

		// }

		


		return $returnArray;
	}

	public function templateView() {
		return $this->CI->load->view("authHelpers/autoRedirect", null, true);
	}


	private function fetchBandaidResult($apiPath) {
	$CI =& get_instance();
       $ch = curl_init('https://cla-bandaid-prd-web.oit.umn.edu' . $apiPath); // Initialise cURL
       $authorization = "Authorization: Bearer ".$CI->config->item('umn_bearer_token'); // Prepare the authorisation token
       curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization )); // Inject the token into the header
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
       $result = curl_exec($ch); // Execute the cURL statement
       curl_close($ch); // Close the cURL connection
       return json_decode($result); // Return the received data
	}

}