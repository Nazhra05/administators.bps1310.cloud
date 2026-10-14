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

$rawApiKey = env('API_KEY');
if (defined('APP_ENV') && APP_ENV === 'production' && (!$rawApiKey || $rawApiKey === 'BPS_SOLSEL_API_2026_KEY_RAHASIA')) {
    throw new RuntimeException('FATAL CONFIGURATION ERROR: API_KEY must be securely defined in .env for production environment.');
}
defined('API_KEY') or define('API_KEY', $rawApiKey ?: 'BPS_SOLSEL_API_2026_KEY_RAHASIA');
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

require_once __DIR__ . '/portal_session.php';


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


/* =====================================================
   STANDARDIZED JSON RESPONSE HELPER
===================================================== */

if (!function_exists('json_response')) {
    /**
     * Mengirimkan respons JSON terstandarisasi dengan kode status HTTP yang tepat.
     *
     * @param bool $success Status keberhasilan operasi
     * @param string $message Pesan deskriptif ramah pengguna
     * @param mixed $data Data payload hasil operasi (opsional)
     * @param int $statusCode Kode status HTTP (default 200)
     * @param array $extra Properti tambahan jika dibutuhkan (misal: meta, pagination)
     * @return void
     */
    function json_response(bool $success, string $message, $data = null, int $statusCode = 200, array $extra = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');

        $payload = array_merge([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], $extra);

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}


/* =====================================================
   CENTRALIZED AUDIT LOGGING HELPER
===================================================== */

if (!function_exists('log_audit')) {
    /**
     * Mencatat riwayat aktivitas administratif ke tabel audit_logs.
     *
     * @param string $aksi Jenis aksi (misal: 'APPROVE_USER', 'DELETE_LAYANAN', 'DEACTIVATE_ADMIN')
     * @param string $tabelTarget Tabel yang terpengaruh (misal: 'admin', 'layanan')
     * @param int|null $idTarget ID record yang dimanipulasi
     * @param string|null $deskripsi Deskripsi detail atau perubahan data
     * @return bool
     */
    function log_audit(string $aksi, string $tabelTarget, ?int $idTarget = null, ?string $deskripsi = null): bool
    {
        global $conn;
        if (!isset($conn)) {
            return false;
        }

        try {
            $idAdmin = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

            $stmt = $conn->prepare("
                INSERT INTO audit_logs (id_admin, aksi, tabel_target, id_target, deskripsi, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $idAdmin,
                $aksi,
                $tabelTarget,
                $idTarget,
                $deskripsi,
                $ipAddress,
                $userAgent
            ]);
        } catch (Throwable $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('[AUDIT_LOG_ERROR] ' . $e->getMessage());
            }
            return false;
        }
    }
}


/* =====================================================
   CENTRALIZED LOGIN RATE LIMITING HELPER (SEC-09)
===================================================== */

if (!function_exists('check_login_rate_limit')) {
    /**
     * Memeriksa apakah percobaan login diizinkan atau terkena rate limit.
     *
     * @param string $identifier Username atau email
     * @param string|null $ipAddress IP klien
     * @param int $maxAttempts Batas maksimal kegagalan (default: 5)
     * @param int $decaySeconds Rentang waktu lockout dalam detik (default: 300 / 5 menit)
     * @return array ['allowed' => bool, 'retry_after' => int, 'remaining' => int, 'failed_count' => int]
     */
    function check_login_rate_limit(string $identifier, ?string $ipAddress = null, int $maxAttempts = 5, int $decaySeconds = 300): array
    {
        global $conn;
        if (!isset($conn)) {
            return ['allowed' => true, 'retry_after' => 0, 'remaining' => $maxAttempts, 'failed_count' => 0];
        }

        $ip = $ipAddress ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $cleanIdent = strtolower(trim($identifier));

        try {
            // Pruning berkala catatan login lama (> 1 hari)
            if (mt_rand(1, 100) <= 5) {
                $conn->exec("DELETE FROM login_attempts WHERE attempt_time < (NOW() - INTERVAL 1 DAY)");
            }

            // Hitung kegagalan berdasarkan IP atau identifier dalam jendela waktu
            $stmt = $conn->prepare("
                SELECT COUNT(*) as failed_count, MAX(attempt_time) as last_attempt
                FROM login_attempts
                WHERE (ip_address = ? OR (identifier != '' AND identifier = ?))
                  AND is_success = 0
                  AND attempt_time >= (NOW() - INTERVAL ? SECOND)
            ");
            $stmt->execute([$ip, $cleanIdent, $decaySeconds]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $failedCount = (int) ($row['failed_count'] ?? 0);
            if ($failedCount >= $maxAttempts) {
                $lastTime = strtotime($row['last_attempt'] ?? 'now');
                $retryAfter = max(1, ($lastTime + $decaySeconds) - time());
                return [
                    'allowed' => false,
                    'retry_after' => $retryAfter,
                    'remaining' => 0,
                    'failed_count' => $failedCount
                ];
            }

            return [
                'allowed' => true,
                'retry_after' => 0,
                'remaining' => max(0, $maxAttempts - $failedCount),
                'failed_count' => $failedCount
            ];
        } catch (Throwable $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('[RATE_LIMIT_CHECK_ERROR] ' . $e->getMessage());
            }
            return ['allowed' => true, 'retry_after' => 0, 'remaining' => $maxAttempts, 'failed_count' => 0];
        }
    }
}

if (!function_exists('record_login_attempt')) {
    /**
     * Mencatat percobaan login (sukses atau gagal).
     *
     * @param string $identifier Username atau email
     * @param bool $isSuccess Status login
     * @param string|null $ipAddress IP klien
     * @return void
     */
    function record_login_attempt(string $identifier, bool $isSuccess, ?string $ipAddress = null): void
    {
        global $conn;
        if (!isset($conn)) {
            return;
        }

        $ip = $ipAddress ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $cleanIdent = strtolower(trim($identifier));

        try {
            $stmt = $conn->prepare("
                INSERT INTO login_attempts (ip_address, identifier, is_success)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$ip, $cleanIdent, $isSuccess ? 1 : 0]);

            // Jika berhasil login, bersihkan percobaan gagal sebelumnya
            if ($isSuccess) {
                $delStmt = $conn->prepare("
                    DELETE FROM login_attempts
                    WHERE (ip_address = ? OR identifier = ?)
                      AND is_success = 0
                ");
                $delStmt->execute([$ip, $cleanIdent]);
            }
        } catch (Throwable $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('[RECORD_LOGIN_ATTEMPT_ERROR] ' . $e->getMessage());
            }
        }
    }
}