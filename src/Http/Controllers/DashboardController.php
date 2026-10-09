<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use PDO;

class DashboardController extends Controller
{
    /**
     * Display the main administrator dashboard with accurate summary metrics,
     * quick actions, and recent audit activity feed (Task 7.14).
     */
    public function index(): string
    {
        $db = $this->db();
        $user = $this->user();

        // 1. Categories with service count (active categories & non-archived services)
        $stmtKat = $db->query("
            SELECT k.id_kategori, k.nama_kategori, COUNT(l.id_layanan) AS jumlah_layanan
            FROM kategori k
            LEFT JOIN layanan l ON k.id_kategori = l.id_kategori AND l.status != 'archived'
            WHERE k.is_active = 1
            GROUP BY k.id_kategori, k.nama_kategori
            ORDER BY k.id_kategori ASC
        ");
        $categories = $stmtKat ? $stmtKat->fetchAll(PDO::FETCH_ASSOC) : [];

        // 2. Summary metrics: total, public, vpn, pending (efficient single query)
        $stmtStats = $db->query("
            SELECT 
                COUNT(CASE WHEN status != 'archived' THEN 1 END) AS total_layanan,
                COUNT(CASE WHEN status = 'published' AND akses = 'Public' THEN 1 END) AS total_publik,
                COUNT(CASE WHEN status = 'published' AND vpn = 'Yes' THEN 1 END) AS total_vpn,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) AS total_pending
            FROM layanan
        ");
        $statsRow = $stmtStats ? $stmtStats->fetch(PDO::FETCH_ASSOC) : [];

        $stmtTotalKat = $db->query("SELECT COUNT(*) FROM kategori WHERE is_active = 1");
        $totalKategori = $stmtTotalKat ? (int)$stmtTotalKat->fetchColumn() : count($categories);

        $metrics = [
            'total_layanan'  => (int)($statsRow['total_layanan'] ?? 0),
            'total_publik'   => (int)($statsRow['total_publik'] ?? 0),
            'total_vpn'      => (int)($statsRow['total_vpn'] ?? 0),
            'total_pending'  => (int)($statsRow['total_pending'] ?? 0),
            'total_kategori' => $totalKategori,
        ];

        // 3. Recent Audit Log Activity Feed (Task 7.14)
        $recentActivities = [];
        try {
            $stmtAudit = $db->query("
                SELECT a.id, a.user_id, a.action, a.entity, a.entity_id, a.meta, a.created_at,
                       adm.username, adm.first_name, adm.last_name, adm.role
                FROM audit_log a
                LEFT JOIN admin adm ON a.user_id = adm.id_admin
                ORDER BY a.id DESC
                LIMIT 8
            ");
            if ($stmtAudit) {
                $recentActivities = $stmtAudit->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (\Throwable $e) {
            $recentActivities = [];
        }

        // 4. Services with optional filter (non-archived)
        $filterKategori = $_GET['kategori'] ?? '';

        $sql = "
            SELECT l.id_layanan, l.id_kategori, l.nama_layanan, l.url, l.logo, l.status, l.akses, l.vpn, k.nama_kategori
            FROM layanan l
            LEFT JOIN kategori k ON l.id_kategori = k.id_kategori
            WHERE l.status != 'archived' AND (k.is_active = 1 OR k.id_kategori IS NULL)
        ";
        $params = [];

        if ($filterKategori !== '') {
            $sql .= " AND l.id_kategori = ?";
            $params[] = $filterKategori;
        }

        $sql .= " ORDER BY l.id_kategori ASC, l.id_layanan DESC";

        $stmtLay = $db->prepare($sql);
        $stmtLay->execute($params);
        $services = $stmtLay ? $stmtLay->fetchAll(PDO::FETCH_ASSOC) : [];

        return $this->render('dashboard/index', [
            'title'            => 'Dashboard Admin',
            'subtitle'         => 'Ringkasan operasional layanan dan kategori portal BPS Kabupaten Solok Selatan',
            'categories'       => $categories,
            'services'         => $services,
            'metrics'          => $metrics,
            'recentActivities' => $recentActivities,
            'filterKategori'   => $filterKategori,
            'user'             => $user,
            // Backward-compatibility aliases
            'totalLayanan'     => $metrics['total_layanan'],
            'totalKategori'    => $metrics['total_kategori'],
        ]);
    }
}
