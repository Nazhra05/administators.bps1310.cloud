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
    \App\Support\SecurityHeaders::send();
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
        echo view('errors/500', [
            'title'     => '500 - Kesalahan Server',
            'heading'   => 'Terjadi Kesalahan Server',
            'message'   => 'Sistem kami sedang mengalami kendala sementara. Tim administrator telah diberitahu dan sedang menangani masalah ini.',
            'requestId' => request_id(),
        ], null);
    }
    exit;
});

// 7. Secure Session Initialization (Lampiran F)
\App\Support\Session::start();
