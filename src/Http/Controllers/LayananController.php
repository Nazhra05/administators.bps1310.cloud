<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LayananService;
use App\Support\Validator;
use finfo;
use PDO;

class LayananController extends Controller
{
    /**
     * Tampilkan daftar layanan (Manage Link) dengan pencarian, filter, sorting, dan paginasi
     */
    public function index(): string
    {
        if (($_GET['action'] ?? '') === 'export') {
            $this->exportCsv();
            return '';
        }
        if (($_GET['action'] ?? '') === 'template_csv') {
            $this->downloadTemplateCsv();
            return '';
        }
        if (($_POST['action'] ?? '') === 'import_csv') {
            $this->importCsv();
            return '';
        }

        $db = $this->db();

        // 1. Parameter Filter
        $search = trim((string)($_GET['search'] ?? ''));

        $selectedKategori = null;
        if (isset($_GET['id_kategori']) && is_numeric($_GET['id_kategori']) && (int)$_GET['id_kategori'] > 0) {
            $selectedKategori = (int)$_GET['id_kategori'];
        }

        $selectedStatus = null;
        if (isset($_GET['status']) && in_array($_GET['status'], ['pending', 'published', 'draft'], true)) {
            $selectedStatus = $_GET['status'];
        }

        $selectedAkses = null;
        if (isset($_GET['akses']) && in_array($_GET['akses'], ['Public', 'Internal'], true)) {
            $selectedAkses = $_GET['akses'];
        }

        $selectedVpn = null;
        if (isset($_GET['vpn']) && in_array($_GET['vpn'], ['Yes', 'No'], true)) {
            $selectedVpn = $_GET['vpn'];
        }

        // 2. Parameter Sorting
        $allowedSorts = ['nama', 'kategori', 'status', 'created_at', 'id'];
        $sort = in_array($_GET['sort'] ?? '', $allowedSorts, true) ? $_GET['sort'] : 'id';
        $order = (strtolower((string)($_GET['order'] ?? '')) === 'asc') ? 'asc' : 'desc';

        // 3. Parameter Paginasi (10, 25, 50)
        $allowedPerPage = [10, 25, 50];
        $rawPerPage = (int)($_GET['per_page'] ?? 10);
        $perPage = in_array($rawPerPage, $allowedPerPage, true) ? $rawPerPage : 10;
        $page = max(1, (int)($_GET['page'] ?? 1));

        // Ambil kategori untuk filter dropdown (hanya aktif)
        $stmtKat = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC");
        $categories = $stmtKat ? $stmtKat->fetchAll(PDO::FETCH_ASSOC) : [];

        // Hitung pending count
        $stmtPending = $db->query("SELECT COUNT(*) FROM layanan WHERE status = 'pending'");
        $pendingCount = (int)($stmtPending ? $stmtPending->fetchColumn() : 0);

        // Filter SQL clauses
        $where = ["l.status != 'archived'"];
        $params = [];

        if ($selectedKategori !== null) {
            $where[] = "l.id_kategori = ?";
            $params[] = $selectedKategori;
        }

        if ($selectedStatus !== null) {
            $where[] = "l.status = ?";
            $params[] = $selectedStatus;
        }

        if ($selectedAkses !== null) {
            $where[] = "l.akses = ?";
            $params[] = $selectedAkses;
        }

        if ($selectedVpn !== null) {
            $where[] = "l.vpn = ?";
            $params[] = $selectedVpn;
        }

        if ($search !== '') {
            $where[] = "(l.nama_layanan LIKE ? OR l.deskripsi_layanan LIKE ? OR l.search_aliases LIKE ? OR l.url LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereSql = implode(" AND ", $where);

        // 4. Hitung Total Data (Count)
        $countSql = "
            SELECT COUNT(*) 
            FROM layanan l 
            LEFT JOIN kategori k ON l.id_kategori = k.id_kategori 
            LEFT JOIN units u ON l.unit_id = u.id
            WHERE {$whereSql}
        ";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($params);
        $totalItems = (int)$stmtCount->fetchColumn();

        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        // 5. Query Data Layanan
        $sortColumn = match ($sort) {
            'nama'       => 'l.nama_layanan',
            'kategori'   => 'k.nama_kategori',
            'status'     => 'l.status',
            'created_at' => 'l.created_at',
            default      => 'l.id_layanan',
        };
        $orderDir = ($order === 'asc') ? 'ASC' : 'DESC';

        $dataSql = "
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
                l.search_aliases,
                u.nama AS unit_name,
                k.nama_kategori 
            FROM layanan l 
            LEFT JOIN kategori k ON l.id_kategori = k.id_kategori 
            LEFT JOIN units u ON l.unit_id = u.id
            WHERE {$whereSql}
            ORDER BY {$sortColumn} {$orderDir}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmtLay = $db->prepare($dataSql);
        $stmtLay->execute($params);
        $services = $stmtLay->fetchAll(PDO::FETCH_ASSOC);

        $hasFilters = ($search !== '' || $selectedKategori !== null || $selectedStatus !== null || $selectedAkses !== null || $selectedVpn !== null);

        return $this->render('layanan/index', [
            'title'            => 'Manage Link - Kelola Layanan',
            'categories'       => $categories,
            'services'         => $services,
            'selectedKategori' => $selectedKategori,
            'selectedStatus'   => $selectedStatus,
            'selectedAkses'    => $selectedAkses,
            'selectedVpn'      => $selectedVpn,
            'search'           => $search,
            'sort'             => $sort,
            'order'            => $order,
            'hasFilters'       => $hasFilters,
            'pendingCount'     => $pendingCount,
            'pagination'       => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'from'        => $totalItems > 0 ? $offset + 1 : 0,
                'to'          => min($offset + $perPage, $totalItems),
            ],
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
            'errors'     => [],
            'form'       => [
                'nama_layanan'      => '',
                'deskripsi_layanan' => '',
                'url'               => '',
                'id_kategori'       => '',
                'akses'             => 'Public',
                'vpn'               => 'No',
                'unit_id'           => '',
                'search_aliases'    => '',
            ],
        ]);
    }

    /**
     * Simpan layanan baru dengan validasi per field
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

        $searchAliases = LayananService::normalizeAliases($_POST['search_aliases'] ?? '');

        $form = [
            'nama_layanan'      => trim($_POST['nama_layanan'] ?? ''),
            'deskripsi_layanan' => trim($_POST['deskripsi_layanan'] ?? ''),
            'url'               => trim($_POST['url'] ?? ''),
            'id_kategori'       => $_POST['id_kategori'] ?? '',
            'akses'             => $_POST['akses'] ?? 'Public',
            'vpn'               => $_POST['vpn'] ?? 'No',
            'unit_id'           => $targetUnitId,
            'search_aliases'    => $searchAliases,
        ];

        if (!csrf_verify()) {
            http_response_code(403);
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'units'      => $units,
                'user'       => $user,
                'error'      => 'Sesi keamanan tidak valid atau telah kedaluwarsa.',
                'errors'     => [],
                'form'       => $form,
            ]);
        }

        $idKategori = Validator::int($form['id_kategori'], 0, 1);
        $urlValid = Validator::url($form['url']);

        $errors = [];
        if (mb_strlen($form['nama_layanan']) < 3) {
            $errors['nama_layanan'] = 'Nama layanan wajib diisi minimal 3 karakter.';
        } elseif (mb_strlen($form['nama_layanan']) > 150) {
            $errors['nama_layanan'] = 'Nama layanan maksimal 150 karakter.';
        }

        if ($form['deskripsi_layanan'] === '') {
            $errors['deskripsi_layanan'] = 'Deskripsi layanan wajib diisi.';
        }

        if ($idKategori <= 0) {
            $errors['id_kategori'] = 'Silakan pilih kategori yang valid.';
        }

        if ($form['url'] === '') {
            $errors['url'] = 'URL / tautan layanan wajib diisi.';
        } elseif ($urlValid === null) {
            $errors['url'] = 'Format URL tidak valid. Hanya URL dengan protokol http:// atau https:// yang diperbolehkan.';
        }

        if (!in_array($form['akses'], ['Internal', 'Public'], true)) {
            $errors['akses'] = 'Pilihan akses harus Public atau Internal.';
        }

        if (!in_array($form['vpn'], ['Yes', 'No'], true)) {
            $errors['vpn'] = 'Pilihan VPN harus Ya (Yes) atau Tidak (No).';
        }

        // Upload logo jika ada
        $logo = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploaded = $this->handleLogoUpload($_FILES['logo']);
            if ($uploaded['error'] !== null) {
                $errors['logo'] = $uploaded['error'];
            } else {
                $logo = $uploaded['filename'];
            }
        } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors['logo'] = 'Terjadi kesalahan saat mengunggah file logo.';
        }

        if (!empty($errors)) {
            return $this->render('layanan/create', [
                'title'      => 'Tambah Layanan',
                'categories' => $categories,
                'units'      => $units,
                'user'       => $user,
                'error'      => 'Mohon periksa kembali input formulir yang ditandai.',
                'errors'     => $errors,
                'form'       => $form,
            ]);
        }

        // Insert ke database
        $status = ($userRole === 'ADMIN IT') ? 'published' : 'pending';
        $userId = isset($user['id']) ? (int)$user['id'] : null;

        $stmtInsert = $db->prepare("
            INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, logo, akses, vpn, status, unit_id, search_aliases, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $searchAliases,
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
            'errors'     => [],
        ]);
    }

    /**
     * Perbarui layanan dengan validasi per field
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

        $searchAliases = LayananService::normalizeAliases($_POST['search_aliases'] ?? ($layanan['search_aliases'] ?? ''));

        $form = [
            'nama_layanan'      => trim($_POST['nama_layanan'] ?? ''),
            'deskripsi_layanan' => trim($_POST['deskripsi_layanan'] ?? ''),
            'url'               => trim($_POST['url'] ?? ''),
            'id_kategori'       => $_POST['id_kategori'] ?? '',
            'akses'             => $_POST['akses'] ?? '',
            'vpn'               => $_POST['vpn'] ?? '',
            'search_aliases'    => $searchAliases,
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
                'errors'     => [],
            ]);
        }

        $idKategori = Validator::int($form['id_kategori'], 0, 1);
        $urlValid = Validator::url($form['url']);

        $errors = [];
        if (mb_strlen($form['nama_layanan']) < 3) {
            $errors['nama_layanan'] = 'Nama layanan wajib diisi minimal 3 karakter.';
        } elseif (mb_strlen($form['nama_layanan']) > 150) {
            $errors['nama_layanan'] = 'Nama layanan maksimal 150 karakter.';
        }

        if ($form['deskripsi_layanan'] === '') {
            $errors['deskripsi_layanan'] = 'Deskripsi layanan wajib diisi.';
        }

        if ($idKategori <= 0) {
            $errors['id_kategori'] = 'Silakan pilih kategori yang valid.';
        }

        if ($form['url'] === '') {
            $errors['url'] = 'URL / tautan layanan wajib diisi.';
        } elseif ($urlValid === null) {
            $errors['url'] = 'Format URL tidak valid. Hanya URL dengan protokol http:// atau https:// yang diperbolehkan.';
        }

        if (!in_array($form['akses'], ['Internal', 'Public'], true)) {
            $errors['akses'] = 'Pilihan akses harus Public atau Internal.';
        }

        if (!in_array($form['vpn'], ['Yes', 'No'], true)) {
            $errors['vpn'] = 'Pilihan VPN harus Ya (Yes) atau Tidak (No).';
        }

        $logo = $layanan['logo'] ?? '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploaded = $this->handleLogoUpload($_FILES['logo']);
            if ($uploaded['error'] !== null) {
                $errors['logo'] = $uploaded['error'];
            } else {
                // Hapus logo lama jika bukan default
                if (!empty($logo) && $logo !== 'logo_bps.jpg') {
                    $oldFile = dirname(__DIR__, 3) . '/public/uploads/' . basename($logo);
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                $logo = $uploaded['filename'];
            }
        } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors['logo'] = 'Terjadi kesalahan saat mengunggah file logo.';
        }

        if (!empty($errors)) {
            return $this->render('layanan/edit', [
                'title'      => 'Edit Layanan',
                'layanan'    => array_merge($layanan, $form),
                'categories' => $categories,
                'units'      => $units,
                'user'       => $user,
                'error'      => 'Mohon periksa kembali input formulir yang ditandai.',
                'errors'     => $errors,
            ]);
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
            SET id_kategori = ?, nama_layanan = ?, deskripsi_layanan = ?, url = ?, logo = ?, akses = ?, vpn = ?, status = ?, unit_id = ?, reject_reason = ?, search_aliases = ?, updated_by = ?
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
            $searchAliases,
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

    /**
     * Ekspor CSV daftar layanan (Task 7.16)
     */
    public function exportCsv(): void
    {
        $db = $this->db();
        $stmt = $db->query("
            SELECT l.id_layanan, l.nama_layanan, k.nama_kategori, l.url, l.akses, l.vpn, l.status,
                   l.deskripsi_layanan, l.search_aliases
            FROM layanan l
            LEFT JOIN kategori k ON l.id_kategori = k.id_kategori
            WHERE l.status != 'archived'
            ORDER BY l.id_layanan ASC
        ");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="layanan_bps_solsel_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output !== false) {
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID', 'Nama Layanan', 'Kategori', 'URL', 'Akses', 'VPN', 'Status', 'Deskripsi', 'Aliases']);
            foreach ($rows as $r) {
                fputcsv($output, [
                    $r['id_layanan'],
                    $r['nama_layanan'],
                    $r['nama_kategori'] ?? '',
                    $r['url'],
                    $r['akses'],
                    $r['vpn'],
                    $r['status'],
                    $r['deskripsi_layanan'],
                    $r['search_aliases'] ?? '',
                ]);
            }
            fclose($output);
        }
        exit;
    }

    /**
     * Unduh template CSV untuk impor layanan (Task 7.16)
     */
    public function downloadTemplateCsv(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_impor_layanan.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output !== false) {
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['nama_layanan', 'kategori', 'url', 'akses', 'vpn', 'deskripsi_layanan', 'search_aliases']);
            fputcsv($output, ['SIMPEG BPS', 'Sosial Kependudukan', 'https://simpeg.bps.go.id', 'Internal', 'Yes', 'Sistem Informasi Kepegawaian', 'kepegawaian, sdm, cuti']);
            fputcsv($output, ['Portal Publik BPS', 'Sosial Kependudukan', 'https://solokselatankab.bps.go.id', 'Public', 'No', 'Website resmi BPS Kabupaten Solok Selatan', 'bps, data statistik']);
            fclose($output);
        }
        exit;
    }

    /**
     * Impor CSV daftar layanan dengan validasi baris per baris (Task 7.16)
     */
    public function importCsv(): void
    {
        if (!csrf_verify()) {
            flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa.');
            $this->redirect('layanan.php');
        }

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Silakan pilih file CSV yang valid untuk diunggah.');
            $this->redirect('layanan.php');
        }

        $file = $_FILES['csv_file'];
        $tmpName = (string)$file['tmp_name'];
        $fileName = (string)$file['name'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            flash('error', 'Hanya berkas berformat .csv yang diperbolehkan.');
            $this->redirect('layanan.php');
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            flash('error', 'Ukuran file CSV maksimal 2 MB.');
            $this->redirect('layanan.php');
        }

        $handle = fopen($tmpName, 'r');
        if ($handle === false) {
            flash('error', 'Gagal membaca berkas CSV.');
            $this->redirect('layanan.php');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            flash('error', 'Berkas CSV kosong.');
            $this->redirect('layanan.php');
        }
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false || empty($header)) {
            fclose($handle);
            flash('error', 'Header berkas CSV tidak terbaca.');
            $this->redirect('layanan.php');
        }

        $headerMap = [];
        foreach ($header as $idx => $colName) {
            $normalized = strtolower(trim((string)$colName));
            $normalized = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $normalized) ?? $normalized;
            $headerMap[$normalized] = $idx;
        }

        $namaIdx = $headerMap['nama_layanan'] ?? $headerMap['nama'] ?? null;
        $kategoriIdx = $headerMap['kategori'] ?? $headerMap['id_kategori'] ?? null;
        $urlIdx = $headerMap['url'] ?? $headerMap['link'] ?? null;
        $aksesIdx = $headerMap['akses'] ?? null;
        $vpnIdx = $headerMap['vpn'] ?? null;
        $deskripsiIdx = $headerMap['deskripsi_layanan'] ?? $headerMap['deskripsi'] ?? null;
        $aliasesIdx = $headerMap['search_aliases'] ?? $headerMap['aliases'] ?? null;

        if ($namaIdx === null || $kategoriIdx === null || $urlIdx === null) {
            fclose($handle);
            flash('error', 'Format header CSV wajib memuat minimal kolom: nama_layanan, kategori, url.');
            $this->redirect('layanan.php');
        }

        $db = $this->db();
        // Preload active categories
        $stmtCats = $db->query("SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1");
        $categoriesList = $stmtCats ? $stmtCats->fetchAll(PDO::FETCH_ASSOC) : [];
        $categoryLookup = [];
        foreach ($categoriesList as $c) {
            $categoryLookup[strtolower(trim($c['nama_kategori']))] = (int)$c['id_kategori'];
            $categoryLookup[(string)$c['id_kategori']] = (int)$c['id_kategori'];
        }

        $user = $this->user();
        $userRole = (string)($user['role'] ?? '');
        $defaultStatus = ($userRole === 'ADMIN IT') ? 'published' : 'pending';

        $lineNum = 1;
        $importedCount = 0;
        $failedRows = [];

        $stmtInsert = $db->prepare("
            INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, akses, vpn, status, search_aliases, unit_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNum++;

            if (empty(array_filter($row, fn($val) => trim((string)$val) !== ''))) {
                continue;
            }

            $nama = trim((string)($row[$namaIdx] ?? ''));
            $kategoriRaw = trim((string)($row[$kategoriIdx] ?? ''));
            $url = trim((string)($row[$urlIdx] ?? ''));
            $akses = ucfirst(strtolower(trim((string)($row[$aksesIdx] ?? 'Public'))));
            $vpn = ucfirst(strtolower(trim((string)($row[$vpnIdx] ?? 'No'))));
            $deskripsi = trim((string)($row[$deskripsiIdx] ?? $nama));
            $aliasesRaw = trim((string)($row[$aliasesIdx] ?? ''));

            if ($nama === '') {
                $failedRows[] = "Baris {$lineNum}: Nama layanan kosong";
                continue;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $failedRows[] = "Baris {$lineNum} ({$nama}): URL tidak valid ('{$url}')";
                continue;
            }

            $catKey = strtolower($kategoriRaw);
            if (!isset($categoryLookup[$catKey])) {
                $failedRows[] = "Baris {$lineNum} ({$nama}): Kategori '{$kategoriRaw}' tidak ditemukan";
                continue;
            }
            $kategoriId = $categoryLookup[$catKey];

            if ($akses !== 'Public' && $akses !== 'Internal') {
                $akses = 'Public';
            }

            if ($vpn !== 'Yes' && $vpn !== 'No') {
                $vpn = 'No';
            }

            $normalizedAliases = null;
            if ($aliasesRaw !== '') {
                $parts = array_map('trim', explode(',', $aliasesRaw));
                $parts = array_map('strtolower', $parts);
                $parts = array_unique(array_filter($parts, fn($p) => $p !== ''));
                $normalizedAliases = implode(', ', $parts);
            }

            try {
                $stmtInsert->execute([
                    $kategoriId,
                    $nama,
                    $deskripsi,
                    $url,
                    $akses,
                    $vpn,
                    $defaultStatus,
                    $normalizedAliases,
                    $user['unit_id'] ?? null,
                ]);
                $importedCount++;
            } catch (\Throwable $e) {
                $failedRows[] = "Baris {$lineNum} ({$nama}): " . $e->getMessage();
            }
        }

        fclose($handle);

        if ($importedCount > 0) {
            audit_log('import_csv', 'layanan', null, [
                'imported' => $importedCount,
                'failed'   => count($failedRows),
            ]);
        }

        if (empty($failedRows)) {
            flash('success', "Berhasil mengimpor {$importedCount} data layanan dari CSV.");
        } elseif ($importedCount > 0) {
            $errPreview = implode('; ', array_slice($failedRows, 0, 3));
            if (count($failedRows) > 3) {
                $errPreview .= ' (dan ' . (count($failedRows) - 3) . ' baris lainnya)';
            }
            flash('warning', "Berhasil mengimpor {$importedCount} layanan. " . count($failedRows) . " baris gagal/dilewati: {$errPreview}");
        } else {
            $errPreview = implode('; ', array_slice($failedRows, 0, 4));
            flash('error', "Gagal mengimpor layanan. Terjadi kesalahan pada seluruh baris: {$errPreview}");
        }

        $this->redirect('layanan.php');
    }
}

