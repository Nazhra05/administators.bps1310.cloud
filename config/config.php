<?php

/* =====================================================
   ENVIRONMENT CONFIGURATION LOADER
===================================================== */

(function () {
    $envPath = dirname(__DIR__) . '/.env';
    if (!file_exists($envPath)) {
        // Fallback to scripts/.env if root .env does not exist
        $envPath = dirname(__DIR__) . '/scripts/.env';
    }

    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if (!isset($_SERVER[$key]) && !isset($_ENV[$key])) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
})();

function env(string $key, $default = null)
{
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($val === false || $val === null || $val === '') {
        return $default;
    }
    return $val;
}


/* =====================================================
   APPLICATION SETTINGS
===================================================== */

defined('APP_NAME') or define('APP_NAME', env('APP_NAME', 'Dashboard Admin BPS Solok Selatan'));
defined('APP_ENV') or define('APP_ENV', env('APP_ENV', 'development'));
defined('APP_DEBUG') or define('APP_DEBUG', filter_var(env('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN));
defined('APP_URL') or define('APP_URL', rtrim(env('APP_URL', 'https://administators.bps1310.cloud'), '/'));

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}


/* =====================================================
   DATABASE CONFIGURATION
===================================================== */

defined('DB_HOST') or define('DB_HOST', env('DB_HOST', '127.0.0.1'));
defined('DB_PORT') or define('DB_PORT', env('DB_PORT', '3306'));
defined('DB_USER') or define('DB_USER', env('DB_USER', 'root'));
defined('DB_PASS') or define('DB_PASS', env('DB_PASS', ''));
defined('DB_NAME') or define('DB_NAME', env('DB_NAME', 'bps_solsel'));


/* =====================================================
   API CONFIGURATION
===================================================== */

defined('API_KEY') or define('API_KEY', env('API_KEY', 'BPS_SOLSEL_API_2026_KEY_RAHASIA'));
defined('API_URL') or define('API_URL', env('API_URL', APP_URL . '/api/layanan.php'));


/* =====================================================
   GMAIL SMTP CONFIGURATION
===================================================== */

defined('SMTP_HOST') or define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
defined('SMTP_PORT') or define('SMTP_PORT', (int) env('SMTP_PORT', 587));
defined('SMTP_USERNAME') or define('SMTP_USERNAME', env('SMTP_USERNAME', ''));
defined('SMTP_PASSWORD') or define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));
defined('SMTP_FROM_EMAIL') or define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', ''));
defined('SMTP_FROM_NAME') or define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'BPS Kabupaten Solok Selatan'));


/* =====================================================
   KONEKSI DATABASE (PDO)
===================================================== */

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());

    if (APP_DEBUG) {
        die("Koneksi database gagal: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }

    die("Koneksi database gagal. Silakan periksa konfigurasi server atau hubungi administrator.");
}


/* =====================================================
   SESSION MANAGEMENT
===================================================== */

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}


/* =====================================================
   CSRF PROTECTION HELPERS
===================================================== */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function csrf_verify(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string) $token);
}


/* =====================================================
   XSS ESCAPING HELPER
===================================================== */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}