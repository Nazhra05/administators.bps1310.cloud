<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Session management complying with security rules in Lampiran F.
 */
class Session
{
    private static bool $started = false;

    /**
     * Start session with hardened cookie settings.
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $appUrl = $_ENV['APP_URL'] ?? '';
        $isHttps = str_starts_with($appUrl, 'https://')
            || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_name('admin_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;
    }

    /**
     * Regenerate session ID safely on authentication changes.
     */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Set a flash message for the next request.
     */
    public static function flash(string $key, ?string $value = null): ?string
    {
        self::start();

        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        if (isset($_SESSION['_flash'][$key])) {
            $msg = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $msg;
        }

        return null;
    }

    /**
     * Get a session variable.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session variable.
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a session variable exists.
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session variable.
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the current session completely.
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
            self::$started = false;
        }
    }
}
