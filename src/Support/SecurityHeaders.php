<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Centralized HTTP Security Headers Manager.
 * Complies with Tasks 6.8 and 6.11 in todo.md.
 */
class SecurityHeaders
{
    /**
     * Emit hardened HTTP security headers.
     */
    public static function send(): void
    {
        if (headers_sent()) {
            return;
        }

        // Standard security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

        // CSP (Content Security Policy)
        $nonce = csp_nonce();
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; form-action 'self'; frame-ancestors 'none';");

        // Strict-Transport-Security only when running under HTTPS
        $appUrl = (string)env('APP_URL', '');
        $isHttps = str_starts_with($appUrl, 'https://')
            || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Authenticated routes cache control
        if (Session::has('user_id')) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }
    }
}
