<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LayananService;
use App\Services\PasswordService;
use App\Support\Json;
use PDO;

class LegacyApiController extends Controller
{
    /**
     * Validate legacy API Key and return error response string if invalid.
     */
    private function verifyApiKey(): ?string
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['HTTP_API_KEY'] ?? '';

        if ($apiKey === '') {
            return Json::raw([
                'status'  => false,
                'message' => 'API key wajib dikirim',
                'data'    => null,
            ], 401);
        }

        $expectedKey = env('API_KEY_APP_B') ?: env('BPS_API_KEY');
        if (empty($expectedKey)) {
            return Json::raw([
                'status'  => false,
                'message' => 'API key server belum dikonfigurasi',
                'data'    => null,
            ], 500);
        }

        if (!hash_equals((string)$expectedKey, (string)$apiKey)) {
            return Json::raw([
                'status'  => false,
                'message' => 'API key tidak valid',
                'data'    => null,
            ], 401);
        }

        return null;
    }

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
     * Legacy GET /api/layanan.php
     */
    public function layanan(): string
    {
        if ($authError = $this->verifyApiKey()) {
            return $authError;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        try {
            if ($id > 0) {
                $stmt = $this->db()->prepare("
                    SELECT
                        l.id_layanan,
                        l.id_kategori,
                        l.akses,
                        l.vpn,
                        l.nama_layanan,
                        l.deskripsi_layanan,
                        l.url,
                        l.logo,
                        l.search_aliases,
                        l.updated_at,
                        k.nama_kategori
                    FROM layanan l
                    LEFT JOIN kategori k ON l.id_kategori = k.id_kategori
                    WHERE l.id_layanan = ? AND l.status = 'published' AND (k.is_active = 1 OR k.id_kategori IS NULL)
                    LIMIT 1
                ");
                $stmt->execute([$id]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$data) {
                    return Json::raw([
                        'status'  => false,
                        'message' => 'Data layanan tidak ditemukan',
                        'data'    => null,
                    ], 404);
                }

                $data['id_layanan'] = (int)$data['id_layanan'];
                $data['id_kategori'] = (int)$data['id_kategori'];
                $data['url'] = $this->normalizeUrl($data['url']);
                $data['aliases'] = LayananService::parseAliasesToArray($data['search_aliases'] ?? null);
                $data['updated_at'] = !empty($data['updated_at']) ? (string)$data['updated_at'] : null;
                unset($data['search_aliases']);

                return Json::raw([
                    'status'  => true,
                    'message' => 'Data layanan berhasil diambil',
                    'data'    => $data,
                ], 200);
            }

            $stmt = $this->db()->query("
                SELECT
                    l.id_layanan,
                    l.id_kategori,
                    l.akses,
                    l.vpn,
                    l.nama_layanan,
                    l.deskripsi_layanan,
                    l.url,
                    l.logo,
                    l.search_aliases,
                    l.updated_at,
                    k.nama_kategori
                FROM layanan l
                LEFT JOIN kategori k ON l.id_kategori = k.id_kategori
                WHERE l.status = 'published' AND (k.is_active = 1 OR k.id_kategori IS NULL)
                ORDER BY l.id_layanan DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as &$item) {
                $item['id_layanan'] = (int)$item['id_layanan'];
                $item['id_kategori'] = (int)$item['id_kategori'];
                $item['url'] = $this->normalizeUrl($item['url']);
                $item['akses'] = $item['akses'] ?? 'Public';
                $item['vpn'] = $item['vpn'] ?? 'No';
                $item['nama_layanan'] = $item['nama_layanan'] ?? '';
                $item['deskripsi_layanan'] = $item['deskripsi_layanan'] ?? '';
                $item['logo'] = $item['logo'] ?? '';
                $item['nama_kategori'] = $item['nama_kategori'] ?? '';
                $item['aliases'] = LayananService::parseAliasesToArray($item['search_aliases'] ?? null);
                $item['updated_at'] = !empty($item['updated_at']) ? (string)$item['updated_at'] : null;
                unset($item['search_aliases']);
            }
            unset($item);

            return Json::raw([
                'status'  => true,
                'message' => 'Data layanan berhasil diambil',
                'data'    => $data,
            ], 200);
        } catch (\Throwable $e) {
            return Json::raw([
                'status'  => false,
                'message' => 'Terjadi kesalahan pada server',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Legacy GET /api/kategori.php
     */
    public function kategori(): string
    {
        if ($authError = $this->verifyApiKey()) {
            return $authError;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        try {
            if ($id > 0) {
                $stmt = $this->db()->prepare("
                    SELECT id_kategori, nama_kategori, deskripsi
                    FROM kategori
                    WHERE id_kategori = ? AND is_active = 1
                    LIMIT 1
                ");
                $stmt->execute([$id]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$data) {
                    return Json::raw([
                        'status'  => false,
                        'message' => 'Data kategori tidak ditemukan',
                        'data'    => null,
                    ], 404);
                }

                $data['id_kategori'] = (int)$data['id_kategori'];
                return Json::raw([
                    'status'  => true,
                    'message' => 'Data kategori berhasil diambil',
                    'data'    => $data,
                ], 200);
            }

            $stmt = $this->db()->query("
                SELECT id_kategori, nama_kategori, deskripsi
                FROM kategori
                WHERE is_active = 1
                ORDER BY id_kategori ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($data as &$item) {
                $item['id_kategori'] = (int)$item['id_kategori'];
            }
            unset($item);

            return Json::raw([
                'status'  => true,
                'message' => 'Data kategori berhasil diambil',
                'data'    => $data,
            ], 200);
        } catch (\Throwable $e) {
            return Json::raw([
                'status'  => false,
                'message' => 'Terjadi kesalahan pada server',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Legacy GET /api/website.php
     */
    public function website(): string
    {
        if ($authError = $this->verifyApiKey()) {
            return $authError;
        }

        $deskripsiKategori = [
            'Distribusi' => 'Perdagangan, harga, transportasi, pariwisata, dan distribusi barang/jasa.',
            'Produksi'   => 'Pertanian, industri, pertambangan, energi, konstruksi, peternakan, kehutanan, perikanan.',
            'Neraca'     => 'PDRB, neraca wilayah, neraca produksi/pengeluaran, dan analisis ekonomi makro.',
            'Sosial'     => 'Penduduk, ketenagakerjaan, kemiskinan, pendidikan, kesehatan, sosial, dan kesejahteraan.',
            'TI'         => 'Sistem dan infrastruktur/alat BPS — bukan data statistik bidang tertentu, melainkan sistem nasional.',
            'Umum'       => 'Administrasi, kepegawaian, pengadaan, regulasi, dan urusan internal.',
            'Diseminasi' => 'Kategori dengan isi paling banyak — tugasnya menyebarkan data ke pengguna.',
        ];

        $urutanKategori = ['Distribusi', 'Produksi', 'Neraca', 'Sosial', 'TI', 'Umum', 'Diseminasi'];

        try {
            $stmt = $this->db()->query("
                SELECT
                    l.id_layanan,
                    l.id_kategori,
                    l.akses,
                    l.vpn,
                    l.nama_layanan,
                    l.url,
                    l.logo,
                    k.nama_kategori
                FROM layanan l
                LEFT JOIN kategori k ON l.id_kategori = k.id_kategori
                WHERE l.status = 'published' AND k.is_active = 1
                ORDER BY k.id_kategori ASC, l.id_layanan ASC
            ");
            $layanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $kategoriGroup = [];
            foreach ($urutanKategori as $nama) {
                $kategoriGroup[$nama] = [
                    'title'       => $nama,
                    'category'    => $nama,
                    'description' => $deskripsiKategori[$nama],
                    'links'       => [],
                ];
            }

            foreach ($layanan as $row) {
                $namaKat = $row['nama_kategori'] ?? '';
                if (!isset($kategoriGroup[$namaKat])) {
                    continue;
                }

                $kategoriGroup[$namaKat]['links'][] = [
                    'label'      => $row['nama_layanan'] ?? '',
                    'href'       => $this->normalizeUrl($row['url']),
                    'logo'       => $row['logo'] ?? '',
                    'id_layanan' => (int)($row['id_layanan'] ?? 0),
                    'akses'      => $row['akses'] ?? 'Public',
                    'vpn'        => $row['vpn'] ?? 'No',
                ];
            }

            return Json::raw([
                'status'  => true,
                'message' => 'Data website berhasil diambil',
                'data'    => array_values($kategoriGroup),
            ], 200);
        } catch (\Throwable $e) {
            return Json::raw([
                'status'  => false,
                'message' => 'Terjadi kesalahan pada server',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Legacy POST /api/auth/loginPortal.php
     */
    public function loginPortal(): string
    {
        if ($authError = $this->verifyApiKey()) {
            return $authError;
        }

        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) {
            return Json::raw([
                'status'  => false,
                'message' => 'Data JSON tidak valid',
                'data'    => null,
            ], 400);
        }

        $identity = trim($input['identity'] ?? '');
        $password = (string)($input['password_raw'] ?? $input['password'] ?? '');

        if ($identity === '' || $password === '') {
            return Json::raw([
                'status'  => false,
                'message' => 'Identity (email/username) dan password wajib diisi',
                'data'    => null,
            ], 400);
        }

        try {
            $stmt = $this->db()->prepare("
                SELECT id_admin, username, email, password, status, role, must_change_password
                FROM admin
                WHERE (email = ? OR username = ?)
                LIMIT 1
            ");
            $stmt->execute([$identity, $identity]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return Json::raw([
                    'status'  => false,
                    'message' => 'Akun tidak ditemukan',
                    'data'    => null,
                ], 404);
            }

            if ($user['status'] !== 'approved') {
                return Json::raw([
                    'status'  => false,
                    'message' => 'Akun belum disetujui atau berstatus pending',
                    'data'    => null,
                ], 403);
            }

            $passwordValid = PasswordService::verify(
                $password,
                (string)($user['password'] ?? ''),
                (int)$user['id_admin'],
                $this->db()
            );

            if (!$passwordValid) {
                return Json::raw([
                    'status'  => false,
                    'message' => 'Password salah',
                    'data'    => null,
                ], 401);
            }

            return Json::raw([
                'status'  => true,
                'message' => 'Login berhasil',
                'data'    => [
                    'id_admin'             => (int)$user['id_admin'],
                    'username'             => $user['username'],
                    'email'                => $user['email'],
                    'role'                 => $user['role'],
                    'must_change_password' => !empty($user['must_change_password']),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return Json::raw([
                'status'  => false,
                'message' => 'Terjadi kesalahan pada server',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Legacy POST /api/auth/changePassword.php
     */
    public function changePassword(): string
    {
        if ($authError = $this->verifyApiKey()) {
            return $authError;
        }

        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) {
            return Json::raw([
                'status'  => false,
                'message' => 'Data JSON tidak valid',
                'data'    => null,
            ], 400);
        }

        $identity = trim((string)($input['identity'] ?? $input['username'] ?? ''));
        $currentPassword = (string)($input['current_password'] ?? '');
        $newPassword = (string)($input['new_password'] ?? '');
        $confirmPassword = (string)($input['confirm_password'] ?? $input['password_confirmation'] ?? $newPassword);

        if ($identity === '' || $currentPassword === '' || $newPassword === '') {
            return Json::raw([
                'status'  => false,
                'message' => 'Identity, current_password, dan new_password wajib diisi',
                'data'    => null,
            ], 400);
        }

        try {
            $stmt = $this->db()->prepare("
                SELECT id_admin, username, email, password, status, role
                FROM admin
                WHERE (email = ? OR username = ?)
                LIMIT 1
            ");
            $stmt->execute([$identity, $identity]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return Json::raw([
                    'status'  => false,
                    'message' => 'Akun tidak ditemukan',
                    'data'    => null,
                ], 404);
            }

            if ($newPassword !== $confirmPassword) {
                return Json::raw([
                    'status'  => false,
                    'message' => 'Konfirmasi password baru tidak cocok',
                    'data'    => null,
                ], 422);
            }

            $policyErr = PasswordService::validatePolicy($newPassword, (string)$user['username']);
            if ($policyErr !== null) {
                return Json::raw([
                    'status'  => false,
                    'message' => $policyErr,
                    'data'    => null,
                ], 422);
            }

            $valid = PasswordService::verify(
                $currentPassword,
                (string)$user['password'],
                (int)$user['id_admin'],
                $this->db()
            );

            if (!$valid) {
                return Json::raw([
                    'status'  => false,
                    'message' => 'Password saat ini salah',
                    'data'    => null,
                ], 401);
            }

            $newHash = PasswordService::hash($newPassword);
            $stmtUp = $this->db()->prepare("
                UPDATE admin
                SET password = ?, must_change_password = 0
                WHERE id_admin = ?
            ");
            $stmtUp->execute([$newHash, (int)$user['id_admin']]);

            audit_log('change_password', 'user', (int)$user['id_admin']);

            return Json::raw([
                'status'  => true,
                'message' => 'Password berhasil diperbarui',
                'data'    => [
                    'id_admin'             => (int)$user['id_admin'],
                    'username'             => $user['username'],
                    'must_change_password' => false,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return Json::raw([
                'status'  => false,
                'message' => 'Terjadi kesalahan pada server',
                'data'    => null,
            ], 500);
        }
    }
}
