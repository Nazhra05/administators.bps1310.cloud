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
// Protected Dashboard Routes
// -----------------------------------------------------------------------------
Router::get('/', [\App\Http\Controllers\DashboardController::class, 'index'], [RequireLogin::class]);
Router::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'], [RequireLogin::class]);
Router::get('/index.php', [\App\Http\Controllers\DashboardController::class, 'index'], [RequireLogin::class]);
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
// Authentication Routes (Web & Backward Compatibility Aliases)
// -----------------------------------------------------------------------------
Router::get('/login', [\App\Http\Controllers\AuthController::class, 'loginForm']);
Router::get('/login.php', [\App\Http\Controllers\AuthController::class, 'loginForm']);
Router::post('/login', [\App\Http\Controllers\AuthController::class, 'loginSubmit']);
Router::post('/login.php', [\App\Http\Controllers\AuthController::class, 'loginSubmit']);

Router::get('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
Router::get('/logout.php', [\App\Http\Controllers\AuthController::class, 'logout']);
Router::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);

Router::get('/register', [\App\Http\Controllers\AuthController::class, 'registerForm']);
Router::get('/register.php', [\App\Http\Controllers\AuthController::class, 'registerForm']);
Router::post('/register', [\App\Http\Controllers\AuthController::class, 'registerSubmit']);
Router::post('/register.php', [\App\Http\Controllers\AuthController::class, 'registerSubmit']);

Router::get('/verify-email', [\App\Http\Controllers\AuthController::class, 'verifyEmail']);
Router::get('/verify_email.php', [\App\Http\Controllers\AuthController::class, 'verifyEmail']);

Router::get('/verify-user', [\App\Http\Controllers\AuthController::class, 'verifyUser']);
Router::get('/verify_user.php', [\App\Http\Controllers\AuthController::class, 'verifyUser']);

Router::get('/setup-user', [\App\Http\Controllers\AuthController::class, 'setupUserForm']);
Router::get('/setup_user.php', [\App\Http\Controllers\AuthController::class, 'setupUserForm']);
Router::post('/setup-user', [\App\Http\Controllers\AuthController::class, 'setupUserSubmit']);
Router::post('/setup_user.php', [\App\Http\Controllers\AuthController::class, 'setupUserSubmit']);

// -----------------------------------------------------------------------------
// Kategori Routes (Clean URLs & Backward Compatibility Aliases)
// -----------------------------------------------------------------------------
Router::get('/kategori', [\App\Http\Controllers\KategoriController::class, 'index'], [RequireLogin::class]);
Router::get('/kategori.php', [\App\Http\Controllers\KategoriController::class, 'index'], [RequireLogin::class]);

Router::get('/kategori/tambah', [\App\Http\Controllers\KategoriController::class, 'create'], [RequireLogin::class]);
Router::get('/tambah_kategori.php', [\App\Http\Controllers\KategoriController::class, 'create'], [RequireLogin::class]);
Router::post('/kategori/tambah', [\App\Http\Controllers\KategoriController::class, 'store'], [RequireLogin::class]);
Router::post('/tambah_kategori.php', [\App\Http\Controllers\KategoriController::class, 'store'], [RequireLogin::class]);

Router::get('/kategori/{id:\d+}/edit', [\App\Http\Controllers\KategoriController::class, 'edit'], [RequireLogin::class]);
Router::get('/edit_kategori.php', [\App\Http\Controllers\KategoriController::class, 'edit'], [RequireLogin::class]);
Router::post('/kategori/{id:\d+}/edit', [\App\Http\Controllers\KategoriController::class, 'update'], [RequireLogin::class]);
Router::post('/edit_kategori.php', [\App\Http\Controllers\KategoriController::class, 'update'], [RequireLogin::class]);

Router::post('/kategori/{id:\d+}/hapus', [\App\Http\Controllers\KategoriController::class, 'destroy'], [RequireLogin::class]);
Router::post('/hapus_kategori.php', [\App\Http\Controllers\KategoriController::class, 'destroy'], [RequireLogin::class]);
Router::get('/hapus_kategori.php', function () {
    http_response_code(405);
    header('Allow: POST');
    echo "Metode tidak diizinkan. Penghapusan harus melalui POST.";
}, [RequireLogin::class]);

// -----------------------------------------------------------------------------
// Layanan Routes (Clean URLs & Backward Compatibility Aliases)
// -----------------------------------------------------------------------------
Router::get('/layanan', [\App\Http\Controllers\LayananController::class, 'index'], [RequireLogin::class]);
Router::get('/layanan.php', [\App\Http\Controllers\LayananController::class, 'index'], [RequireLogin::class]);

Router::get('/layanan/tambah', [\App\Http\Controllers\LayananController::class, 'create'], [RequireLogin::class]);
Router::get('/tambah_layanan.php', [\App\Http\Controllers\LayananController::class, 'create'], [RequireLogin::class]);
Router::post('/layanan/tambah', [\App\Http\Controllers\LayananController::class, 'store'], [RequireLogin::class]);
Router::post('/tambah_layanan.php', [\App\Http\Controllers\LayananController::class, 'store'], [RequireLogin::class]);

Router::get('/layanan/{id:\d+}/edit', [\App\Http\Controllers\LayananController::class, 'edit'], [RequireLogin::class]);
Router::get('/edit_layanan.php', [\App\Http\Controllers\LayananController::class, 'edit'], [RequireLogin::class]);
Router::post('/layanan/{id:\d+}/edit', [\App\Http\Controllers\LayananController::class, 'update'], [RequireLogin::class]);
Router::post('/edit_layanan.php', [\App\Http\Controllers\LayananController::class, 'update'], [RequireLogin::class]);

Router::post('/layanan/{id:\d+}/hapus', [\App\Http\Controllers\LayananController::class, 'destroy'], [RequireLogin::class]);
Router::post('/hapus_layanan.php', [\App\Http\Controllers\LayananController::class, 'destroy'], [RequireLogin::class]);
Router::get('/hapus_layanan.php', function () {
    http_response_code(405);
    header('Allow: POST');
    echo "Metode tidak diizinkan. Penghapusan harus melalui POST.";
}, [RequireLogin::class]);


