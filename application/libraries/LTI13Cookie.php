<?php

use Packback\Lti1p3\Interfaces\ICookie;

class LTI13Cookie implements ICookie
{
    public function getCookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    public function setCookie(string $name, string $value, int $exp = 3600, array $options = []): void
    {
        $defaultOptions = [
            'expires' => time() + $exp,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'None',
        ];

        $cookieOptions = array_merge($defaultOptions, $options);

        setcookie($name, $value, $cookieOptions);
    }
}
