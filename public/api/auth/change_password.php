<?php

header('Content-Type: application/json; charset=utf-8');

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
    echo json_encode([
        'status'  => $status,
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
        responseJson(false, 'Kunci akses wajib dikirim', null, 401);
    }
    if (!defined('API_KEY') || API_KEY === '') {
        responseJson(false, 'Konfigurasi kunci akses server belum lengkap', null, 500);
    }
    if (!hash_equals(API_KEY, $apiKey)) {
        responseJson(false, 'Kunci akses tidak valid', null, 401);
    }
}

checkApiKey();

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method !== 'POST') {
    responseJson(false, 'Metode permintaan tidak didukung', null, 405);
}

/* =====================================================
   PROSES GANTI PASSWORD (POST)
===================================================== */
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    responseJson(false, 'Format data tidak valid', null, 400);
}

$userId = (int)($input['user_id'] ?? 0);
$currentPassword = (string)($input['current_password'] ?? '');
$newPassword = (string)($input['new_password'] ?? '');

if ($userId <= 0) {
    responseJson(false, 'Pengenal akun tidak valid', null, 400);
}

if ($currentPassword === '' || $newPassword === '') {
    responseJson(false, 'Kata sandi saat ini dan kata sandi baru wajib diisi', null, 400);
}

if (strlen($newPassword) < 6) {
    responseJson(false, 'Kata sandi baru minimal harus 6 karakter', null, 400);
}

if ($currentPassword === $newPassword) {
    responseJson(false, 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini', null, 400);
}

try {
    $stmt = $conn->prepare("
        SELECT id_admin, password, status
        FROM admin
        WHERE id_admin = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        responseJson(false, 'Akun pengguna tidak ditemukan', null, 404);
    }

    $dbPassword = (string)($user['password'] ?? '');
    $passwordValid = false;

    if ($dbPassword !== '') {
        // 1. Cek Bcrypt
        if (password_verify($currentPassword, $dbPassword)) {
            $passwordValid = true;
        }

        // 2. Fallback cek SHA-256
        if (!$passwordValid) {
            $hashedInput = hash('sha256', $currentPassword);
            if (hash_equals(strtolower($dbPassword), strtolower($hashedInput))) {
                $passwordValid = true;
            }
        }
    }

    if (!$passwordValid) {
        responseJson(false, 'Kata sandi saat ini tidak sesuai', null, 400);
    }

    // Hash kata sandi baru menggunakan Bcrypt
    $newHashed = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateStmt = $conn->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
    $updateStmt->execute([$newHashed, $userId]);

    responseJson(true, 'Kata sandi berhasil diperbarui');

} catch (PDOException $e) {
    responseJson(false, 'Terjadi kendala pada sistem. Silakan coba kembali.', null, 500);
}
