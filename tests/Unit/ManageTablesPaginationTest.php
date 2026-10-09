<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LayananController;
use App\Support\Database;
use App\Support\Session;
use PDO;
use PHPUnit\Framework\TestCase;

class ManageTablesPaginationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE admin (
                id_admin INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT,
                last_name TEXT,
                username TEXT NOT NULL,
                email TEXT NOT NULL,
                password TEXT NULL,
                status TEXT NOT NULL DEFAULT 'approved',
                role TEXT NOT NULL DEFAULT 'USER APP PORTAL',
                unit_id INTEGER NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                must_change_password INTEGER NOT NULL DEFAULT 0,
                email_verified INTEGER NOT NULL DEFAULT 0,
                failed_attempts INTEGER NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login_at DATETIME NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE units (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama TEXT NOT NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE kategori (
                id_kategori INTEGER PRIMARY KEY AUTOINCREMENT,
                nama_kategori TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE layanan (
                id_layanan INTEGER PRIMARY KEY AUTOINCREMENT,
                id_kategori INTEGER NOT NULL,
                nama_layanan TEXT NOT NULL,
                deskripsi_layanan TEXT NOT NULL,
                url TEXT NOT NULL,
                logo TEXT NULL,
                akses TEXT NOT NULL DEFAULT 'Public',
                vpn TEXT NOT NULL DEFAULT 'No',
                status TEXT NOT NULL DEFAULT 'published',
                unit_id INTEGER NULL,
                reject_reason TEXT NULL,
                search_aliases TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE sessions (
                token TEXT PRIMARY KEY,
                user_id INTEGER NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Seed Units
        $this->pdo->exec("
            INSERT INTO units (id, nama) VALUES 
            (1, 'Bagian Umum'),
            (2, 'Statistik Sosial'),
            (3, 'Statistik Produksi')
        ");

        // Seed Categories
        for ($k = 1; $k <= 15; $k++) {
            $name = ($k === 1) ? 'Sosial Kependudukan' : "Kategori Bidang {$k}";
            $this->pdo->exec("INSERT INTO kategori (id_kategori, nama_kategori, is_active) VALUES ({$k}, '{$name}', 1)");
        }

        // Seed Layanan (35 items)
        for ($i = 1; $i <= 35; $i++) {
            $catId = ($i % 5) + 1;
            $unitId = ($i % 3) + 1;
            $akses = ($i % 2 === 0) ? 'Public' : 'Internal';
            $vpn = ($i % 3 === 0) ? 'Yes' : 'No';
            $status = ($i === 35) ? 'pending' : (($i % 4 === 0) ? 'draft' : 'published');
            $name = ($i === 10) ? 'Layanan Khusus BPS' : "Layanan Nomor {$i}";
            $aliases = ($i === 10) ? 'khusus,penting' : "alias_{$i}";

            $this->pdo->exec("
                INSERT INTO layanan (id_layanan, id_kategori, nama_layanan, deskripsi_layanan, url, akses, vpn, status, unit_id, search_aliases)
                VALUES ({$i}, {$catId}, '{$name}', 'Deskripsi layanan ke {$i}', 'https://bps.go.id/layanan/{$i}', '{$akses}', '{$vpn}', '{$status}', {$unitId}, '{$aliases}')
            ");
        }

        // Seed Admins
        $this->pdo->exec("
            INSERT INTO admin (id_admin, username, email, first_name, last_name, role, unit_id, status, is_active)
            VALUES (1, 'superadmin', 'admin@bps.go.id', 'Super', 'Admin', 'ADMIN IT', 1, 'approved', 1),
                   (2, 'leader1', 'leader@bps.go.id', 'Leader', 'Satu', 'ADMIN DASHBOARD', 2, 'approved', 1)
        ");

        // Seed Portal Users (25 items)
        for ($u = 10; $u <= 34; $u++) {
            $unit = ($u % 3) + 1;
            $status = ($u === 34) ? 'pending' : 'approved';
            $this->pdo->exec("
                INSERT INTO admin (id_admin, username, email, first_name, last_name, role, unit_id, status, is_active)
                VALUES ({$u}, 'user_{$u}', 'user_{$u}@bps.go.id', 'Pegawai', '{$u}', 'USER APP PORTAL', {$unit}, '{$status}', 1)
            ");
        }

        Database::setPdo($this->pdo);

        // Login session as ADMIN IT
        Session::start();
        Session::set('admin_id', 1);
        Session::set('admin_username', 'superadmin');
        Session::set('admin_role', 'ADMIN IT');

        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        Session::remove('admin_id');
        Session::remove('admin_username');
        Session::remove('admin_role');
    }

    public function testLayananPaginationAndLimit(): void
    {
        $_GET = [
            'per_page' => '10',
            'page'     => '2',
        ];

        $controller = new LayananController();
        $html = $controller->index();

        $this->assertStringContainsString('Menampilkan <strong style="color: #0f172a;">11</strong> - <strong style="color: #0f172a;">20</strong> dari <strong style="color: #0f172a;">35</strong> data', $html);
        $this->assertStringContainsString('page=1', $html);
        $this->assertStringContainsString('page=3', $html);
    }

    public function testLayananSearchAndFilter(): void
    {
        $_GET = [
            'search' => 'Khusus',
        ];

        $controller = new LayananController();
        $html = $controller->index();

        $this->assertStringContainsString('Layanan Khusus BPS', $html);
        $this->assertStringContainsString('Menampilkan <strong style="color: #0f172a;">1</strong> - <strong style="color: #0f172a;">1</strong> dari <strong style="color: #0f172a;">1</strong> data', $html);

        // Test search not found
        $_GET = [
            'search' => 'KataKunciYangPastiTidakAda12345',
        ];
        $htmlNotFound = $controller->index();
        $this->assertStringContainsString('Pencarian Tidak Ditemukan', $htmlNotFound);
        $this->assertStringContainsString('Reset Semua Filter', $htmlNotFound);
    }

    public function testLayananSortOrder(): void
    {
        $_GET = [
            'sort'  => 'nama',
            'order' => 'asc',
        ];

        $controller = new LayananController();
        $html = $controller->index();

        // In ASC, Layanan Khusus BPS should appear before Layanan Nomor 2
        $posKhusus = strpos($html, 'Layanan Khusus BPS');
        $posNomor = strpos($html, 'Layanan Nomor 1');
        $this->assertNotFalse($posKhusus);
        $this->assertNotFalse($posNomor);
        $this->assertLessThan($posNomor, $posKhusus);
    }

    public function testKategoriPaginationAndSearch(): void
    {
        $_GET = [
            'per_page' => '10',
            'page'     => '1',
        ];

        $controller = new KategoriController();
        $html = $controller->index();

        $this->assertStringContainsString('Menampilkan <strong style="color: #0f172a;">1</strong> - <strong style="color: #0f172a;">10</strong> dari <strong style="color: #0f172a;">15</strong> data', $html);

        // Search category
        $_GET = [
            'search' => 'Kependudukan',
        ];
        $searchHtml = $controller->index();
        $this->assertStringContainsString('Sosial Kependudukan', $searchHtml);
        $this->assertStringContainsString('Menampilkan <strong style="color: #0f172a;">1</strong> - <strong style="color: #0f172a;">1</strong> dari <strong style="color: #0f172a;">1</strong> data', $searchHtml);
    }

    public function testManageUserPaginationAndFilters(): void
    {
        $_GET = [
            'per_page' => '10',
            'page'     => '1',
            'unit_id'  => '1',
        ];

        $controller = new AdminController();
        $html = $controller->manageUser();

        $this->assertStringContainsString('Daftar Pengguna Portal & Pegawai', $html);
        $this->assertStringContainsString('Bagian Umum', $html);

        // Search user
        $_GET = [
            'search' => 'user_12',
        ];
        $searchHtml = $controller->manageUser();
        $this->assertStringContainsString('user_12', $searchHtml);
    }

    public function testManageAdminPaginationAndFilters(): void
    {
        $_GET = [
            'role' => 'ADMIN IT',
        ];

        $controller = new AdminController();
        $html = $controller->manageAdmin();

        $this->assertStringContainsString('Daftar Administrator Sistem', $html);
        $this->assertStringContainsString('superadmin', $html);
        $this->assertStringNotContainsString('leader1', $html);
    }
}
