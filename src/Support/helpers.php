<?php
declare(strict_types=1);

use App\Support\Database;
use App\Support\Session;
use App\Support\Csrf;
use App\Support\View;

if (!function_exists('env')) {
    /**
     * Get environment variable with fallback.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null) {
            return $default;
        }

        return match (strtolower((string)$val)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)'   => null,
            default            => $val,
        };
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML output safely to prevent XSS.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * Generate an application URL with proper APP_BASE_PATH handling.
     */
    function url(string $path = '/'): string
    {
        $base = rtrim((string)env('APP_BASE_PATH', ''), '/');
        if ($base === '') {
            $appUrlPath = parse_url((string)env('APP_URL', ''), PHP_URL_PATH);
            if (!empty($appUrlPath) && $appUrlPath !== '/') {
                $base = rtrim($appUrlPath, '/');
            }
        }

        $cleanPath = '/' . ltrim($path, '/');

        if ($cleanPath === '/' && !empty($base)) {
            return $base . '/';
        }

        return $base . $cleanPath;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate an asset URL.
     */
    function asset(string $path): string
    {
        return url('/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to another URL and exit.
     */
    function redirect(string $path, int $status = 302): never
    {
        $target = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : url($path);

        header("Location: {$target}", true, $status);
        exit;
    }
}

if (!function_exists('flash')) {
    /**
     * Set or get a flash message.
     */
    function flash(string $key, ?string $value = null): ?string
    {
        return Session::flash($key, $value);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Retrieve current CSRF token.
     */
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Render hidden CSRF token input tag.
     */
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_input')) {
    /**
     * Alias for csrf_field().
     */
    function csrf_input(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_verify')) {
    /**
     * Verify CSRF token from current request.
     */
    function csrf_verify(?string $token = null): bool
    {
        return Csrf::verify($token);
    }
}

if (!function_exists('view')) {
    /**
     * Render a view template.
     */
    function view(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        return View::render($template, $data, $layout);
    }
}

if (!function_exists('json_response')) {
    /**
     * Return JSON HTTP response and terminate execution.
     */
    function json_response(mixed $data, int $status = 200, array $headers = []): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $key => $val) {
            header("{$key}: {$val}");
        }

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('db')) {
    /**
     * Return global PDO database instance.
     */
    function db(): PDO
    {
        return Database::pdo();
    }
}
