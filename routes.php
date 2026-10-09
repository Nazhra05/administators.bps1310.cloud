<?php
declare(strict_types=1);

use App\Http\Router;
use App\Http\Middleware\RequireLogin;
use App\Http\Middleware\VerifyCsrf;
use App\Http\Middleware\ApiKey;

/**
 * Application Routes Definition
 */

// -----------------------------------------------------------------------------
// Health Check (Public, No Auth, Lampiran D)
// -----------------------------------------------------------------------------
Router::get('/api/v1/health', function () {
    try {
        $pdo = \App\Support\Database::pdo();
        $pdo->query('SELECT 1');
        return [
            'data'  => ['status' => 'ok'],
            'error' => null,
        ];
    } catch (\Throwable $e) {
        http_response_code(503);
        return [
            'data'  => null,
            'error' => [
                'code'    => 'service_unavailable',
                'message' => 'Database connection failed',
            ],
        ];
    }
});

// -----------------------------------------------------------------------------
// Public Authentication Routes (Web)
// -----------------------------------------------------------------------------
// Note: Will be attached to AuthController in Task 4.4
