<?php

use Packback\Lti1p3\Interfaces\ICookie;

class LTI13Cookie implements ICookie
{
    private function isSecureRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    public function getCookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    public function setCookie(string $name, string $value, int $exp = 3600, array $options = []): void
    {
        $isSecure = $this->isSecureRequest();

        $defaultOptions = [
            'expires' => time() + $exp,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => $isSecure ? 'None' : 'Lax',
        ];

        $cookieOptions = array_merge($defaultOptions, $options);

        setcookie($name, $value, $cookieOptions);
    }
}
