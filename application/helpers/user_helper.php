<?php

use \Entity\User;

if (!function_exists('hasUserAccountExpired')) {
    function hasUserAccountExpired(User $user): bool {
        return $user->getHasExpiry() && $user->getExpires() > new \DateTime();
    }
}

if (!function_exists('verifyUserPassword')) {
    function verifyUserPassword(User $user, string $password): bool {
        $hashedPass = sha1(config_item('encryption_key') . $password);
        $hashedPass2 = sha1("monkeybox43049pokdhjaldsjkaf" . $password);

        $storedPassword = $user->getPassword();
        return $storedPassword === $hashedPass || $storedPassword === $hashedPass2;
    }
}

/**
 * Get a user by a set of parameters
 * @param  array  params for findOneBy method
 * @return User  User object
 * 
 * @example 
 * $user = $this->user_model->getUserBy([
 * 	"username" => "testuser",
 * 	"userType" => "local"
 * ]);
 */
if (!function_exists('getUserBy')) {
    function getUserBy(array $params): ?User {
        $CI =& get_instance();
        return $CI->doctrine->em->getRepository('Entity\User')->findOneBy($params);
    }
}