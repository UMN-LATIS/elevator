<?php

use Packback\Lti1p3\Interfaces\ICache;

class LTI13Cache implements ICache
{
    private const SESSION_ROOT = 'lti13';

    private function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (!isset($_SESSION[self::SESSION_ROOT])) {
            $_SESSION[self::SESSION_ROOT] = [
                'launches' => [],
                'nonces' => [],
                'tokens' => [],
            ];
        }
    }

    public function getLaunchData(string $key): ?array
    {
        $this->ensureSession();

        return $_SESSION[self::SESSION_ROOT]['launches'][$key] ?? null;
    }

    public function cacheLaunchData(string $key, array $jwtBody): void
    {
        $this->ensureSession();
        $_SESSION[self::SESSION_ROOT]['launches'][$key] = $jwtBody;
    }

    public function cacheNonce(string $nonce, string $state): void
    {
        $this->ensureSession();
        $_SESSION[self::SESSION_ROOT]['nonces'][$nonce] = $state;
    }

    public function checkNonceIsValid(string $nonce, string $state): bool
    {
        $this->ensureSession();

        if (!isset($_SESSION[self::SESSION_ROOT]['nonces'][$nonce])) {
            return false;
        }

        $isValid = $_SESSION[self::SESSION_ROOT]['nonces'][$nonce] === $state;

        // One-time nonce use prevents replay attempts.
        unset($_SESSION[self::SESSION_ROOT]['nonces'][$nonce]);

        return $isValid;
    }

    public function cacheAccessToken(string $key, string $accessToken): void
    {
        $this->ensureSession();
        $_SESSION[self::SESSION_ROOT]['tokens'][$key] = $accessToken;
    }

    public function getAccessToken(string $key): ?string
    {
        $this->ensureSession();

        return $_SESSION[self::SESSION_ROOT]['tokens'][$key] ?? null;
    }

    public function clearAccessToken(string $key): void
    {
        $this->ensureSession();
        unset($_SESSION[self::SESSION_ROOT]['tokens'][$key]);
    }
}
