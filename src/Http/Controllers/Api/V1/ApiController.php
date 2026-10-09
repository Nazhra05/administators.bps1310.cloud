<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
                    k.nama_kategori AS category_name
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
                $item['aliases'] = [];
                $item['status'] = 'published';
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
    public function login(): string
    {
        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) {
            return Json::error('bad_request', 'Data JSON tidak valid', 400);
        }

        $username = trim($input['username'] ?? $input['identity'] ?? '');
        $password = (string)($input['password'] ?? '');

        if ($username === '' || $password === '') {
            return Json::error('validation_failed', 'Username dan password wajib diisi', 422);
        }

        try {
            $stmt = $this->db()->prepare("
                SELECT id_admin, first_name, last_name, username, email, password, status, role
                FROM admin
                WHERE (email = ? OR username = ?)
                LIMIT 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                PasswordService::dummyVerify();
                return Json::error('unauthorized', 'Kredensial login tidak valid', 401);
            }

            if ($user['status'] !== 'approved') {
                return Json::error('forbidden', 'Akun belum disetujui atau masih pending', 403);
            }

            $valid = PasswordService::verify(
                $password,
                (string)($user['password'] ?? ''),
                (int)$user['id_admin'],
                $this->db()
            );

            if (!$valid) {
                return Json::error('unauthorized', 'Kredensial login tidak valid', 401);
            }

            $token = bin2hex(random_bytes(32));
            $expiresAt = date('c', time() + (7 * 24 * 3600));

            return Json::ok([
                'token'      => $token,
                'expires_at' => $expiresAt,
                'user'       => [
                    'id'           => (int)$user['id_admin'],
                    'username'     => $user['username'],
                    'display_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username'],
                    'email'        => $user['email'],
                    'role'         => $user['role'],
                    'unit_id'      => null,
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
        $sessionToken = $_SERVER['HTTP_X_SESSION'] ?? '';
        if (empty($sessionToken)) {
            return Json::error('unauthorized', 'Header X-Session wajib disertakan', 401);
        }

        return Json::ok([
            'authenticated' => true,
            'session_token' => $sessionToken,
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(): string
    {
        http_response_code(204);
        return '';
    }

    /**
     * GET /api/v1/settings/public
     */
    public function publicSettings(): string
    {
        return Json::ok([
            'app_name'      => 'Portal Internal BPS Kabupaten Solok Selatan',
            'contact_email' => env('SMTP_FROM_EMAIL', 'bps1310@bps.go.id'),
            'portal_url'    => env('APP_URL', 'http://admin.test'),
        ]);
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
