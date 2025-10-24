<?php
declare(strict_types=1);

namespace App\Services;

class CsrfService
{
    private const SESSION_KEY = 'csrf_token';

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
    }

    public function getToken(): string
    {
        return (string)($_SESSION[self::SESSION_KEY] ?? '');
    }

    public function validate(?string $token): bool
    {
        $current = (string)($_SESSION[self::SESSION_KEY] ?? '');
        return is_string($token) && $token !== '' && hash_equals($current, $token);
    }
}