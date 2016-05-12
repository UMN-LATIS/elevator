<?php
/**
* Based class for auth helpers
*/
class AuthHelper
{
	
	public function __construct()
	{

	}

	// can be either a shibboleth class or a username
	public function createUserFromRemote($shibHelper) {
		return false;
	}

	public function updateUserFromRemote($shibHelper) {
		return false;
	}

	public function getUsernameFromRemote($shibHelper) {

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

}