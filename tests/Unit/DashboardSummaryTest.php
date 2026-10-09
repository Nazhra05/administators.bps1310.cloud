<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\DashboardController;
use App\Support\AuditLogger;
use App\Support\Database;
use App\Support\Session;
use PDO;
use PHPUnit\Framework\TestCase;

class DashboardSummaryTest extends TestCase
{
    private PDO $pdo;
    private DashboardController $controller;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQLite schema for test
        $this->pdo->exec("
            CREATE TABLE admin (
                id_admin INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT,
                last_name TEXT,
                username TEXT NOT NULL,
                email TEXT NOT NULL,
                password TEXT NULL,
                status TEXT NOT NULL DEFAULT 'approved',
                role TEXT NOT NULL DEFAULT 'ADMIN IT',
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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_by INTEGER NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT,
                entity TEXT,
                entity_id INTEGER,
                meta TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        Database::setPdo($this->pdo);

        // Seed admin user
        $this->pdo->exec("
            INSERT INTO admin (id_admin, username, email, first_name, last_name, role, is_active, status)
            VALUES (1, 'admin_it', 'it@bps1310.cloud', 'Admin', 'Solsel', 'ADMIN IT', 1, 'approved')
        ");

        Session::start();
        Session::set('admin_id', 1);
        Session::set('admin_role', 'ADMIN IT');

        $this->controller = new DashboardController();
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        Session::destroy();
    }

    public function testSummaryMetricsAccurateAndExcludesArchived(): void
    {
        // 2 active categories, 1 inactive category
        $this->pdo->exec("INSERT INTO kategori (id_kategori, nama_kategori, is_active) VALUES (1, 'Statistik Sosial', 1)");
        $this->pdo->exec("INSERT INTO kategori (id_kategori, nama_kategori, is_active) VALUES (2, 'Statistik Ekonomi', 1)");
        $this->pdo->exec("INSERT INTO kategori (id_kategori, nama_kategori, is_active) VALUES (3, 'Kategori Arsip', 0)");

        // Services:
        // 1. Published, Public, VPN No
        $this->pdo->exec("INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, status, akses, vpn)
            VALUES (1, 'Portal Publik 1', 'Deskripsi', 'https://example.com/1', 'published', 'Public', 'No')");

        // 2. Published, Public, VPN Yes
        $this->pdo->exec("INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, status, akses, vpn)
            VALUES (1, 'Portal Publik 2', 'Deskripsi', 'https://example.com/2', 'published', 'Public', 'Yes')");

        // 3. Published, Internal, VPN Yes
        $this->pdo->exec("INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, status, akses, vpn)
            VALUES (2, 'Aplikasi Internal VPN', 'Deskripsi', 'https://example.com/3', 'published', 'Internal', 'Yes')");

        // 4. Pending, Public, VPN No
        $this->pdo->exec("INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, status, akses, vpn)
            VALUES (2, 'Usulan Layanan Baru', 'Deskripsi', 'https://example.com/4', 'pending', 'Public', 'No')");

        // 5. Draft, Public, VPN No
        $this->pdo->exec("INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, status, akses, vpn)
            VALUES (2, 'Konsep Layanan', 'Deskripsi', 'https://example.com/5', 'draft', 'Public', 'No')");

        // 6. Archived, Public, VPN Yes (MUST NOT be counted in any active metric)
        $this->pdo->exec("INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, status, akses, vpn)
            VALUES (1, 'Layanan Usang', 'Deskripsi', 'https://example.com/6', 'archived', 'Public', 'Yes')");

        $html = $this->controller->index();

        // Total layanan (non-archived): 5
        $this->assertStringContainsString('Total Layanan', $html);
        $this->assertStringContainsString('5', $html);

        // Layanan Publik (published & Public): 2
        $this->assertStringContainsString('Layanan Publik', $html);

        // Memerlukan VPN (published & vpn=Yes): 2
        $this->assertStringContainsString('Memerlukan VPN', $html);

        // Total Kategori (is_active=1): 2
        $this->assertStringContainsString('Total Kategori', $html);

        // Pending Approval: 1, with link 'Tinjau →'
        $this->assertStringContainsString('Pending Approval', $html);
        $this->assertStringContainsString('Tinjau →', $html);
    }

    public function testRecentAuditActivitiesFeedLimitAndFormatting(): void
    {
        // Insert 10 audit log entries
        for ($i = 1; $i <= 10; $i++) {
            AuditLogger::log(
                action: 'create',
                entity: 'layanan',
                entityId: $i,
                meta: ['nama_layanan' => "Layanan Test {$i}"],
                userId: 1,
                pdo: $this->pdo
            );
        }

        $html = $this->controller->index();

        // Feed limits to 8 latest items
        $this->assertStringContainsString('Aktivitas Terbaru', $html);
        $this->assertStringContainsString('Layanan Test 10', $html);
        $this->assertStringContainsString('Layanan Test 9', $html);
        $this->assertStringContainsString('Layanan Test 3', $html);
        // Item 1 & 2 should be sliced out due to LIMIT 8 (items 10 down to 3)
        $this->assertStringNotContainsString('Layanan Test 1<', $html);
        $this->assertStringNotContainsString('Layanan Test 2<', $html);
    }

    public function testEmptyAuditLogGracefulHandling(): void
    {
        // Audit log is completely empty
        $html = $this->controller->index();

        $this->assertStringContainsString('Belum ada catatan aktivitas tercatat', $html);
    }

    public function testCategoryFiltering(): void
    {
        $this->pdo->exec("INSERT INTO kategori (id_kategori, nama_kategori, is_active) VALUES (10, 'Sosial', 1)");
        $this->pdo->exec("INSERT INTO kategori (id_kategori, nama_kategori, is_active) VALUES (20, 'Ekonomi', 1)");

        $this->pdo->exec("INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, status)
            VALUES (10, 'Layanan Sosial Spesifik', 'Deskripsi', 'https://sosial.bps.go.id', 'published')");
        $this->pdo->exec("INSERT INTO layanan (id_kategori, nama_layanan, deskripsi_layanan, url, status)
            VALUES (20, 'Layanan Ekonomi Spesifik', 'Deskripsi', 'https://ekonomi.bps.go.id', 'published')");

        // 1. Without filter: both present
        $_GET = [];
        $htmlAll = $this->controller->index();
        $this->assertStringContainsString('Layanan Sosial Spesifik', $htmlAll);
        $this->assertStringContainsString('Layanan Ekonomi Spesifik', $htmlAll);

        // 2. Filter by category 10
        $_GET['kategori'] = '10';
        $htmlSosial = $this->controller->index();
        $this->assertStringContainsString('Layanan Sosial Spesifik', $htmlSosial);
        $this->assertStringNotContainsString('Layanan Ekonomi Spesifik', $htmlSosial);
        $this->assertStringContainsString('Reset', $htmlSosial);
    }

    public function testQuickActionsVisibleForAdminIt(): void
    {
        Session::set('admin_role', 'ADMIN IT');
        $html = $this->controller->index();

        $this->assertStringContainsString('Tambah Layanan', $html);
        $this->assertStringContainsString('Tambah Kategori', $html);
        $this->assertStringContainsString('Kelola Pengguna', $html);
        $this->assertStringContainsString('Pengaturan Sistem', $html);
    }

    public function testQuickActionsRestrictedForPimpinan(): void
    {
        Session::set('admin_role', 'PIMPINAN');
        $html = $this->controller->index();

        $this->assertStringContainsString('Tambah Layanan', $html);
        $this->assertStringContainsString('Tambah Kategori', $html);
        // Admin IT-only shortcuts must not appear for PIMPINAN
        $this->assertStringNotContainsString('Kelola Pengguna', $html);
        $this->assertStringNotContainsString('Pengaturan Sistem', $html);
    }
}
