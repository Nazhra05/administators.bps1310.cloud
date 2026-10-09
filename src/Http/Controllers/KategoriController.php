<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use PDO;

class KategoriController extends Controller
{
    /**
     * Tampilkan daftar kategori
     */
    public function index(): string
    {
        $db = $this->db();

        // Ambil semua kategori + jumlah layanan
        $stmt = $db->query("
            SELECT 
                k.id_kategori,
                k.nama_kategori,
                COUNT(l.id_layanan) AS jumlah_layanan
            FROM kategori k
            LEFT JOIN layanan l ON k.id_kategori = l.id_kategori
            GROUP BY k.id_kategori, k.nama_kategori
            ORDER BY k.id_kategori ASC
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil preview layanan per kategori
        $stmtServices = $db->query("
            SELECT id_layanan, id_kategori, nama_layanan, url
            FROM layanan
            ORDER BY id_kategori ASC, id_layanan ASC
        ");
        $allServices = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

        $servicesByCategory = [];
        foreach ($allServices as $srv) {
            $catId = (int)$srv['id_kategori'];
            if (!isset($servicesByCategory[$catId])) {
                $servicesByCategory[$catId] = [];
            }
            if (count($servicesByCategory[$catId]) < 3) {
                $servicesByCategory[$catId][] = $srv;
            }
        }

        return $this->render('kategori/index', [
            'title'              => 'Kelola Kategori',
            'categories'         => $categories,
            'servicesByCategory' => $servicesByCategory,
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

        // Cek duplikasi
        $stmt = $db->prepare("SELECT COUNT(*) FROM kategori WHERE nama_kategori = ?");
        $stmt->execute([$namaKategori]);
        if ((int)$stmt->fetchColumn() > 0) {
            return $this->render('kategori/create', [
                'title'         => 'Tambah Kategori',
                'error'         => 'Kategori tersebut sudah ada.',
                'nama_kategori' => $namaKategori,
            ]);
        }

        // Insert
        $stmtInsert = $db->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
        $stmtInsert->execute([$namaKategori]);

        flash('success', 'Kategori baru berhasil ditambahkan.');
        $this->redirect('kategori.php');
    }

    /**
     * Form edit kategori
     */
    public function edit(array $params = []): string
    {
        $id = isset($params['id']) ? (int)$params['id'] : (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('kategori.php');
        }

        $db = $this->db();
        $stmt = $db->prepare("SELECT * FROM kategori WHERE id_kategori = ?");
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
        $id = isset($params['id']) ? (int)$params['id'] : (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('kategori.php');
        }

        if (!csrf_verify()) {
            http_response_code(403);
            die('Sesi keamanan tidak valid atau telah kedaluwarsa.');
        }

        $namaKategori = trim($_POST['nama_kategori'] ?? '');

        if ($namaKategori === '') {
            return $this->render('kategori/edit', [
                'title'    => 'Edit Kategori',
                'kategori' => ['id_kategori' => $id, 'nama_kategori' => ''],
                'error'    => 'Nama kategori wajib diisi.',
            ]);
        }

        $db = $this->db();

        // Cek duplikasi dengan kategori lain
        $stmt = $db->prepare("SELECT COUNT(*) FROM kategori WHERE nama_kategori = ? AND id_kategori != ?");
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

        flash('success', 'Kategori berhasil diperbarui.');
        $this->redirect('kategori.php');
    }

    /**
     * Hapus kategori (hanya POST dengan CSRF)
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
            $this->redirect('kategori.php');
        }

        $db = $this->db();

        // Cek apakah ada layanan yang memakai kategori ini
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM layanan WHERE id_kategori = ?");
        $stmtCheck->execute([$id]);
        $hasServices = (int)$stmtCheck->fetchColumn();

        if ($hasServices > 0) {
            flash('error', 'Kategori tidak dapat dihapus karena masih memiliki layanan.');
            $this->redirect('kategori.php');
        }

        $stmtDelete = $db->prepare("DELETE FROM kategori WHERE id_kategori = ?");
        $stmtDelete->execute([$id]);

        flash('success', 'Kategori berhasil dihapus.');
        $this->redirect('kategori.php');
    }
}
