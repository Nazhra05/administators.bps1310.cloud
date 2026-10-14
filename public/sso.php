<?php

require_once __DIR__ . '/../config/config.php';

/* =====================================================
   SINGLE SIGN-ON (SSO) RECEIVER & SYNCHRONIZER
   Memvalidasi token SSO kriptografis HMAC-SHA256 dari portal
   Next.js dan menginisialisasi sesi PHP sesuai identitas akun aktif.
   Menghapus sesi lama agar akun admin tidak menempel pada user biasa.
===================================================== */

$token = trim((string)($_GET['token'] ?? ''));
$targetParam = trim((string)($_GET['target'] ?? 'layanan.php'));

$allowedTargets = [
    'layanan.php',
    'index.php',
    'tambah_layanan.php',
    'repositori_private.php',
    'kategori.php',
    'pengaturan.php'
];

// Pastikan target hanya file yang diizinkan dan terbebas dari path traversal
$target = in_array($targetParam, $allowedTargets, true) ? $targetParam : 'layanan.php';

if ($token === '') {
    header('Location: login.php?error=' . urlencode('Token otentikasi SSO tidak ditemukan.'));
    exit;
}

$parts = explode('.', $token);
if (count($parts) !== 2) {
    header('Location: login.php?error=' . urlencode('Format token otentikasi SSO tidak valid.'));
    exit;
}

require_once __DIR__ . '/../config/portal_session.php';

[$encodedPayload, $signature] = $parts;
$secret = get_portal_session_secret();

if (!$secret) {
    header('Location: login.php?error=' . urlencode('Kunci keamanan server belum terkonfigurasi.'));
    exit;
}

// 1. Verifikasi tanda tangan digital HMAC-SHA256
$expectedRaw = hash_hmac('sha256', $encodedPayload, $secret, true);
$expectedSignature = rtrim(strtr(base64_encode($expectedRaw), '+/', '-_'), '=');

if (!hash_equals($expectedSignature, $signature)) {
    header('Location: login.php?error=' . urlencode('Tanda tangan digital token SSO tidak valid atau telah dimodifikasi.'));
    exit;
}

// 2. Decode payload JSON
$payloadJson = base64_decode(strtr($encodedPayload, '-_', '+/'));
if (!$payloadJson) {
    header('Location: login.php?error=' . urlencode('Payload token SSO tidak dapat didekodekan.'));
    exit;
}

$payload = json_decode($payloadJson, true);
if (!is_array($payload) || empty($payload['userId']) || empty($payload['exp']) || empty($payload['iat'])) {
    header('Location: login.php?error=' . urlencode('Data payload token SSO tidak lengkap.'));
    exit;
}

// 3. Validasi masa berlaku (maksimal 60 detik + toleransi clock skew)
$now = time();
if ((int)$payload['exp'] < $now || abs($now - (int)$payload['iat']) > 120) {
    header('Location: login.php?error=' . urlencode('Token otentikasi SSO telah kedaluwarsa. Silakan muat ulang dan coba kembali.'));
    exit;
}

$userId = (int)$payload['userId'];

// 4. Verifikasi akun pengguna di basis data
try {
    $stmt = $conn->prepare("
        SELECT id_admin, username, first_name, last_name, email, role, status
        FROM admin
        WHERE id_admin = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: login.php?error=' . urlencode('Akun pengguna tidak ditemukan di sistem.'));
        exit;
    }

    $status = strtolower(trim((string)($user['status'] ?? '')));
    if ($status !== 'approved') {
        header('Location: login.php?error=' . urlencode('Akun Anda belum disetujui atau dinonaktifkan oleh administrator.'));
        exit;
    }

    // 5. BERSIHKAN TOTAL SESI LAMA AGAR TIDAK ADA SESI ADMIN MENEMPEL
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $accId = (int)$user['id_admin'];
    $isAdmin = (strtoupper(trim($user['role'] ?? '')) === 'ADMIN');
    $roleType = $isAdmin ? 'ADMIN' : 'USER';
    $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['username'] ?? 'User');

    $_SESSION['accounts'] = [
        $accId => [
            'id' => $accId,
            'username' => $user['username'] ?? '',
            'name' => $displayName,
            'email' => $user['email'] ?? '',
            'role' => $roleType,
            'raw_role' => $roleType
        ]
    ];
    $_SESSION['active_account_id'] = $accId;

    $_SESSION['admin_id'] = $accId;
    $_SESSION['admin_username'] = $user['username'] ?? '';
    $_SESSION['admin_name'] = $displayName;
    $_SESSION['admin_role'] = $roleType;
    $_SESSION['admin_real_role'] = $roleType;
    $_SESSION['admin_status'] = $user['status'] ?? '';
    $_SESSION['admin_email'] = $user['email'] ?? '';
    $_SESSION['active_role'] = $roleType;
    $_SESSION['auth_source'] = 'sso';

    // Pastikan sesi tersimpan sebelum redirect ke halaman tujuan
    session_write_close();

    header('Location: ' . $target);
    exit;

} catch (Exception $e) {
    header('Location: login.php?error=' . urlencode('Gagal memproses otentikasi sesi: ' . $e->getMessage()));
    exit;
}
