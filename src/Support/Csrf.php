<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Cross-Site Request Forgery (CSRF) token generation and verification.
 */
class Csrf
{
    /**
     * Get or generate a CSRF token stored in the user session.
     */
    public static function token(): string
    {
        Session::start();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Render hidden HTML form input field for CSRF.
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Verify submitted CSRF token using constant-time string comparison.
     */
    public static function verify(?string $token = null): bool
    {
        Session::start();

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken)) {
            return false;
        }

        if ($token === null) {
            $token = $_POST['csrf_token']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? $_SERVER['HTTP_X_XSRF_TOKEN']
                ?? '';
        }

        return hash_equals($sessionToken, (string)$token);
    }
}
