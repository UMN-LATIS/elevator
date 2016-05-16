<?php
/**
* Based class for auth helpers
*/

class AuthHelper
{
	

	public $authTypes = [];

	public function __construct()
	{

	}

	public function populateUserDataFromShib($shibHelper) {
		return array();
	}
	
	public function getGroupMapping($userData) {
		return array();
	}

	// can be either a shibboleth class or a username
	public function createUserFromRemote($shibHelper) {
		return false;
	}

	public function updateUserFromRemote($shibHelper, $user) {
		return false;
	}

	public function getUserIdFromRemote($shibHelper) {

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
				->where('u.instance= :instance')
				->andWhere('u.displayName LIKE :name')
				->setParameter('instance', $CI->instance)
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

}