<?php

require_once __DIR__ . '/config.php';

/* =====================================================
   PORTAL SESSION AUTO-SYNC (SSO SYNCHRONIZATION)
===================================================== */

/**
 * Verifikasi token sesi portal dari cookie bps_solsel_session
 */
function verify_portal_session_cookie(): ?array
{
    $rawCookie = $_COOKIE['bps_solsel_session'] ?? '';
    if (!$rawCookie || !is_string($rawCookie)) {
        return null;
    }

    $parts = explode('.', trim($rawCookie));
    if (count($parts) !== 2) {
        return null;
    }

    [$encodedPayload, $signature] = $parts;
    if (!$encodedPayload || !$signature) {
        return null;
    }

    $secret = defined('API_KEY') ? API_KEY : env('API_KEY', '');
    if (!$secret) {
        return null;
    }

    $expectedRaw = hash_hmac('sha256', $encodedPayload, $secret, true);
    $expectedSignature = rtrim(strtr(base64_encode($expectedRaw), '+/', '-_'), '=');

    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }

    $json = base64_decode(strtr($encodedPayload, '-_', '+/'));
    if (!$json) {
        return null;
    }

    $payload = json_decode($json, true);
    if (!is_array($payload) || empty($payload['userId']) || empty($payload['exp'])) {
        return null;
    }

    if ((int)$payload['exp'] <= time()) {
        return null;
    }

    return $payload;
}

/**
 * Sinkronisasi sesi PHP ke user ID portal yang terverifikasi
 */
function sync_session_from_portal_user(int $userId): bool
{
    global $conn;

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
            return false;
        }

        $status = strtolower(trim((string)($user['status'] ?? '')));
        if ($status !== 'approved') {
            return false;
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

        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Sinkronisasi otomatis dari portal Next.js jika cookie bps_solsel_session tersedia
$portalSession = verify_portal_session_cookie();
if ($portalSession && !empty($portalSession['userId'])) {
    $portalUserId = (int)$portalSession['userId'];
    // Jika sesi PHP kosong ATAU sesi aktif berbeda dengan akun portal yang sedang aktif,
    // segera re-sinkronisasi ke akun portal aktif (mencegah akun admin lama menempel)
    if (empty($_SESSION['admin_id']) || (int)$_SESSION['admin_id'] !== $portalUserId) {
        sync_session_from_portal_user($portalUserId);
    }
}

/* =====================================================
   AUTHENTICATION CHECK
===================================================== */

if (empty($_SESSION['admin_id'])) {
    if (
        str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/') ||
        (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    ) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'Sesi login tidak valid atau telah berakhir. Silakan login kembali.'
        ]);
        exit;
    }

    header('Location: login.php');
    exit;
}

// Cek apakah akun yang sedang aktif di-nonaktifkan oleh administrator
if (!empty($_SESSION['admin_id'])) {
    try {
        $checkStatusStmt = $conn->prepare("SELECT status FROM admin WHERE id_admin = ? LIMIT 1");
        $checkStatusStmt->execute([(int) $_SESSION['admin_id']]);
        $sessionUserStatus = strtolower(trim((string) $checkStatusStmt->fetchColumn()));
        if ($sessionUserStatus === 'inactive' || $sessionUserStatus === 'nonaktif' || $sessionUserStatus === 'rejected') {
            session_unset();
            session_destroy();
            header('Location: login.php?error=' . urlencode('Akun Anda telah dinonaktifkan oleh administrator.'));
            exit;
        }
    } catch (Exception $e) {
        // fail-safe
    }
}

function auth_id(): ?int
{
    return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
}

function auth_username(): string
{
    return (string) ($_SESSION['admin_username'] ?? 'Admin');
}

/**
 * Mendapatkan role asli akun dari DB / Session (ADMIN / USER).
 * Akun yang mendaftar mandiri (register) memiliki role asli USER.
 */
function auth_real_role(): string
{
    global $conn;

    if (!empty($_SESSION['admin_real_role'])) {
        return (strtoupper(trim($_SESSION['admin_real_role'])) === 'ADMIN') ? 'ADMIN' : 'USER';
    }

    if (!empty($_SESSION['admin_id'])) {
        try {
            $stmt = $conn->prepare("SELECT role FROM admin WHERE id_admin = ? LIMIT 1");
            $stmt->execute([(int) $_SESSION['admin_id']]);
            $dbRole = $stmt->fetchColumn();
            $realRole = (strtoupper(trim($dbRole ?: '')) === 'ADMIN') ? 'ADMIN' : 'USER';
            $_SESSION['admin_real_role'] = $realRole;
            return $realRole;
        } catch (Exception $e) {
            // fallback
        }
    }

    return 'USER';
}

/**
 * Apakah akun yang sedang login aslinya ber-role ADMIN?
 * Khusus akun ADMIN yang memiliki hak akses switch role (ke User / kembali ke Admin).
 */
function is_admin_user(): bool
{
    return auth_real_role() === 'ADMIN';
}

/**
 * Role aktif tampilan saat ini.
 * Jika akun aslinya adalah USER biasa (daftar sendiri), active_role SELALU 'USER'.
 */
function auth_active_role(): string
{
    if (!is_admin_user()) {
        $_SESSION['active_role'] = 'USER';
        $_SESSION['admin_role'] = 'USER';
        return 'USER';
    }

    $active = strtoupper(trim($_SESSION['active_role'] ?? 'ADMIN'));
    $role = ($active === 'USER') ? 'USER' : 'ADMIN';
    $_SESSION['active_role'] = $role;
    $_SESSION['admin_role'] = $role;
    return $role;
}

/* =====================================================
   MULTI-ACCOUNT & ROLE TOGGLE HELPERS
===================================================== */

function get_logged_in_accounts(): array
{
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    $realRole = auth_real_role();
    $activeRole = auth_active_role();

    // Hanya kelola akun sendiri saat ini agar admin tidak melihat akun user lain
    return [
        $id => [
            'id' => $id,
            'username' => $_SESSION['admin_username'] ?? 'Admin',
            'name' => $_SESSION['admin_name'] ?? ($_SESSION['admin_username'] ?? 'Admin'),
            'email' => $_SESSION['admin_email'] ?? '',
            'role' => $activeRole,
            'raw_role' => $realRole
        ]
    ];
}

function get_active_account(): ?array
{
    $accounts = get_logged_in_accounts();
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    return $accounts[$id] ?? (reset($accounts) ?: null);
}

function switch_to_account(int $accountId): bool
{
    // Dinonaktifkan: Admin tidak boleh beralih ke akun user lain
    return false;
}

function toggle_active_role(string $targetRole): bool
{
    // Khusus admin asli
    if (!is_admin_user()) {
        return false;
    }

    $role = (strtoupper(trim($targetRole)) === 'USER') ? 'USER' : 'ADMIN';
    $_SESSION['active_role'] = $role;
    $_SESSION['admin_role'] = $role;
    return true;
}