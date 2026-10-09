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

// -----------------------------------------------------------------------------
// Admin & User Management Routes (Clean URLs & Backward Compatibility Aliases)
// -----------------------------------------------------------------------------
Router::get('/admin', [\App\Http\Controllers\AdminController::class, 'manageAdmin'], [RequireLogin::class]);
Router::get('/kelola_admin.php', [\App\Http\Controllers\AdminController::class, 'manageAdmin'], [RequireLogin::class]);
Router::post('/admin/action', [\App\Http\Controllers\AdminController::class, 'actionAdmin'], [RequireLogin::class]);
Router::post('/kelola_admin.php', [\App\Http\Controllers\AdminController::class, 'actionAdmin'], [RequireLogin::class]);

Router::get('/admin/users', [\App\Http\Controllers\AdminController::class, 'manageUser'], [RequireLogin::class]);
Router::get('/kelola_user.php', [\App\Http\Controllers\AdminController::class, 'manageUser'], [RequireLogin::class]);
Router::post('/admin/users', [\App\Http\Controllers\AdminController::class, 'inviteUser'], [RequireLogin::class]);
Router::post('/kelola_user.php', [\App\Http\Controllers\AdminController::class, 'inviteUser'], [RequireLogin::class]);

// -----------------------------------------------------------------------------
// Pengaturan / Settings Routes (Clean URLs & Backward Compatibility Aliases)
// -----------------------------------------------------------------------------
Router::get('/pengaturan', [\App\Http\Controllers\PengaturanController::class, 'index'], [RequireLogin::class]);
Router::get('/pengaturan.php', [\App\Http\Controllers\PengaturanController::class, 'index'], [RequireLogin::class]);
Router::post('/pengaturan', [\App\Http\Controllers\PengaturanController::class, 'changePassword'], [RequireLogin::class]);
Router::post('/pengaturan.php', [\App\Http\Controllers\PengaturanController::class, 'changePassword'], [RequireLogin::class]);

// -----------------------------------------------------------------------------
// Legacy API Compatibility Routes (For App B)
// -----------------------------------------------------------------------------
Router::get('/api/layanan.php', [\App\Http\Controllers\Api\LegacyApiController::class, 'layanan']);
Router::get('/api/kategori.php', [\App\Http\Controllers\Api\LegacyApiController::class, 'kategori']);
Router::get('/api/website.php', [\App\Http\Controllers\Api\LegacyApiController::class, 'website']);
Router::post('/api/auth/loginPortal.php', [\App\Http\Controllers\Api\LegacyApiController::class, 'loginPortal']);

// -----------------------------------------------------------------------------
// API v1 Modern Routes (Lampiran C & D)
// -----------------------------------------------------------------------------
Router::get('/api/v1/health', [\App\Http\Controllers\Api\V1\ApiController::class, 'health']);
Router::get('/api/v1/layanan', [\App\Http\Controllers\Api\V1\ApiController::class, 'layanan'], [ApiKey::class]);
Router::get('/api/v1/kategori', [\App\Http\Controllers\Api\V1\ApiController::class, 'kategori'], [ApiKey::class]);
Router::post('/api/v1/auth/login', [\App\Http\Controllers\Api\V1\ApiController::class, 'login'], [ApiKey::class]);
Router::get('/api/v1/auth/session', [\App\Http\Controllers\Api\V1\ApiController::class, 'session'], [ApiKey::class]);
Router::post('/api/v1/auth/logout', [\App\Http\Controllers\Api\V1\ApiController::class, 'logout'], [ApiKey::class]);
Router::get('/api/v1/settings/public', [\App\Http\Controllers\Api\V1\ApiController::class, 'publicSettings'], [ApiKey::class]);
