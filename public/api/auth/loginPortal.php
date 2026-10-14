<?php

header('Content-Type: application/json; charset=utf-8');

// Karena posisinya di api/auth/, mundurnya 3 tingkat ke root config
require_once __DIR__ . '/../../../config/config.php';

/* =====================================================
   CORS & PREFLIGHT
===================================================== */
$envOrigins = array_filter(
    array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '')))
);

$allowedOrigins = array_unique(
    array_merge(
        ['http://localhost:3000', 'http://127.0.0.1:3000'],
        $envOrigins
    )
);

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/* =====================================================
   FUNGSI RESPONSE JSON
===================================================== */
function responseJson($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    $isSuccess = ($status === true || $status === 'success');
    echo json_encode([
        'status'  => $status,
        'success' => $isSuccess,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* =====================================================
   CEK API KEY
===================================================== */
function checkApiKey() {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($apiKey === '') {
        responseJson(false, 'API key wajib dikirim', null, 401);
    }
    if (!defined('API_KEY') || API_KEY === '') {
        responseJson(false, 'API key server belum dikonfigurasi', null, 500);
    }
    if (!hash_equals(API_KEY, $apiKey)) {
        responseJson(false, 'API key tidak valid', null, 401);
    }
}

checkApiKey();

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method !== 'POST') {
    responseJson(false, 'Method tidak didukung', null, 405);
}

/* =====================================================
   PROSES LOGIN (POST)
===================================================== */
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    responseJson(false, 'Data JSON tidak valid', null, 400);
}

$identity = trim($input['identity'] ?? ''); 
$password = (string)($input['password_raw'] ?? $input['password'] ?? '');

if ($identity === '' || $password === '') {
    responseJson(false, 'Identity (email/username) dan password wajib diisi', null, 400);
}

// Cek Rate Limiting (SEC-09)
$rateLimit = check_login_rate_limit($identity);
if (!$rateLimit['allowed']) {
    $minutes = ceil($rateLimit['retry_after'] / 60);
    responseJson(false, "Terlalu banyak percobaan login gagal. Akun/IP Anda ditangguhkan sementara selama {$minutes} menit.", [
        'retry_after' => $rateLimit['retry_after']
    ], 429);
}

try {
    $stmt = $conn->prepare("
        SELECT id_admin, username, email, password, status, role, email_verified
        FROM admin
        WHERE (email = ? OR username = ?)
        LIMIT 1
    ");
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        record_login_attempt($identity, false);
        responseJson(false, 'Akun tidak ditemukan', null, 404);
    }

    if ($user['status'] !== 'approved') {
        responseJson(false, 'Akun belum disetujui atau berstatus pending', null, 403);
    }

    if (isset($user['email_verified']) && (int)$user['email_verified'] !== 1) {
        responseJson(false, 'Email belum diverifikasi. Silakan periksa kotak masuk email Anda.', null, 403);
    }

    /* =====================================================
       VERIFIKASI PASSWORD (HYBRID CHECK)
    ===================================================== */
    $dbPassword = (string)($user['password'] ?? '');
    $passwordValid = false;
    $needsRehash = false;

    if ($dbPassword !== '') {
        // 1. Cek Bcrypt untuk akun USER ($2y$12$...)
        if (password_verify($password, $dbPassword)) {
            $passwordValid = true;
            if (password_needs_rehash($dbPassword, PASSWORD_BCRYPT)) {
                $needsRehash = true;
            }
        }

        // 2. Fallback cek SHA-256 untuk akun Admin register.php
        if (!$passwordValid) {
            $hashedInput = hash('sha256', $password);
            if (hash_equals(strtolower($dbPassword), strtolower($hashedInput))) {
                $passwordValid = true;
                $needsRehash = true;
            }
        }
    }

    if (!$passwordValid) {
        record_login_attempt($identity, false);
        responseJson(false, 'Password salah', null, 401);
    }

    // Login Berhasil
    record_login_attempt($identity, true);

    // Transparent password upgrade: migrasikan hash lama atau rehash ke Bcrypt modern
    if ($needsRehash) {
        try {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $updStmt = $conn->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
            $updStmt->execute([$newHash, (int)$user['id_admin']]);
        } catch (Exception $e) {
            error_log('[PASSWORD_REHASH_ERROR] Gagal mengupgrade hash password id_admin ' . $user['id_admin'] . ': ' . $e->getMessage());
        }
    }

    responseJson(true, 'Login berhasil', [
        'id_admin' => (int)$user['id_admin'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => $user['role']
    ]);

} catch (PDOException $e) {
    responseJson(false, 'Terjadi kesalahan pada server', null, 500);
}