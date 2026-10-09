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
        if ($val === false) {
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
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $target = $path;
        } elseif (str_starts_with($path, '/')) {
            $target = url($path);
        } else {
            $target = $path;
        }

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

if (!function_exists('audit_log')) {
    /**
     * Helper to write an entry to audit_log table.
     *
     * @param array<string, mixed>|null $meta
     */
    function audit_log(string $action, string $entity, ?int $entityId = null, ?array $meta = null): void
    {
        \App\Support\AuditLogger::log($action, $entity, $entityId, $meta);
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

if (!function_exists('request_id')) {
    /**
     * Retrieve or generate unique trace request ID for the current lifecycle.
     */
    function request_id(): string
    {
        static $id = null;
        if ($id === null) {
            $incoming = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;
            if (!empty($incoming) && is_string($incoming) && preg_match('/^[a-zA-Z0-9_\-\.]{8,64}$/', $incoming)) {
                $id = $incoming;
            } else {
                $id = bin2hex(random_bytes(8));
            }
        }
        return $id;
    }
}

if (!function_exists('csp_nonce')) {
    /**
     * Retrieve or generate per-request CSP nonce.
     */
    function csp_nonce(): string
    {
        static $nonce = null;
        if ($nonce === null) {
            $nonce = base64_encode(random_bytes(16));
        }
        return $nonce;
    }
}

if (!function_exists('service_initials')) {
    /**
     * Generate 1-2 uppercase initials from a service name.
     */
    function service_initials(string $name): string
    {
        $clean = trim(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $name) ?? '');
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (empty($words)) {
            return 'BP';
        }

        if (count($words) === 1) {
            return strtoupper(mb_substr($words[0], 0, 2));
        }

        return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
}

if (!function_exists('service_color')) {
    /**
     * Generate a consistent gradient background based on string hash.
     */
    function service_color(string $name): string
    {
        $palette = [
            'linear-gradient(135deg, #2563eb, #1d4ed8)', // Royal Blue
            'linear-gradient(135deg, #059669, #047857)', // Emerald
            'linear-gradient(135deg, #7c3aed, #6d28d9)', // Violet
            'linear-gradient(135deg, #d97706, #b45309)', // Amber
            'linear-gradient(135deg, #db2777, #be185d)', // Pink
            'linear-gradient(135deg, #0891b2, #0e7490)', // Cyan
            'linear-gradient(135deg, #ea580c, #c2410c)', // Orange
            'linear-gradient(135deg, #0d9488, #0f766e)', // Teal
            'linear-gradient(135deg, #4f46e5, #4338ca)', // Indigo
            'linear-gradient(135deg, #e11d48, #9f1239)', // Rose
        ];
        $hash = crc32($name);
        return $palette[abs($hash) % count($palette)];
    }
}
