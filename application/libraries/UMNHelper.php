<?php
/**
* UMN Helper
*/

require_once("AuthHelper.php");
class UMNHelper extends AuthHelper
{
	
	public function __construct()
	{

	}

	public function createUserFromRemote($shibHelper) {
		$CI =& get_instance();
		if(is_object($shibHelper)) {
			$username = $this->getUserIdFromRemote($shibHelper);
		}
		else {
			$username = $shibHelper;
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
		$user->setInstance($CI->instance);
		$user->setIsSuperAdmin(false);
		$user->setFastUpload(false);
		$CI->doctrine->em->persist($user);
		$CI->doctrine->em->flush();
		return $user;
	}

	public function getUserIdFromRemote($shibHelper) {
		return $shibHelper->getAttributeValue('umnDID');
	}

	public function updateUserFromRemote($shibHelper) {


	}

	public function autocompleteUsername($partialUsername) {
		$CI =& get_instance();
		
		$outputArray = parent::autocompleteUsername($partialUsername);

		$userMatches = $CI->findUserByUsername($partialUsername);
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
		$userMatches = $CI->findUserByName($partialUsername);

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
		return $returnArray;
	}

}