<?php

/**
 * Portal Session & SSO Cookie Helpers
 * Menyediakan sinkronisasi dua arah antara Portal Next.js dan Dashboard Admin PHP.
 */

if (!function_exists('get_portal_session_secret')) {
    function get_portal_session_secret(): string
    {
        $secret = env('SESSION_SECRET') ?: (defined('API_KEY') ? API_KEY : env('API_KEY', ''));
        if (!$secret || $secret === 'bps-solsel-internal-session-secret-key-32chars-minimum-security') {
            if (defined('APP_ENV') && APP_ENV === 'production') {
                throw new RuntimeException('FATAL CONFIGURATION ERROR: SESSION_SECRET or API_KEY must be defined in production environment.');
            }
            $secret = 'bps-solsel-internal-session-secret-key-32chars-minimum-security';
        }
        return trim((string)$secret);
    }
}

if (!function_exists('create_portal_session_token')) {
    /**
     * Membuat token sesi HMAC-SHA256 yang kompatibel dengan Next.js createSessionToken()
     */
    function create_portal_session_token(array $user, int $maxAge = 2592000): string
    {
        $now = time();
        $secret = get_portal_session_secret();
        
        $sessionId = bin2hex(random_bytes(16));
        if (function_exists('com_create_guid')) {
            $sessionId = trim(com_create_guid(), '{}');
        }

        $userId = $user['id_admin'] ?? $user['userId'] ?? $user['id'] ?? 0;
        $username = $user['username'] ?? '';
        $name = !empty($user['name']) ? $user['name'] : (!empty($user['username']) ? $user['username'] : 'Pegawai BPS');
        $email = $user['email'] ?? '';
        $role = $user['role'] ?? 'Pegawai';

        $payload = [
            'sessionId' => $sessionId,
            'userId'    => $userId,
            'username'  => $username,
            'name'      => $name,
            'email'     => $email,
            'role'      => $role,
            'iat'       => $now,
            'exp'       => $now + $maxAge,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encodedPayload = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $rawHmac = hash_hmac('sha256', $encodedPayload, $secret, true);
        $signature = rtrim(strtr(base64_encode($rawHmac), '+/', '-_'), '=');

        return $encodedPayload . '.' . $signature;
    }
}

if (!function_exists('verify_portal_session_cookie')) {
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

        $secret = get_portal_session_secret();
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
}

if (!function_exists('issue_portal_session_cookies')) {
    /**
     * Menerbitkan cookie portal bps_solsel_session & bps_solsel_auth_user
     */
    function issue_portal_session_cookies(array $userData, int $maxAge = 2592000): void
    {
        $token = create_portal_session_token($userData, $maxAge);
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        $cookieOptions = [
            'expires'  => time() + $maxAge,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        setcookie('bps_solsel_session', $token, $cookieOptions);
        $_COOKIE['bps_solsel_session'] = $token;

        $userId = (int)($userData['id_admin'] ?? $userData['userId'] ?? $userData['id'] ?? 0);
        $publicUserJson = json_encode([
            'id'        => $userId,
            'id_admin'  => $userId,
            'username'  => $userData['username'] ?? '',
            'name'      => $userData['name'] ?? ($userData['username'] ?? ''),
            'email'     => $userData['email'] ?? '',
            'role'      => $userData['role'] ?? 'Pegawai',
            'status'    => $userData['status'] ?? 'approved',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $publicCookieOptions = $cookieOptions;
        $publicCookieOptions['httponly'] = false;
        setcookie('bps_solsel_auth_user', $publicUserJson, $publicCookieOptions);
        $_COOKIE['bps_solsel_auth_user'] = $publicUserJson;

        // Hapus legacy flag cookie jika ada
        setcookie('bps_solsel_auth_verified', '', time() - 42000, '/');
        unset($_COOKIE['bps_solsel_auth_verified']);
    }
}

if (!function_exists('clear_portal_session_cookies')) {
    /**
     * Menghapus cookie portal bps_solsel_session & bps_solsel_auth_user
     */
    function clear_portal_session_cookies(): void
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        $expireOptions = [
            'expires'  => time() - 42000,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        setcookie('bps_solsel_session', '', $expireOptions);
        unset($_COOKIE['bps_solsel_session']);

        $expireOptions['httponly'] = false;
        setcookie('bps_solsel_auth_user', '', $expireOptions);
        unset($_COOKIE['bps_solsel_auth_user']);
        setcookie('bps_solsel_auth_verified', '', $expireOptions);
        unset($_COOKIE['bps_solsel_auth_verified']);
    }
}

if (!function_exists('sync_session_from_portal_user')) {
    /**
     * Sinkronisasi sesi PHP ke user ID portal yang terverifikasi
     */
    function sync_session_from_portal_user(int $userId): bool
    {
        global $conn;

        if (!$conn) {
            return false;
        }

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
            $_SESSION['auth_source'] = 'portal_sync';

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
