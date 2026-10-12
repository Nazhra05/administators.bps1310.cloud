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

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/* =====================================================
   AUTENTIKASI SERVER-TO-SERVER (API KEY)
===================================================== */
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey === '') {
    json_response(false, 'Header X-API-KEY wajib dikirim.', null, 401);
}

if (!defined('API_KEY') || API_KEY === '') {
    json_response(false, 'API key server belum dikonfigurasi.', null, 500);
}

if (!hash_equals(API_KEY, $apiKey)) {
    json_response(false, 'API key tidak valid.', null, 403);
}

/* =====================================================
   BACA PARAMETER USER
===================================================== */
$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true) ?: [];

$idUser = (int)($_GET['id_user'] ?? $body['id_user'] ?? 0);
$username = trim((string)($_GET['username'] ?? $body['username'] ?? ''));

if ($idUser <= 0 && $username === '') {
    json_response(false, 'Parameter id_user atau username wajib disertakan.', null, 400);
}

/* =====================================================
   VALIDASI STATUS AKUN DI BASIS DATA
===================================================== */
try {
    if ($idUser > 0) {
        $stmt = $conn->prepare("
            SELECT id_admin, username, email, first_name, last_name, role, status, email_verified
            FROM admin
            WHERE id_admin = ?
            LIMIT 1
        ");
        $stmt->execute([$idUser]);
    } else {
        $stmt = $conn->prepare("
            SELECT id_admin, username, email, first_name, last_name, role, status, email_verified
            FROM admin
            WHERE username = ?
            LIMIT 1
        ");
        $stmt->execute([$username]);
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        json_response(false, 'Akun pengguna tidak ditemukan atau telah dihapus oleh administrator.', [
            'valid'   => false,
            'revoked' => true,
            'reason'  => 'user_not_found'
        ], 404);
    }

    $status = strtolower(trim($user['status'] ?? ''));

    if ($status !== 'approved') {
        json_response(false, 'Status akun tidak aktif atau telah dinonaktifkan (' . $status . ').', [
            'valid'   => false,
            'revoked' => true,
            'status'  => $status,
            'reason'  => 'account_' . $status
        ], 403);
    }

    // Akun valid dan berstatus aktif
    json_response(true, 'Sesi akun valid dan aktif.', [
        'valid'          => true,
        'revoked'        => false,
        'id_user'        => (int) $user['id_admin'],
        'username'       => $user['username'],
        'role'           => $user['role'],
        'email_verified' => (int) ($user['email_verified'] ?? 0) === 1,
        'status'         => 'approved'
    ], 200);

} catch (PDOException $e) {
    error_log('[CHECK_SESSION_ERROR] ' . $e->getMessage());
    json_response(false, 'Terjadi kesalahan basis data pada server.', null, 500);
}
