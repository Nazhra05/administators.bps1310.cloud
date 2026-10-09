<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Validator;
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

        $selectedStatus = null;
        if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'published', 'draft'], true)) {
            $selectedStatus = $_GET['status'];
        }

        // Ambil kategori untuk filter dropdown (hanya aktif)
        $stmtKat = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC");
        $categories = $stmtKat ? $stmtKat->fetchAll(PDO::FETCH_ASSOC) : [];

        // Hitung pending count
        $stmtPending = $db->query("SELECT COUNT(*) FROM layanan WHERE status = 'pending'");
        $pendingCount = (int)($stmtPending ? $stmtPending->fetchColumn() : 0);

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
                l.unit_id,
                l.reject_reason,
                u.nama AS unit_name,
                k.nama_kategori 
            FROM layanan l 
            LEFT JOIN kategori k ON l.id_kategori = k.id_kategori 
            LEFT JOIN units u ON l.unit_id = u.id
            WHERE l.status != 'archived'
        ";
        $params = [];

        if ($selectedKategori !== null) {
            $sql .= " AND l.id_kategori = ?";
            $params[] = $selectedKategori;
        }

        if ($selectedStatus !== null) {
            $sql .= " AND l.status = ?";
            $params[] = $selectedStatus;
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
            'selectedStatus'   => $selectedStatus,
            'pendingCount'     => $pendingCount,
            'user'             => $this->user(),
        ]);
    }

    /**
     * Form tambah layanan
     */
    public function create(): string
    {
        $db = $this->db();
        $stmtKat = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC");
        $categories = $stmtKat ? $stmtKat->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmtUnits = $db->query("SELECT id, nama FROM units ORDER BY nama ASC");
        $units = $stmtUnits ? $stmtUnits->fetchAll(PDO::FETCH_ASSOC) : [];

        return $this->render('layanan/create', [
            'title'      => 'Tambah Layanan',
            'categories' => $categories,
            'units'      => $units,
            'user'       => $this->user(),
            'error'      => '',
            'form'       => [
                'nama_layanan'      => '',
                'deskripsi_layanan' => '',
                'url'               => '',
                'id_kategori'       => '',
                'akses'             => 'Public',
                'vpn'               => 'No',
                'unit_id'           => '',
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
        $categories = $stmtKat ? $stmtKat->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmtUnits = $db->query("SELECT id, nama FROM units ORDER BY nama ASC");
        $units = $stmtUnits ? $stmtUnits->fetchAll(PDO::FETCH_ASSOC) : [];

        $user = $this->user();
        $userRole = (string)($user['role'] ?? '');
        $userUnitId = isset($user['unit_id']) ? (int)$user['unit_id'] : null;

        $targetUnitId = $userUnitId;
        if ($userRole === 'ADMIN IT') {
            if (isset($_POST['unit_id']) && is_numeric($_POST['unit_id']) && (int)$_POST['unit_id'] > 0) {
                $targetUnitId = (int)$_POST['unit_id'];
            } else {
                $targetUnitId = null;
            }
        }

        $form = [
            'nama_layanan'      => trim($_POST['nama_layanan'] ?? ''),
            'deskripsi_layanan' => trim($_POST['deskripsi_layanan'] ?? ''),
            'url'               => trim($_POST['url'] ?? ''),
            'id_kategori'       => $_POST['id_kategori'] ?? '',
            'akses'             => $_POST['akses'] ?? 'Public',
            'vpn'               => $_POST['vpn'] ?? 'No',
            'unit_id'           => $targetUnitId,
        ];

        if (!csrf_verify()) {
            http_response_code(403);
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'units'      => $units,
                'user'       => $user,
                'error'      => 'Sesi keamanan tidak valid atau telah kedaluwarsa.',
                'form'       => $form,
            ]);
        }

        $idKategori = Validator::int($form['id_kategori'], 0, 1);
        $urlValid = Validator::url($form['url']);

        if (
            $form['nama_layanan'] === '' ||
            $form['deskripsi_layanan'] === '' ||
            $form['url'] === '' ||
            $idKategori <= 0 ||
            $form['akses'] === '' ||
            $form['vpn'] === ''
        ) {
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'units'      => $units,
                'user'       => $user,
                'error'      => 'Semua data wajib diisi.',
                'form'       => $form,
            ]);
        }

        if ($urlValid === null) {
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'units'      => $units,
                'user'       => $user,
                'error'      => 'Format URL tidak valid. Hanya URL dengan protokol http:// atau https:// yang diperbolehkan.',
                'form'       => $form,
            ]);
        }

        if (!in_array($form['akses'], ['Internal', 'Public'], true)) {
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'units'      => $units,
                'user'       => $user,
                'error'      => 'Pilihan akses tidak valid.',
                'form'       => $form,
            ]);
        }

        if (!in_array($form['vpn'], ['Yes', 'No'], true)) {
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'units'      => $units,
                'user'       => $user,
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
                    'units'      => $units,
                    'user'       => $user,
                    'error'      => $uploaded['error'],
                    'form'       => $form,
                ]);
            }
            $logo = $uploaded['filename'];
        }

        // Insert ke database
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
            $targetUnitId,
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
        $id = Validator::int($params['id'] ?? $_GET['id'] ?? 0);

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

        if ($userRole !== 'ADMIN IT') {
            if ($userUnitId === null || (int)($layanan['unit_id'] ?? 0) !== $userUnitId) {
                http_response_code(403);
                return $this->render('errors/403', [
                    'title'   => '403 - Akses Ditolak',
                    'message' => 'Anda hanya berhak mengelola layanan di unit kerja Anda.',
                ]);
            }
        }

        $stmtKat = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC");
        $categories = $stmtKat ? $stmtKat->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmtUnits = $db->query("SELECT id, nama FROM units ORDER BY nama ASC");
        $units = $stmtUnits ? $stmtUnits->fetchAll(PDO::FETCH_ASSOC) : [];

        return $this->render('layanan/edit', [
            'title'      => 'Edit Layanan',
            'layanan'    => $layanan,
            'categories' => $categories,
            'units'      => $units,
            'user'       => $user,
            'error'      => '',
        ]);
    }

    /**
     * Perbarui layanan
     */
    public function update(array $params = []): string
    {
        $id = Validator::int($params['id'] ?? $_POST['id'] ?? $_GET['id'] ?? 0);

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

        if ($userRole !== 'ADMIN IT') {
            if ($userUnitId === null || (int)($layanan['unit_id'] ?? 0) !== $userUnitId) {
                http_response_code(403);
                return $this->render('errors/403', [
                    'title'   => '403 - Akses Ditolak',
                    'message' => 'Anda hanya berhak mengelola layanan di unit kerja Anda.',
                ]);
            }
        }

        $stmtKat = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC");
        $categories = $stmtKat ? $stmtKat->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmtUnits = $db->query("SELECT id, nama FROM units ORDER BY nama ASC");
        $units = $stmtUnits ? $stmtUnits->fetchAll(PDO::FETCH_ASSOC) : [];

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
                'units'      => $units,
                'user'       => $user,
                'error'      => 'Sesi keamanan tidak valid atau telah kedaluwarsa.',
            ]);
        }

        $idKategori = Validator::int($form['id_kategori'], 0, 1);
        $urlValid = Validator::url($form['url']);

        if (
            $form['nama_layanan'] === '' ||
            $form['deskripsi_layanan'] === '' ||
            $form['url'] === '' ||
            $idKategori <= 0 ||
            $form['akses'] === '' ||
            $form['vpn'] === ''
        ) {
            return $this->render('layanan/edit', [
                'title'      => 'Edit Layanan',
                'layanan'    => array_merge($layanan, $form),
                'categories' => $categories,
                'units'      => $units,
                'user'       => $user,
                'error'      => 'Semua data wajib diisi.',
            ]);
        }

        if ($urlValid === null) {
            return $this->render('layanan/edit', [
                'title'      => 'Edit Layanan',
                'layanan'    => array_merge($layanan, $form),
                'categories' => $categories,
                'units'      => $units,
                'user'       => $user,
                'error'      => 'Format URL tidak valid. Hanya URL dengan protokol http:// atau https:// yang diperbolehkan.',
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
                    'units'      => $units,
                    'user'       => $user,
                    'error'      => $uploaded['error'],
                ]);
            }
            // Hapus logo lama jika bukan default
            if (!empty($logo) && $logo !== 'logo_bps.jpg') {
                $oldFile = dirname(__DIR__, 3) . '/public/uploads/' . basename($logo);
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }
            $logo = $uploaded['filename'];
        }

        $targetUnitId = isset($layanan['unit_id']) ? (int)$layanan['unit_id'] : null;
        if ($userRole === 'ADMIN IT') {
            if (isset($_POST['unit_id']) && is_numeric($_POST['unit_id']) && (int)$_POST['unit_id'] > 0) {
                $targetUnitId = (int)$_POST['unit_id'];
            } elseif (isset($_POST['unit_id']) && $_POST['unit_id'] === '') {
                $targetUnitId = null;
            }
        }

        $newStatus = ($userRole === 'ADMIN IT') ? ($layanan['status'] ?? 'published') : 'pending';
        // Saat disubmit ulang oleh leader, reject_reason di-reset agar bersih
        $newRejectReason = ($userRole === 'ADMIN IT') ? ($layanan['reject_reason'] ?? null) : null;
        $userId = isset($user['id']) ? (int)$user['id'] : null;

        $stmtUpdate = $db->prepare("
            UPDATE layanan 
            SET id_kategori = ?, nama_layanan = ?, deskripsi_layanan = ?, url = ?, logo = ?, akses = ?, vpn = ?, status = ?, unit_id = ?, reject_reason = ?, updated_by = ?
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
            $targetUnitId,
            $newRejectReason,
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

        $id = Validator::int($params['id'] ?? $_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('layanan.php');
        }

        $db = $this->db();
        $stmt = $db->prepare("SELECT * FROM layanan WHERE id_layanan = ? AND status != 'archived'");
        $stmt->execute([$id]);
        $layanan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$layanan) {
            flash('error', 'Layanan tidak ditemukan.');
            $this->redirect('layanan.php');
        }

        $user = $this->user();
        $userRole = (string)($user['role'] ?? '');
        $userUnitId = isset($user['unit_id']) ? (int)$user['unit_id'] : null;

        if ($userRole !== 'ADMIN IT') {
            if ($userUnitId === null || (int)($layanan['unit_id'] ?? 0) !== $userUnitId) {
                http_response_code(403);
                echo "Anda hanya berhak mengelola layanan di unit kerja Anda.";
                return;
            }
        }

        $stmtDelete = $db->prepare("UPDATE layanan SET status = 'archived' WHERE id_layanan = ?");
        $stmtDelete->execute([$id]);

        audit_log('delete', 'layanan', $id);

        flash('success', 'Layanan berhasil dihapus.');
        $this->redirect('layanan.php');
    }

    /**
     * Persetujuan / Penolakan Layanan oleh Admin IT (Task 7.3)
     */
    public function updateStatus(array $params = []): void
    {
        $user = $this->user();
        if (!$user || ($user['role'] ?? '') !== 'ADMIN IT') {
            http_response_code(403);
            echo "Anda tidak memiliki hak untuk menyetujui atau menolak layanan.";
            return;
        }

        if (!csrf_verify()) {
            flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa.');
            $this->redirect('layanan.php');
        }

        $id = Validator::int($params['id'] ?? $_POST['id'] ?? 0);
        $action = (string)($_POST['action'] ?? '');

        if ($id <= 0) {
            flash('error', 'ID layanan tidak valid.');
            $this->redirect('layanan.php');
        }

        $db = $this->db();
        $stmt = $db->prepare("SELECT * FROM layanan WHERE id_layanan = ? AND status != 'archived'");
        $stmt->execute([$id]);
        $layanan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$layanan) {
            flash('error', 'Layanan tidak ditemukan.');
            $this->redirect('layanan.php');
        }

        $userId = (int)($user['id'] ?? 0);

        if ($action === 'approve') {
            $stmtUp = $db->prepare("
                UPDATE layanan 
                SET status = 'published', reject_reason = NULL, updated_by = ? 
                WHERE id_layanan = ?
            ");
            $stmtUp->execute([$userId, $id]);

            audit_log('approve', 'layanan', $id, [
                'nama_layanan' => $layanan['nama_layanan'],
                'status'       => 'published',
            ]);

            flash('success', "Layanan '{$layanan['nama_layanan']}' berhasil disetujui dan dipublikasikan.");
        } elseif ($action === 'reject') {
            $reason = trim((string)($_POST['reject_reason'] ?? ''));
            if ($reason === '') {
                flash('error', 'Alasan penolakan wajib diisi agar pengaju dapat memperbaiki layanan.');
                $this->redirect('layanan.php');
            }

            $stmtUp = $db->prepare("
                UPDATE layanan 
                SET status = 'draft', reject_reason = ?, updated_by = ? 
                WHERE id_layanan = ?
            ");
            $stmtUp->execute([substr($reason, 0, 255), $userId, $id]);

            audit_log('reject', 'layanan', $id, [
                'nama_layanan'  => $layanan['nama_layanan'],
                'status'        => 'draft',
                'reject_reason' => $reason,
            ]);

            flash('success', "Layanan '{$layanan['nama_layanan']}' ditolak dan dikembalikan ke status draft.");
        } else {
            flash('error', 'Aksi tidak valid.');
        }

        $this->redirect('layanan.php');
    }

    /**
     * Helper validasi & penyimpanan logo upload
     * @param array<string, mixed> $file
     * @return array{filename: string, error: ?string}
     */
    private function handleLogoUpload(array $file): array
    {
        $folderUpload = dirname(__DIR__, 3) . '/public/uploads/';
        if (!is_dir($folderUpload)) {
            mkdir($folderUpload, 0755, true);
        }

        $originalName = basename((string)($file['name'] ?? ''));
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExts, true)) {
            return ['filename' => '', 'error' => 'Format file logo tidak didukung. Gunakan JPG, PNG, atau WebP.'];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !file_exists($tmpPath)) {
            return ['filename' => '', 'error' => 'File upload tidak valid.'];
        }

        if ((int)($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return ['filename' => '', 'error' => 'Ukuran file logo maksimal 2MB.'];
        }

        $normalizedTmp = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tmpPath);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = @$finfo->file($normalizedTmp) ?: '';
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mime, $allowedMimes, true)) {
            return ['filename' => '', 'error' => 'Tipe MIME gambar logo tidak valid.'];
        }

        // Re-process image through GD to strip malicious EXIF/polyglot PHP payloads
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png'  => @imagecreatefrompng($tmpPath),
            'image/webp' => @imagecreatefromwebp($tmpPath),
            default      => false,
        };

        if ($image === false) {
            return ['filename' => '', 'error' => 'Gambar logo rusak atau tidak valid.'];
        }

        $safeExt = ($ext === 'jpeg') ? 'jpg' : $ext;
        $newFilename = bin2hex(random_bytes(16)) . '.' . $safeExt;
        $target = $folderUpload . $newFilename;

        $saved = match ($safeExt) {
            'jpg'   => imagejpeg($image, $target, 90),
            'png'   => imagepng($image, $target, 8),
            'webp'  => imagewebp($image, $target, 90),
            default => false,
        };

        imagedestroy($image);

        if (!$saved || !file_exists($target)) {
            return ['filename' => '', 'error' => 'Gagal mengolah dan menyimpan file upload logo ke server.'];
        }

        return ['filename' => $newFilename, 'error' => null];
    }
}
