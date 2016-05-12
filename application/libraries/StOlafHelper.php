<?php
/**
* UMN Helper
*/

require_once("AuthHelper.php");
class StOlafHelper extends AuthHelper
{
	
	public function __construct()
	{

	}

	public function createUserFromRemote($shibHelper) {
		$user = new Entity\User;	

		if(is_object($shibHelper)) {
			$username = $this->getUserIdFromRemote($shibHelper);
			$user->setDisplayName($shibHelper->getAttributeValue('displayName'));
			$user->setEmail(array_pop(explode(";", $shibHelper->getAttributeValue('mail'))));
		}
		else {
			$username = $shibHelper;
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

	public function getUserIdFromRemote($shibHelper) {
		return $shibHelper->getAttributeValue('uid');
	}

	public function updateUserFromRemote($shibHelper, $user) {

		if($user->getDisplayName() == "") {
			$user->setDisplayName($shibHelper->getAttributeValue('displayName'));
		}
		if($user->getEmail() == "") {
			$user->setEmail(array_pop(explode(";", $shibHelper->getAttributeValue('mail'))));
		}
		$CI->doctrine->em->persist($user);
		$CI->doctrine->em->flush();

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

	public function findUser($key, $field, $createMissing = false) {
		return array();
	}

}