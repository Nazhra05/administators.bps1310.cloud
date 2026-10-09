<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Validator;
use PDO;

class KategoriController extends Controller
{
    /**
     * Tampilkan daftar kategori dengan pencarian, sorting, dan paginasi
     */
    public function index(): string
    {
        $db = $this->db();

        // 1. Parameter Filter & Search
        $search = trim((string)($_GET['search'] ?? ''));

        // 2. Parameter Sorting
        $allowedSorts = ['nama', 'layanan', 'id'];
        $sort = in_array($_GET['sort'] ?? '', $allowedSorts, true) ? $_GET['sort'] : 'id';
        $order = (strtolower((string)($_GET['order'] ?? '')) === 'desc') ? 'desc' : 'asc';

        // 3. Parameter Paginasi
        $allowedPerPage = [10, 25, 50];
        $rawPerPage = (int)($_GET['per_page'] ?? 10);
        $perPage = in_array($rawPerPage, $allowedPerPage, true) ? $rawPerPage : 10;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $where = ["k.is_active = 1"];
        $params = [];

        if ($search !== '') {
            $where[] = "k.nama_kategori LIKE ?";
            $params[] = "%{$search}%";
        }

        $whereSql = implode(" AND ", $where);

        // 4. Hitung Total Kategori (Count)
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM kategori k WHERE {$whereSql}");
        $stmtCount->execute($params);
        $totalItems = (int)$stmtCount->fetchColumn();

        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        // 5. Query Data Kategori
        $sortColumn = match ($sort) {
            'nama'    => 'k.nama_kategori',
            'layanan' => 'jumlah_layanan',
            default   => 'k.id_kategori',
        };
        $orderDir = ($order === 'desc') ? 'DESC' : 'ASC';

        $dataSql = "
            SELECT 
                k.id_kategori,
                k.nama_kategori,
                COUNT(l.id_layanan) AS jumlah_layanan
            FROM kategori k
            LEFT JOIN layanan l ON k.id_kategori = l.id_kategori AND l.status != 'archived'
            WHERE {$whereSql}
            GROUP BY k.id_kategori, k.nama_kategori
            ORDER BY {$sortColumn} {$orderDir}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $db->prepare($dataSql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil preview layanan per kategori (hanya non-archived)
        $servicesByCategory = [];
        if (!empty($categories)) {
            $catIds = array_column($categories, 'id_kategori');
            $inPlaceholders = implode(',', array_fill(0, count($catIds), '?'));
            $stmtServices = $db->prepare("
                SELECT id_layanan, id_kategori, nama_layanan, url
                FROM layanan
                WHERE status != 'archived' AND id_kategori IN ({$inPlaceholders})
                ORDER BY id_kategori ASC, id_layanan ASC
            ");
            $stmtServices->execute($catIds);
            $allServices = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

            foreach ($allServices as $srv) {
                $catId = (int)$srv['id_kategori'];
                if (!isset($servicesByCategory[$catId])) {
                    $servicesByCategory[$catId] = [];
                }
                if (count($servicesByCategory[$catId]) < 3) {
                    $servicesByCategory[$catId][] = $srv;
                }
            }
        }

        $hasFilters = ($search !== '');

        return $this->render('kategori/index', [
            'title'              => 'Kelola Kategori',
            'categories'         => $categories,
            'servicesByCategory' => $servicesByCategory,
            'search'             => $search,
            'sort'               => $sort,
            'order'              => $order,
            'hasFilters'         => $hasFilters,
            'pagination'         => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'from'        => $totalItems > 0 ? $offset + 1 : 0,
                'to'          => min($offset + $perPage, $totalItems),
            ],
            'user'               => $this->user(),
        ]);
    }

    /**
     * Form tambah kategori
     */
    public function create(): string
    {
        return $this->render('kategori/create', [
            'title'         => 'Tambah Kategori',
            'error'         => '',
            'nama_kategori' => '',
        ]);
    }

    /**
     * Simpan kategori baru
     */
    public function store(): string
    {
        if (!csrf_verify()) {
            http_response_code(403);
            return $this->render('kategori/create', [
                'title'         => 'Tambah Kategori',
                'error'         => 'Sesi keamanan tidak valid atau telah kedaluwarsa.',
                'nama_kategori' => $_POST['nama_kategori'] ?? '',
            ]);
        }

        $namaKategori = trim($_POST['nama_kategori'] ?? '');

        if ($namaKategori === '') {
            return $this->render('kategori/create', [
                'title'         => 'Tambah Kategori',
                'error'         => 'Nama kategori wajib diisi.',
                'nama_kategori' => '',
            ]);
        }

        $db = $this->db();

        // Cek duplikasi (termasuk yang diarsip/soft-deleted)
        $stmt = $db->prepare("SELECT id_kategori, is_active FROM kategori WHERE nama_kategori = ? LIMIT 1");
        $stmt->execute([$namaKategori]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ((int)$existing['is_active'] === 1) {
                return $this->render('kategori/create', [
                    'title'         => 'Tambah Kategori',
                    'error'         => 'Kategori tersebut sudah ada.',
                    'nama_kategori' => $namaKategori,
                ]);
            }

            // Jika pernah di-soft-delete, re-aktifkan kembali
            $stmtReactivate = $db->prepare("UPDATE kategori SET is_active = 1 WHERE id_kategori = ?");
            $stmtReactivate->execute([(int)$existing['id_kategori']]);

            flash('success', 'Kategori baru berhasil ditambahkan.');
            $this->redirect('kategori.php');
        }

        // Insert
        $stmtInsert = $db->prepare("INSERT INTO kategori (nama_kategori, is_active) VALUES (?, 1)");
        $stmtInsert->execute([$namaKategori]);
        $newId = (int)$db->lastInsertId();

        audit_log('create', 'kategori', $newId, ['nama_kategori' => $namaKategori]);

        flash('success', 'Kategori baru berhasil ditambahkan.');
        $this->redirect('kategori.php');
    }

    /**
     * Form edit kategori
     */
    public function edit(array $params = []): string
    {
        $id = Validator::int($params['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('kategori.php');
        }

        $db = $this->db();
        $stmt = $db->prepare("SELECT * FROM kategori WHERE id_kategori = ? AND is_active = 1");
        $stmt->execute([$id]);
        $kategori = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$kategori) {
            http_response_code(404);
            return $this->render('errors/404', [
                'title'   => 'Kategori Tidak Ditemukan',
                'message' => 'Kategori yang dicari tidak ditemukan.'
            ]);
        }

        return $this->render('kategori/edit', [
            'title'    => 'Edit Kategori',
            'kategori' => $kategori,
            'error'    => '',
        ]);
    }

    /**
     * Perbarui kategori
     */
    public function update(array $params = []): string
    {
        $id = Validator::int($params['id'] ?? $_POST['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('kategori.php');
        }

        if (!csrf_verify()) {
            http_response_code(403);
            die('Sesi keamanan tidak valid atau telah kedaluwarsa.');
        }

        $namaKategori = Validator::string($_POST['nama_kategori'] ?? '', 100);

        if ($namaKategori === '') {
            return $this->render('kategori/edit', [
                'title'    => 'Edit Kategori',
                'kategori' => ['id_kategori' => $id, 'nama_kategori' => ''],
                'error'    => 'Nama kategori wajib diisi.',
            ]);
        }

        $db = $this->db();

        // Cek duplikasi dengan kategori lain yang aktif
        $stmt = $db->prepare("SELECT COUNT(*) FROM kategori WHERE nama_kategori = ? AND id_kategori != ? AND is_active = 1");
        $stmt->execute([$namaKategori, $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return $this->render('kategori/edit', [
                'title'    => 'Edit Kategori',
                'kategori' => ['id_kategori' => $id, 'nama_kategori' => $namaKategori],
                'error'    => 'Nama kategori tersebut sudah digunakan.',
            ]);
        }

        $stmtUpdate = $db->prepare("UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?");
        $stmtUpdate->execute([$namaKategori, $id]);

        audit_log('update', 'kategori', $id, ['nama_kategori' => $namaKategori]);

        flash('success', 'Kategori berhasil diperbarui.');
        $this->redirect('kategori.php');
    }

    /**
     * Hapus kategori (soft delete is_active = 0, hanya POST dengan CSRF)
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
            $this->redirect('kategori.php');
        }

        $db = $this->db();

        // Cek apakah ada layanan aktif (non-archived) yang memakai kategori ini
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM layanan WHERE id_kategori = ? AND status != 'archived'");
        $stmtCheck->execute([$id]);
        $hasServices = (int)$stmtCheck->fetchColumn();

        if ($hasServices > 0) {
            flash('error', 'Kategori tidak dapat dihapus karena masih memiliki layanan.');
            $this->redirect('kategori.php');
        }

        // Soft delete: is_active = 0
        $stmtDelete = $db->prepare("UPDATE kategori SET is_active = 0 WHERE id_kategori = ?");
        $stmtDelete->execute([$id]);

        audit_log('delete', 'kategori', $id);

        flash('success', 'Kategori berhasil dihapus.');
        $this->redirect('kategori.php');
    }
}
