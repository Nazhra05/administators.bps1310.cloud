<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LayananService;
use App\Services\LoginThrottleService;
use App\Services\PasswordService;
use App\Support\Json;
use PDO;

class ApiController extends Controller
{
    /**
     * Normalize URL pattern e.g. [url](url) to url.
     */
    private function normalizeUrl(?string $url): string
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }

        if (preg_match('/^\[([^\]]+)\]\((https?:\/\/[^)]+)\)$/i', $url, $matches)) {
            return trim($matches[2]);
        }

        return $url;
    }

    /**
     * GET /api/v1/layanan
     */
    public function layanan(): string
    {
        try {
            $stmt = $this->db()->query("
                SELECT
                    l.id_layanan AS id,
                    l.nama_layanan AS name,
                    l.deskripsi_layanan AS description,
                    l.url,
                    l.logo,
                    l.akses AS access,
                    l.vpn,
                    l.id_kategori AS category_id,
                    k.nama_kategori AS category_name,
                    l.search_aliases,
                    l.updated_at
                FROM layanan l
                LEFT JOIN kategori k ON l.id_kategori = k.id_kategori
                WHERE l.status = 'published' AND (k.is_active = 1 OR k.id_kategori IS NULL)
                ORDER BY l.id_layanan DESC
            ");
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($services as &$item) {
                $item['id'] = (int)$item['id'];
                $item['category_id'] = (int)$item['category_id'];
                $item['url'] = $this->normalizeUrl($item['url']);
                $item['aliases'] = LayananService::parseAliasesToArray($item['search_aliases'] ?? null);
                $item['status'] = 'published';
                $item['updated_at'] = !empty($item['updated_at']) ? (string)$item['updated_at'] : null;
                unset($item['search_aliases']);
            }
            unset($item);

            return Json::ok($services);
        } catch (\Throwable $e) {
            return Json::error('server_error', 'Gagal memuat data layanan', 500);
        }
    }

    /**
     * GET /api/v1/kategori
     */
    public function kategori(): string
    {
        try {
            $stmt = $this->db()->query("
                SELECT
                    id_kategori AS id,
                    nama_kategori AS name,
                    deskripsi AS description
                FROM kategori
                WHERE is_active = 1
                ORDER BY id_kategori ASC
            ");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($categories as &$item) {
                $item['id'] = (int)$item['id'];
            }
            unset($item);

            return Json::ok($categories);
        } catch (\Throwable $e) {
            return Json::error('server_error', 'Gagal memuat data kategori', 500);
        }
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(array $vars = [], ?array $payload = null): string
    {
        $input = $payload ?? json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) {
            return Json::error('bad_request', 'Data JSON tidak valid', 400);
        }

        $username = trim((string)($input['username'] ?? $input['identity'] ?? ''));
        $password = (string)($input['password'] ?? '');

        if ($username === '' || $password === '') {
            return Json::error('validation_failed', 'Username dan password wajib diisi', 422);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        try {
            if (LoginThrottleService::isIpThrottled($this->db(), $ip)) {
                return Json::error('too_many_requests', 'Terlalu banyak percobaan login gagal dari IP Anda. Silakan coba lagi nanti.', 429);
            }

            $stmt = $this->db()->prepare("
                SELECT id_admin, first_name, last_name, username, email, password, status, role, unit_id, is_active, failed_attempts, locked_until
                FROM admin
                WHERE (email = ? OR username = ?)
                LIMIT 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || empty($user['password'])) {
                LoginThrottleService::handleFailedLogin($this->db(), null, $ip, $username);
                return Json::error('unauthorized', 'Kredensial login tidak valid', 401);
            }

            if (LoginThrottleService::isAccountLocked($user)) {
                PasswordService::dummyVerify();
                return Json::error('unauthorized', 'Akun sementara terkunci karena terlalu banyak percobaan gagal. Silakan coba lagi nanti.', 401);
            }

            $valid = PasswordService::verify(
                $password,
                (string)($user['password'] ?? ''),
                (int)$user['id_admin'],
                $this->db()
            );

            if (!$valid) {
                LoginThrottleService::handleFailedLogin($this->db(), $user, $ip, $username);
                return Json::error('unauthorized', 'Kredensial login tidak valid', 401);
            }

            if ($user['status'] !== 'approved' || (isset($user['is_active']) && (int)$user['is_active'] === 0)) {
                return Json::error('forbidden', 'Akun tidak aktif atau belum disetujui', 403);
            }

            LoginThrottleService::handleSuccessfulLogin($this->db(), (int)$user['id_admin'], $ip, $username);

            // Opportunistically clean up expired sessions
            try {
                $cleanStmt = $this->db()->prepare("DELETE FROM sessions WHERE expires_at < ?");
                $cleanStmt->execute([date('Y-m-d H:i:s')]);
            } catch (\Throwable) {
                // Silently ignore cleanup error
            }

            // Create 8-hour session token
            $token = bin2hex(random_bytes(32));
            $expiresAtTimestamp = time() + (8 * 3600);
            $expiresAtDb = date('Y-m-d H:i:s', $expiresAtTimestamp);
            $expiresAtIso = date('c', $expiresAtTimestamp);
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

            $sessionStmt = $this->db()->prepare("
                INSERT INTO sessions (token, user_id, expires_at, created_at, ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $sessionStmt->execute([
                $token,
                (int)$user['id_admin'],
                $expiresAtDb,
                date('Y-m-d H:i:s'),
                $ip,
                $userAgent,
            ]);

            return Json::ok([
                'token'      => $token,
                'expires_at' => $expiresAtIso,
                'user'       => [
                    'id'           => (int)$user['id_admin'],
                    'username'     => $user['username'],
                    'display_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username'],
                    'email'        => $user['email'],
                    'role'         => $user['role'] ?? 'user',
                    'unit_id'      => $user['unit_id'] !== null ? (int)$user['unit_id'] : null,
                ],
            ]);
        } catch (\Throwable $e) {
            return Json::error('server_error', 'Terjadi kesalahan sistem saat proses login', 500);
        }
    }

    /**
     * GET /api/v1/auth/session
     */
    public function session(): string
    {
        $sessionToken = trim($_SERVER['HTTP_X_SESSION'] ?? '');
        if ($sessionToken === '' && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/^Bearer\s+([a-f0-9]{64})/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $sessionToken = $matches[1];
            }
        }

        if ($sessionToken === '') {
            return Json::error('unauthorized', 'Header X-Session wajib disertakan', 401);
        }

        try {
            $stmt = $this->db()->prepare("
                SELECT s.token, s.expires_at, s.user_id,
                       a.id_admin, a.first_name, a.last_name, a.username, a.email, a.role, a.unit_id, a.is_active, a.status
                FROM sessions s
                JOIN admin a ON a.id_admin = s.user_id
                WHERE s.token = ?
                LIMIT 1
            ");
            $stmt->execute([$sessionToken]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                return Json::error('unauthorized', 'Sesi tidak valid atau telah berakhir', 401);
            }

            if (strtotime((string)$session['expires_at']) <= time()) {
                try {
                    $delStmt = $this->db()->prepare("DELETE FROM sessions WHERE token = ?");
                    $delStmt->execute([$sessionToken]);
                } catch (\Throwable) {
                }
                return Json::error('unauthorized', 'Sesi telah kedaluwarsa', 401);
            }

            if ($session['status'] !== 'approved' || (isset($session['is_active']) && (int)$session['is_active'] === 0)) {
                return Json::error('forbidden', 'Akun tidak aktif atau belum disetujui', 403);
            }

            return Json::ok([
                'token'      => $session['token'],
                'expires_at' => date('c', strtotime((string)$session['expires_at'])),
                'user'       => [
                    'id'           => (int)$session['id_admin'],
                    'username'     => $session['username'],
                    'display_name' => trim(($session['first_name'] ?? '') . ' ' . ($session['last_name'] ?? '')) ?: $session['username'],
                    'email'        => $session['email'],
                    'role'         => $session['role'] ?? 'user',
                    'unit_id'      => $session['unit_id'] !== null ? (int)$session['unit_id'] : null,
                ],
            ]);
        } catch (\Throwable $e) {
            return Json::error('server_error', 'Gagal memverifikasi sesi', 500);
        }
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(): string
    {
        $sessionToken = trim($_SERVER['HTTP_X_SESSION'] ?? '');
        if ($sessionToken === '' && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/^Bearer\s+([a-f0-9]{64})/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $sessionToken = $matches[1];
            }
        }

        if ($sessionToken !== '') {
            try {
                $stmt = $this->db()->prepare("DELETE FROM sessions WHERE token = ?");
                $stmt->execute([$sessionToken]);
            } catch (\Throwable) {
                // Silently handle
            }
        }

        http_response_code(204);
        return '';
    }

    /**
     * GET /api/v1/settings/public
     */
    public function publicSettings(): string
    {
        $whitelist = [
            'app_name',
            'contact_email',
            'contact_whatsapp',
            'service_hours',
            'helpdesk_unit',
            'portal_url',
        ];

        $defaults = [
            'app_name'         => 'Portal Internal BPS Kabupaten Solok Selatan',
            'contact_email'    => env('SMTP_FROM_EMAIL', 'bps1310@bps.go.id'),
            'contact_whatsapp' => '08116661310',
            'service_hours'    => 'Senin - Kamis: 08.00 - 15.30 WIB, Jumat: 08.00 - 16.00 WIB',
            'helpdesk_unit'    => 'Tim IPDS BPS Kabupaten Solok Selatan',
            'portal_url'       => env('APP_URL', 'http://admin.test'),
        ];

        try {
            $stmt = $this->db()->query("SELECT skey, svalue FROM settings");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];

            $result = [];
            foreach ($whitelist as $key) {
                if (isset($rows[$key]) && $rows[$key] !== '') {
                    $result[$key] = $rows[$key];
                } elseif (isset($defaults[$key])) {
                    $result[$key] = $defaults[$key];
                }
            }

            return Json::ok($result);
        } catch (\Throwable $e) {
            return Json::ok($defaults);
        }
    }

    /**
     * GET /api/v1/health
     */
    public function health(): string
    {
        try {
            $this->db()->query('SELECT 1');
            return Json::ok(['status' => 'ok']);
        } catch (\Throwable $e) {
            return Json::error('service_unavailable', 'Database connection failed', 503);
        }
    }
}
