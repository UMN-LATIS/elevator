<?php
/**
* Based class for auth helpers
*/

#[AllowDynamicProperties]
class AuthHelper
{
	
	public $shibboleth;
	public $authTypes = [];

	public function __construct()
	{

		$this->CI =& get_instance();
		
	}

	public function getDestination() { 
		return NULL;
	}

	public function remoteLogin($redirectURL, $noForcedAuth=false) {
		// Example Object-Oriented instantiation and redirect to login:
		
		
		if (!$this->CI->session->userdata('userAuthField')) {
			if($noForcedAuth == "true") {
				if($redirectURL) {
					redirect($redirectURL);
				}
				else {
					instance_redirect("/");
				}
				return true;
			}
			$target = urlencode(instance_url("/loginManager/remoteLogin?redirect=" . urlencode($redirectURL)));
			$redirect = "/Shibboleth/localSPLogin?target=".$target;

			instance_redirect($redirect);
		}
		return false;

	}

	public function remoteLogout() {
		var_dump("Hey");
		redirect("/Shibboleth/localSPLogout");
	}

	public function populateUserData($user) {
		return array();
	}
	
	public function getGroupMapping($userData) {
		return array();
	}

	// can be either a shibboleth class or a username
	public function createUserFromRemote() {
		return false;
	}

	public function updateUserFromRemote($user) {
		return false;
	}

	public function getUserIdFromRemote() {

	}

	public function findById($key, $createMissing=false) {
		return array();
	}

	public function findUserByUsername($key, $createMissing=false) {
		return array();
	}

	public function findUserByName($key, $createMissing = false) {
		return array();
	}

	public function autocompleteUsername($partialUsername) {
		$CI =& get_instance();
		$result = $CI->doctrine->em->getRepository("Entity\User")->createQueryBuilder('u')
				// ->where('u.instance= :instance')
				->andWhere('lower(u.displayName) LIKE lower(:name)')
				->orWhere('lower(u.email) LIKE lower(:email)')
				// ->setParameter('instance', $CI->instance)
				->setParameter('name', '%'.$partialUsername.'%')
				->setParameter('email', '%'.$partialUsername.'%')
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

	public function templateView() {
		return $this->CI->load->view("authHelpers/genericRefresh", null,true);
	}

}