<?php

header('Content-Type: application/json; charset=utf-8');

// Karena posisinya di api/auth/, mundurnya 3 tingkat ke root config
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/services/mail_service.php';

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
   PROSES REGISTRASI PEGAWAI (POST)
===================================================== */
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    responseJson(false, 'Data JSON tidak valid', null, 400);
}

$firstName = trim($input['first_name'] ?? '');
$lastName  = trim($input['last_name'] ?? '');
$username  = trim($input['username'] ?? '');
$email     = trim($input['email'] ?? '');
$password  = (string)($input['password_raw'] ?? $input['password'] ?? '');

if ($firstName === '' || $username === '' || $email === '' || $password === '') {
    responseJson(false, 'Nama depan, username, email, dan kata sandi wajib diisi', null, 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responseJson(false, 'Format email tidak valid', null, 400);
}

if (strlen($password) < 6) {
    responseJson(false, 'Kata sandi minimal 6 karakter', null, 400);
}

if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
    responseJson(false, 'Username hanya boleh mengandung huruf, angka, titik, strip, atau garis bawah', null, 400);
}

try {
    // Cek username atau email sudah terdaftar
    $stmt = $conn->prepare("
        SELECT id_admin, username, email
        FROM admin
        WHERE username = ? OR email = ?
        LIMIT 1
    ");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if (strcasecmp($existing['username'], $username) === 0) {
            responseJson(false, 'Username sudah digunakan oleh akun lain', null, 409);
        } else {
            responseJson(false, 'Email sudah terdaftar pada sistem', null, 409);
        }
    }

    $verificationToken = bin2hex(random_bytes(32));
    $verificationExpires = date('Y-m-d H:i:s', time() + (24 * 60 * 60));

    // Gunakan Bcrypt standar modern untuk pendaftaran pengguna baru
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $conn->beginTransaction();

    $stmtInsert = $conn->prepare("
        INSERT INTO admin (
            first_name,
            last_name,
            username,
            email,
            password,
            status,
            role,
            email_verified,
            verification_token,
            verification_expires
        )
        VALUES (
            ?, ?, ?, ?, ?,
            'pending',
            'USER',
            0,
            ?, ?
        )
    ");

    $stmtInsert->execute([
        $firstName,
        $lastName,
        $username,
        $email,
        $hashedPassword,
        $verificationToken,
        $verificationExpires
    ]);

    $newId = (int)$conn->lastInsertId();

    // Kirim email verifikasi (jika mailer aktif)
    $emailSent = false;
    try {
        if (function_exists('sendVerificationEmail')) {
            $emailSent = sendVerificationEmail($email, $firstName, $verificationToken);
        }
    } catch (\Throwable $mErr) {
        error_log('[registerPortal mail error]: ' . $mErr->getMessage());
    }

    $conn->commit();

    // Kirim notifikasi ke admin (non-blocking)
    try {
        if (function_exists('sendAdminRegistrationNotification')) {
            sendAdminRegistrationNotification($firstName, $lastName, $username, $email);
        }
    } catch (\Throwable $mErr2) {
        // Abaikan galat notifikasi admin
    }

    $msg = $emailSent
        ? 'Pendaftaran akun berhasil! Tautan verifikasi telah dikirim ke email Anda. Silakan verifikasi dan tunggu persetujuan administrator.'
        : 'Pendaftaran akun berhasil! Akun Anda sedang menunggu persetujuan administrator untuk aktivasi penuh.';

    responseJson(true, $msg, [
        'id_admin'       => $newId,
        'username'       => $username,
        'email'          => $email,
        'role'           => 'USER',
        'status'         => 'pending',
        'email_verified' => $emailSent ? 1 : 0
    ], 201);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('[registerPortal PDO error]: ' . $e->getMessage());
    responseJson(false, 'Terjadi kesalahan basis data saat memproses pendaftaran', null, 500);
}
