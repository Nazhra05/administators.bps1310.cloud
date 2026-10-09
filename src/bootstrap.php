<?php
declare(strict_types=1);

/**
 * Application Bootstrap
 * Initializes environment, error handling, session, and global services.
 */

// 1. Composer Autoloader
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// 2. Global Helpers
require_once __DIR__ . '/Support/helpers.php';

// 3. Environment Configuration
$rootPath = dirname(__DIR__);
if (class_exists(\Dotenv\Dotenv::class) && file_exists($rootPath . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable($rootPath);
    $dotenv->safeLoad();
}

// 4. Boot Validation: Required Variables
$requiredEnv = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER'];
$missingEnv = [];
foreach ($requiredEnv as $req) {
    if (env($req) === null || env($req) === '') {
        $missingEnv[] = $req;
    }
}
if (!empty($missingEnv)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "FATAL: Konfigurasi sistem belum lengkap. Variabel lingkungan berikut wajib diisi di .env:\n- "
        . implode("\n- ", $missingEnv) . "\n";
    exit(1);
}

// 5. Timezone
date_default_timezone_set('Asia/Jakarta');

// 6. Centralized Logging & Error Handling
$isDebug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);

if (!headers_sent()) {
    header('X-Request-Id: ' . request_id());
}

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    \App\Support\Logger::error("PHP Error ({$severity}): {$message}", [
        'file'       => $file,
        'line'       => $line,
        'request_id' => request_id(),
    ]);

    if ($severity === E_USER_ERROR || $severity === E_RECOVERABLE_ERROR) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    return true;
});

set_exception_handler(function (\Throwable $e) use ($isDebug): void {
    \App\Support\Logger::error(sprintf('%s: %s', get_class($e), $e->getMessage()), [
        'file'       => $e->getFile(),
        'line'       => $e->getLine(),
        'trace'      => $e->getTraceAsString(),
        'request_id' => request_id(),
    ]);

    http_response_code(500);

    $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'data'  => null,
            'error' => [
                'code'       => 'server_error',
                'message'    => $isDebug ? $e->getMessage() : 'Terjadi kesalahan internal pada server.',
                'request_id' => request_id(),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    if ($isDebug) {
        echo '<h1>Aplikasi Mengalami Error (Debug Mode)</h1>';
        echo '<p><strong>' . e(get_class($e)) . ':</strong> ' . e($e->getMessage()) . '</p>';
        echo '<p><em>' . e($e->getFile()) . ':' . $e->getLine() . '</em></p>';
        echo '<p><small>Request ID: ' . e(request_id()) . '</small></p>';
        echo '<pre>' . e($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<!DOCTYPE html><html lang="id"><head><title>500 - Server Error</title>';
        echo '<meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<style>body{font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;color:#1e293b;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1.5rem}';
        echo '.card{background:#fff;border-radius:12px;padding:2.5rem;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);max-width:480px;text-align:center}';
        echo 'h1{font-size:1.75rem;margin:0 0 1rem;color:#0f172a}p{margin:0 0 1.25rem;color:#64748b;line-height:1.5}';
        echo '.req-id{font-family:monospace;font-size:0.875rem;background:#f1f5f9;padding:0.375rem 0.75rem;border-radius:6px;display:inline-block;color:#475569;margin-bottom:1.5rem}';
        echo '.btn{display:inline-block;padding:0.625rem 1.25rem;background:#0284c7;color:#fff;border-radius:8px;text-decoration:none;font-weight:500}</style></head>';
        echo '<body><div class="card"><h1>500 - Terjadi Kesalahan</h1>';
        echo '<p>Sistem kami sedang mengalami kendala sementara. Tim administrator telah diberitahu dan sedang menanganinya.</p>';
        echo '<div class="req-id">Request ID: ' . e(request_id()) . '</div><br>';
        echo '<a href="' . e(url('/')) . '" class="btn">Kembali ke Beranda</a>';
        echo '</div></body></html>';
    }
    exit;
});

// 7. Secure Session Initialization (Lampiran F)
\App\Support\Session::start();
