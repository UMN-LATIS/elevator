<?php
/**
* UMN Helper
*/

require_once("AuthHelper.php");
class StOlafHelper extends AuthHelper
{
	
	public function __construct()
	{
		parent::__construct();
	}

	public function createUserFromRemote($userOverride=null) {
		$CI =& get_instance();
		$user = new Entity\User;	

		if(!$userOverride) {
			$username = $this->getUserIdFromRemote();
			$user->setDisplayName($this->shibboleth->getAttributeValue('displayName'));
			$user->setEmail(array_pop(explode(";", $this->shibboleth->getAttributeValue('mail'))));
		}
		else {
			$username = $userOverride;
		}

		$user->setUsername($username);

		$user->setHasExpiry(false);
		$user->setCreatedAt(new \DateTime("now"));
		$user->setUserType("Remote");
		$user->setInstance($CI->instance);
		$user->setIsSuperAdmin(false);
		$user->setFastUpload(false);
		$CI->doctrine->em->persist($user);
		$CI->doctrine->em->flush();
		return $user;
	}

	public function getUserIdFromRemote() {
		return $this->shibboleth->getAttributeValue('uid');
	}

	public function updateUserFromRemote($user) {
		$CI =& get_instance();
		if($user->getDisplayName() == "") {
			$user->setDisplayName($this->shibboleth->getAttributeValue('displayName'));
		}
		if($user->getEmail() == "") {
			$user->setEmail(array_pop(explode(";", $this->shibboleth->getAttributeValue('mail'))));
		}
		$CI->doctrine->em->persist($user);
		$CI->doctrine->em->flush();

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