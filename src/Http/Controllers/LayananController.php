<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use finfo;
use PDO;

class LayananController extends Controller
{
    /**
     * Tampilkan daftar layanan (Manage Link)
     */
    public function index(): string
    {
        $db = $this->db();

        $selectedKategori = null;
        if (isset($_GET['id_kategori']) && is_numeric($_GET['id_kategori']) && (int)$_GET['id_kategori'] > 0) {
            $selectedKategori = (int)$_GET['id_kategori'];
        }

        // Ambil kategori untuk filter dropdown (hanya aktif)
        $stmtKat = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC");
        $categories = $stmtKat->fetchAll(PDO::FETCH_ASSOC);

        // Query layanan (hanya non-archived)
        $sql = "
            SELECT 
                l.id_layanan, 
                l.id_kategori, 
                l.nama_layanan, 
                l.deskripsi_layanan,
                l.url, 
                l.logo, 
                l.akses, 
                l.vpn, 
                l.status,
                k.nama_kategori 
            FROM layanan l 
            LEFT JOIN kategori k ON l.id_kategori = k.id_kategori 
            WHERE l.status != 'archived'
        ";
        $params = [];

        if ($selectedKategori !== null) {
            $sql .= " AND l.id_kategori = ?";
            $params[] = $selectedKategori;
        }

        $sql .= " ORDER BY l.id_kategori ASC, l.id_layanan DESC";

        $stmtLay = $db->prepare($sql);
        $stmtLay->execute($params);
        $services = $stmtLay->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('layanan/index', [
            'title'            => 'Manage Link - Kelola Layanan',
            'categories'       => $categories,
            'services'         => $services,
            'selectedKategori' => $selectedKategori,
        ]);
    }

    /**
     * Form tambah layanan
     */
    public function create(): string
    {
        $db = $this->db();
        $stmtKat = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC");
        $categories = $stmtKat->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('layanan/create', [
            'title'      => 'Tambah Layanan',
            'categories' => $categories,
            'error'      => '',
            'form'       => [
                'nama_layanan'      => '',
                'deskripsi_layanan' => '',
                'url'               => '',
                'id_kategori'       => '',
                'akses'             => 'Public',
                'vpn'               => 'No',
            ],
        ]);
    }

    /**
     * Simpan layanan baru
     */
    public function store(): string
    {
        $db = $this->db();
        $stmtKat = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC");
        $categories = $stmtKat->fetchAll(PDO::FETCH_ASSOC);

        $form = [
            'nama_layanan'      => trim($_POST['nama_layanan'] ?? ''),
            'deskripsi_layanan' => trim($_POST['deskripsi_layanan'] ?? ''),
            'url'               => trim($_POST['url'] ?? ''),
            'id_kategori'       => $_POST['id_kategori'] ?? '',
            'akses'             => $_POST['akses'] ?? 'Public',
            'vpn'               => $_POST['vpn'] ?? 'No',
        ];

        if (!csrf_verify()) {
            http_response_code(403);
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'error'      => 'Sesi keamanan tidak valid atau telah kedaluwarsa.',
                'form'       => $form,
            ]);
        }

        if (
            $form['nama_layanan'] === '' ||
            $form['deskripsi_layanan'] === '' ||
            $form['url'] === '' ||
            $form['id_kategori'] === '' ||
            $form['akses'] === '' ||
            $form['vpn'] === ''
        ) {
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'error'      => 'Semua data wajib diisi.',
                'form'       => $form,
            ]);
        }

        if (!in_array($form['akses'], ['Internal', 'Public'], true)) {
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'error'      => 'Pilihan akses tidak valid.',
                'form'       => $form,
            ]);
        }

        if (!in_array($form['vpn'], ['Yes', 'No'], true)) {
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'error'      => 'Pilihan VPN tidak valid.',
                'form'       => $form,
            ]);
        }

        // Upload logo jika ada
        $logo = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploaded = $this->handleLogoUpload($_FILES['logo']);
            if ($uploaded['error'] !== null) {
                return $this->render('layanan/create', [
                    'title'      => 'Tambah Layanan',
                    'categories' => $categories,
                    'error'      => $uploaded['error'],
                    'form'       => $form,
                ]);
            }
            $logo = $uploaded['filename'];
        }

        // Insert ke database
        $user = $this->user();
        $userRole = (string)($user['role'] ?? '');
        $userUnitId = isset($user['unit_id']) ? (int)$user['unit_id'] : null;
        $status = ($userRole === 'ADMIN IT') ? 'published' : 'pending';
        $userId = isset($user['id']) ? (int)$user['id'] : null;

        $stmtInsert = $db->prepare("
            INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, logo, akses, vpn, status, unit_id, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([
            (int)$form['id_kategori'],
            $form['nama_layanan'],
            $form['deskripsi_layanan'],
            $form['url'],
            $logo,
            $form['akses'],
            $form['vpn'],
            $status,
            $userUnitId,
            $userId,
        ]);
        $newId = (int)$db->lastInsertId();

        audit_log('create', 'layanan', $newId, [
            'nama_layanan' => $form['nama_layanan'],
            'status'       => $status,
        ]);

        flash('success', 'Layanan berhasil ditambahkan.');
        $this->redirect('layanan.php');
    }

    /**
     * Form edit layanan
     */
    public function edit(array $params = []): string
    {
        $id = isset($params['id']) ? (int)$params['id'] : (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('layanan.php');
        }

        $db = $this->db();
        $stmt = $db->prepare("SELECT * FROM layanan WHERE id_layanan = ? AND status != 'archived'");
        $stmt->execute([$id]);
        $layanan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$layanan) {
            http_response_code(404);
            return $this->render('errors/404', [
                'title'   => 'Layanan Tidak Ditemukan',
                'message' => 'Layanan yang dicari tidak ditemukan.'
            ]);
        }

        $user = $this->user();
        $userRole = (string)($user['role'] ?? '');
        $userUnitId = isset($user['unit_id']) ? (int)$user['unit_id'] : null;

        if ($userRole !== 'ADMIN IT' && $userUnitId !== null && !empty($layanan['unit_id'])) {
            if ((int)$layanan['unit_id'] !== $userUnitId) {
                http_response_code(403);
                return $this->render('errors/403', [
                    'title'   => '403 - Akses Ditolak',
                    'message' => 'Anda hanya berhak mengelola layanan di unit kerja Anda.',
                ]);
            }
        }

        $stmtKat = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC");
        $categories = $stmtKat->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('layanan/edit', [
            'title'      => 'Edit Layanan',
            'layanan'    => $layanan,
            'categories' => $categories,
            'error'      => '',
        ]);
    }

    /**
     * Perbarui layanan
     */
    public function update(array $params = []): string
    {
        $id = isset($params['id']) ? (int)$params['id'] : (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('layanan.php');
        }

        $db = $this->db();
        $stmt = $db->prepare("SELECT * FROM layanan WHERE id_layanan = ? AND status != 'archived'");
        $stmt->execute([$id]);
        $layanan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$layanan) {
            http_response_code(404);
            return $this->render('errors/404', [
                'title'   => 'Layanan Tidak Ditemukan',
                'message' => 'Layanan yang dicari tidak ditemukan.'
            ]);
        }

        $user = $this->user();
        $userRole = (string)($user['role'] ?? '');
        $userUnitId = isset($user['unit_id']) ? (int)$user['unit_id'] : null;

        if ($userRole !== 'ADMIN IT' && $userUnitId !== null && !empty($layanan['unit_id'])) {
            if ((int)$layanan['unit_id'] !== $userUnitId) {
                http_response_code(403);
                return $this->render('errors/403', [
                    'title'   => '403 - Akses Ditolak',
                    'message' => 'Anda hanya berhak mengelola layanan di unit kerja Anda.',
                ]);
            }
        }

        $stmtKat = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC");
        $categories = $stmtKat->fetchAll(PDO::FETCH_ASSOC);

        $form = [
            'nama_layanan'      => trim($_POST['nama_layanan'] ?? ''),
            'deskripsi_layanan' => trim($_POST['deskripsi_layanan'] ?? ''),
            'url'               => trim($_POST['url'] ?? ''),
            'id_kategori'       => $_POST['id_kategori'] ?? '',
            'akses'             => $_POST['akses'] ?? '',
            'vpn'               => $_POST['vpn'] ?? '',
        ];

        if (!csrf_verify()) {
            http_response_code(403);
            return $this->render('layanan/edit', [
                'title'      => 'Edit Layanan',
                'layanan'    => array_merge($layanan, $form),
                'categories' => $categories,
                'error'      => 'Sesi keamanan tidak valid atau telah kedaluwarsa.',
            ]);
        }

        if (
            $form['nama_layanan'] === '' ||
            $form['deskripsi_layanan'] === '' ||
            $form['url'] === '' ||
            $form['id_kategori'] === '' ||
            $form['akses'] === '' ||
            $form['vpn'] === ''
        ) {
            return $this->render('layanan/edit', [
                'title'      => 'Edit Layanan',
                'layanan'    => array_merge($layanan, $form),
                'categories' => $categories,
                'error'      => 'Semua data wajib diisi.',
            ]);
        }

        $logo = $layanan['logo'] ?? '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploaded = $this->handleLogoUpload($_FILES['logo']);
            if ($uploaded['error'] !== null) {
                return $this->render('layanan/edit', [
                    'title'      => 'Edit Layanan',
                    'layanan'    => array_merge($layanan, $form),
                    'categories' => $categories,
                    'error'      => $uploaded['error'],
                ]);
            }
            // Hapus logo lama jika bukan default
            if (!empty($logo) && $logo !== 'logo_bps.jpg') {
                $oldFile = dirname(__DIR__, 2) . '/public/uploads/' . basename($logo);
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }
            $logo = $uploaded['filename'];
        }

        $newStatus = ($userRole === 'ADMIN IT') ? ($layanan['status'] ?? 'published') : 'pending';
        $userId = isset($user['id']) ? (int)$user['id'] : null;

        $stmtUpdate = $db->prepare("
            UPDATE layanan 
            SET id_kategori = ?, nama_layanan = ?, deskripsi_layanan = ?, url = ?, logo = ?, akses = ?, vpn = ?, status = ?, updated_by = ?
            WHERE id_layanan = ?
        ");
        $stmtUpdate->execute([
            (int)$form['id_kategori'],
            $form['nama_layanan'],
            $form['deskripsi_layanan'],
            $form['url'],
            $logo,
            $form['akses'],
            $form['vpn'],
            $newStatus,
            $userId,
            $id,
        ]);

        audit_log('update', 'layanan', $id, [
            'nama_layanan' => $form['nama_layanan'],
            'status'       => $newStatus,
        ]);

        flash('success', 'Layanan berhasil diperbarui.');
        $this->redirect('layanan.php');
    }

    /**
     * Hapus layanan (hanya POST dengan CSRF)
     */
    public function destroy(array $params = []): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo "Metode tidak diizinkan. Penghapusan harus melalui POST.";
            return;
        }

        if (!csrf_verify()) {
            http_response_code(403);
            echo "Token keamanan CSRF tidak valid atau telah kedaluwarsa.";
            return;
        }

        $id = isset($params['id']) ? (int)$params['id'] : (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('layanan.php');
        }

        $db = $this->db();
        $stmtDelete = $db->prepare("UPDATE layanan SET status = 'archived' WHERE id_layanan = ?");
        $stmtDelete->execute([$id]);

        audit_log('delete', 'layanan', $id);

        flash('success', 'Layanan berhasil dihapus.');
        $this->redirect('layanan.php');
    }

    /**
     * Helper validasi & penyimpanan logo upload
     * @param array<string, mixed> $file
     * @return array{filename: string, error: ?string}
     */
    private function handleLogoUpload(array $file): array
    {
        $folderUpload = dirname(__DIR__, 2) . '/public/uploads/';
        if (!is_dir($folderUpload)) {
            mkdir($folderUpload, 0755, true);
        }

        $originalName = basename((string)$file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExts, true)) {
            return ['filename' => '', 'error' => 'Format file logo tidak didukung. Gunakan JPG, PNG, atau WebP.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string)$file['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mime, $allowedMimes, true)) {
            return ['filename' => '', 'error' => 'Tipe MIME gambar logo tidak valid.'];
        }

        if ((int)$file['size'] > 2 * 1024 * 1024) {
            return ['filename' => '', 'error' => 'Ukuran file logo maksimal 2MB.'];
        }

        $newFilename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $target = $folderUpload . $newFilename;

        if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
            return ['filename' => '', 'error' => 'Gagal memindahkan file upload logo ke server.'];
        }

        return ['filename' => $newFilename, 'error' => null];
    }
}
