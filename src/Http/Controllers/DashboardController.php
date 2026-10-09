<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use PDO;

class DashboardController extends Controller
{
    /**
     * Display the main administrator dashboard.
     */
    public function index(): string
    {
        $db = $this->db();

        // 1. Categories with service count
        $stmtKat = $db->query("
            SELECT k.id_kategori, k.nama_kategori, COUNT(l.id_layanan) AS jumlah_layanan
            FROM kategori k
            LEFT JOIN layanan l ON k.id_kategori = l.id_kategori
            GROUP BY k.id_kategori, k.nama_kategori
            ORDER BY k.id_kategori ASC
        ");
        $categories = $stmtKat->fetchAll(PDO::FETCH_ASSOC);

        // 2. Services with optional filter
        $filterKategori = $_GET['kategori'] ?? '';

        $sql = "
            SELECT l.id_layanan, l.id_kategori, l.nama_layanan, l.url, l.logo, k.nama_kategori
            FROM layanan l
            LEFT JOIN kategori k ON l.id_kategori = k.id_kategori
        ";
        $params = [];

        if ($filterKategori !== '') {
            $sql .= " WHERE l.id_kategori = ?";
            $params[] = $filterKategori;
        }

        $sql .= " ORDER BY l.id_kategori ASC, l.id_layanan DESC";

        $stmtLay = $db->prepare($sql);
        $stmtLay->execute($params);
        $services = $stmtLay->fetchAll(PDO::FETCH_ASSOC);

        // 3. Stats totals
        $stmtTotal = $db->query("SELECT COUNT(*) FROM layanan");
        $totalLayanan = (int)$stmtTotal->fetchColumn();

        return $this->render('dashboard/index', [
            'title'          => 'Dashboard Admin',
            'subtitle'       => 'Ringkasan operasional layanan dan kategori portal BPS Kabupaten Solok Selatan',
            'categories'     => $categories,
            'services'       => $services,
            'totalLayanan'   => $totalLayanan,
            'totalKategori'  => count($categories),
            'filterKategori' => $filterKategori,
        ]);
    }
}
