<?php
/**
* UMN Helper
*/





require_once("AuthHelper.php");
class UMNHelper extends AuthHelper
{
	public $authTypes = [UNIT_TYPE=>["name"=>UNIT_TYPE, "label"=>UNIT_TYPE], JOB_TYPE=>["name"=>JOB_TYPE, "label"=>"Job Code"], COURSE_TYPE=>["name"=>COURSE_TYPE, "label"=>COURSE_TYPE], DEPT_COURSE_TYPE=>["name"=>DEPT_COURSE_TYPE, "label"=>DEPT_COURSE_TYPE, "helpText"=>"Use % for wildcard, like DEPT.NUMBER% to include all sections."], STATUS_TYPE=>["name"=>STATUS_TYPE, "label"=>"Student Status"], EMPLOYEE_TYPE=>["name"=>EMPLOYEE_TYPE, "label"=>"Employee Type"]];

	public function __construct()
	{
		parent::__construct();
	}

	public function createUserFromRemote($userOverride=null, $map=null) {
		$CI =& get_instance();
		if(!$userOverride) {
			$username = $this->getUserIdFromRemote($map);
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
		if($map["isGuest"] == "Y") {
			$user->setUserType("Remote-Guest");
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


	}

	public function autocompleteUsername($partialUsername) {
		$CI =& get_instance();
		
		$outputArray = parent::autocompleteUsername($partialUsername);


		// now wildcard names
		$userMatches = $this->findUser($partialUsername);

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
		$CI =& get_instance();
		$map = $CI->session->userdata("userAttributesCache");

		if (isset($map) && is_array($map)) {
			
			$emplId = $map['emplId'];
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
						$courseName = join(".", [$entry->SUBJECT, $entry->CATALOG_NUMBER, $entry->CLASS_SECTION]);
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
				
				
				
				if($map['umnRegSummary']) {
					$regSummary = explode(";",$map['umnRegSummary']);
					foreach($regSummary as $studentCode) {
						$studentStatusArray = explode(":", $studentCode);
						if(isset($studentStatusArray[12]) && strlen($studentStatusArray[12]) == 4) {
							$studentStatus[] = $studentStatusArray[12];
						}
					}	
				}
				if($map['eduPersonAffiliation']) {
					$employeeType = $map['eduPersonAffiliation'];
					if(is_string($employeeType)) {
						$employeeType = [$employeeType];
					}
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
		$shib = ["eduPersonAffiliation","eppn","isGuest","uid","uniqueIdentifier","umnJobSummary","umnRegSummary", "eduCourseMember"];

		$shibData = [];
		foreach($map as $key=>$value) {
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
		return $this->findUser($key);
	}

	public function findUserByUsername($key, $createMissing=false) {
		return $this->findUser($key);
	}

	public function findUserByName($key, $createMissing = false) {
		return $this->findUser($key);
	}

	public function findUser($key) {		
		$results = $this->fetchBandaidResult("/api/names/autocomplete/" . urlencode($key));

		if(!is_array($results)) {
			return [];
		}
		$returnArray = array();
		foreach($results as $entry) {
			log_message('error', 'UMNHelper::findUser: ' . json_encode($entry)	);
			$user = new Entity\User;
			$user->setUsername($entry->UMNDID);
			$user->setDisplayName($entry->NAME);
			$user->setEmail($entry->INTERNET_ID . "@umn.edu");
			$returnArray[] = $user;
		}

		return $returnArray;
	}

	public function templateView() {
		return $this->CI->load->view("authHelpers/autoRedirect", null, true);
	}


	private function fetchBandaidResult($apiPath) {
		
		$CI =& get_instance();
		if(!$CI->config->item('umn_bearer_token')) {
			return [];
		}
       $ch = curl_init('https://bandaid.cla.umn.edu' . $apiPath); // Initialise cURL
       $authorization = "Authorization: Bearer ".$CI->config->item('umn_bearer_token'); // Prepare the authorisation token
       curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization )); // Inject the token into the header
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
       $result = curl_exec($ch); // Execute the cURL statement
       curl_close($ch); // Close the cURL connection
       return json_decode($result); // Return the received data
	}

}