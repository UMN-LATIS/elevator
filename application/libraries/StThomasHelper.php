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

		if($map["last_name"]) {
			$user->setDisplayName($map["first_name"] . " " .$map["last_name"]);
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
			return array_shift(explode("@", $userAuthField));
		}
		
		if($map) {
			return array_shift(explode("@", $map["uniqueIdentifier"]));
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

		$CI =& get_instance();
		$map = $CI->session->userdata("userAttributesCache");
		if (isset($map) && is_array($map)) {
			if ($map["eduPersonAffiliation"]) {
				$groups = $map["eduPersonAffiliation"];

				foreach($groups as $group) {
					$hintMembership[$group] = $group;
					$groupMembership[$group] = $group;
				}

				$userData[GROUP_MEMBER] = ["values"=>$groupMembership, "hints"=>$hintMembership];
			}
			
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

	public function autocompleteUsername($partialUsername) {
		$CI =& get_instance();
		$result = $CI->doctrine->em->getRepository("Entity\User")->createQueryBuilder('u')
				->where('u.displayName LIKE :name')
				->setParameter('name', '%'.$partialUsername.'%')
				->getQuery()
				->getResult();

		//TODO: limit number of users
		//TODO: only search local uesrs
		$userMatches = $CI->doctrine->em->getRepository("Entity\User")->findBy(["username"=>$partialUsername]);
		$outputArray = array();
		foreach($userMatches as $user) {
			$tempArray = ["name"=>$user->getDisplayName(), "email"=>$user->getEmail(), "completionId"=>$user->getId(), "username"=>$user->getUsername()];
			$outputArray[$user->getId()] = $tempArray;
		}
		$i=0;
		foreach($result as $user) {
			$tempArray = ["name"=>$user->getDisplayName(), "email"=>$user->getEmail(), "completionId"=>$user->getId(), "username"=>$user->getUsername()];
			if(!array_key_exists($user->getId(), $outputArray)) {
				$outputArray[$user->getId()] = $tempArray;
			}
			$i++;
			if($i>10) {
				break;
			}
		}
		return $outputArray;
	}


	public function remoteLogout() {
		
		
	}

}
