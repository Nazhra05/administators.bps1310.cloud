<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\LayananController;
use App\Support\Database;
use App\Support\Session;
use PDO;
use PHPUnit\Framework\TestCase;

class TestableLayananController extends LayananController
{
    public ?string $lastRedirect = null;

    protected function redirect(string $path, int $status = 302): never
    {
        $this->lastRedirect = $path;
        throw new \RuntimeException("REDIRECT: {$path}");
    }
}

class ServiceApprovalWorkflowTest extends TestCase
{
    private PDO $pdo;
    private TestableLayananController $layananController;
    private AdminController $adminController;

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
                role TEXT NOT NULL DEFAULT 'ADMIN DASHBOARD',
                unit_id INTEGER NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                must_change_password INTEGER NOT NULL DEFAULT 0,
                failed_attempts INTEGER NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                last_login_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                email_verified INTEGER DEFAULT 1,
                verification_token TEXT NULL,
                verification_expires DATETIME NULL
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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
                search_aliases TEXT NULL,
                updated_by INTEGER NULL,
                reject_reason TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                action TEXT NOT NULL,
                entity TEXT NOT NULL,
                entity_id INTEGER NULL,
                meta TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Seed basic units and kategori
        $this->pdo->exec("INSERT INTO units (id, nama) VALUES (1, 'Bagian Umum'), (2, 'Tim Nerwilis')");
        $this->pdo->exec("INSERT INTO kategori (id_kategori, nama_kategori) VALUES (1, 'Statistik Sosial'), (2, 'Administrasi')");

        // Seed admins
        $this->pdo->exec("
            INSERT INTO admin (id_admin, username, email, role, unit_id, is_active, status)
            VALUES (1, 'superadmin', 'admin@bps.go.id', 'ADMIN IT', 1, 1, 'approved'),
                   (2, 'leader_nerwilis', 'leader@bps.go.id', 'ADMIN DASHBOARD', 2, 1, 'approved')
        ");

        Database::setPdo($this->pdo);

        $this->layananController = new TestableLayananController();
        $this->adminController = new AdminController();

        Session::start();
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_FILES = [];
    }

    private function authenticateUser(int $id, string $role, ?int $unitId = null): void
    {
        Session::start();
        Session::set('admin_logged_in', true);
        Session::set('admin_id', $id);
        Session::set('admin_role', $role);
        Session::set('role', $role);
        Session::set('admin_unit_id', $unitId);
        Session::set('unit_id', $unitId);
        Session::set('admin_username', 'user_' . $id);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $id;
        $_SESSION['role'] = $role;
        $_SESSION['admin_role'] = $role;
        $_SESSION['admin_unit_id'] = $unitId;
        $_SESSION['unit_id'] = $unitId;
        $_SESSION['admin_username'] = 'user_' . $id;
    }

    public function testLeaderAddingServiceSetsStatusPendingAndAssignsUnit(): void
    {
        $this->authenticateUser(2, 'ADMIN DASHBOARD', 2);
        $_SESSION['csrf_token'] = 'test-token';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'csrf_token'        => 'test-token',
            'nama_layanan'      => 'Portal Nerwilis Solsel',
            'deskripsi_layanan' => 'Layanan analisis statistik regional',
            'url'               => 'https://nerwilis.bps.go.id',
            'id_kategori'       => '1',
            'akses'             => 'Public',
            'vpn'               => 'No',
        ];

        try {
            $this->layananController->store();
            $this->fail("Expected redirect exception");
        } catch (\RuntimeException $e) {
            $this->assertSame("REDIRECT: layanan.php", $e->getMessage());
        }

        $stmt = $this->pdo->query("SELECT * FROM layanan WHERE nama_layanan = 'Portal Nerwilis Solsel'");
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($service);
        $this->assertSame('pending', $service['status']);
        $this->assertEquals(2, $service['unit_id']);

        // Check audit log
        $stmtLog = $this->pdo->query("SELECT * FROM audit_log WHERE entity = 'layanan' AND action = 'create'");
        $log = $stmtLog->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertEquals(2, $log['user_id']);
        $this->assertStringContainsString('pending', $log['meta']);
    }

    public function testLeaderUpdatingServiceRevertsStatusToPendingAndClearsRejectReason(): void
    {
        $this->authenticateUser(2, 'ADMIN DASHBOARD', 2);
        $_SESSION['csrf_token'] = 'test-token';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Insert initial draft service with previous rejection reason
        $this->pdo->exec("
            INSERT INTO layanan (id_layanan, id_kategori, nama_layanan, deskripsi_layanan, url, status, unit_id, reject_reason)
            VALUES (10, 1, 'Data PDRB', 'Deskripsi lama', 'https://bps.go.id/pdrb', 'draft', 2, 'URL mati mohon diperbaiki')
        ");

        $_POST = [
            'id'                => '10',
            'csrf_token'        => 'test-token',
            'nama_layanan'      => 'Data PDRB Terkini',
            'deskripsi_layanan' => 'Deskripsi baru yang sudah diperbaiki',
            'url'               => 'https://bps.go.id/pdrb-baru',
            'id_kategori'       => '1',
            'akses'             => 'Internal',
            'vpn'               => 'Yes',
        ];

        try {
            $this->layananController->update(['id' => 10]);
            $this->fail("Expected redirect exception");
        } catch (\RuntimeException $e) {
            $this->assertSame("REDIRECT: layanan.php", $e->getMessage());
        }

        $stmt = $this->pdo->query("SELECT * FROM layanan WHERE id_layanan = 10");
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('pending', $service['status']);
        $this->assertNull($service['reject_reason']);
        $this->assertSame('Data PDRB Terkini', $service['nama_layanan']);

        // Check audit log
        $stmtLog = $this->pdo->query("SELECT * FROM audit_log WHERE entity = 'layanan' AND action = 'update' AND entity_id = 10");
        $log = $stmtLog->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertStringContainsString('pending', $log['meta']);
    }

    public function testAdminItApprovingPendingServiceSetsPublishedAndClearsRejectReason(): void
    {
        $this->authenticateUser(1, 'ADMIN IT', 1);
        $_SESSION['csrf_token'] = 'test-token';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->pdo->exec("
            INSERT INTO layanan (id_layanan, id_kategori, nama_layanan, deskripsi_layanan, url, status, unit_id, reject_reason)
            VALUES (20, 2, 'Layanan Kepegawaian', 'Deskripsi', 'https://simpeg.bps.go.id', 'pending', 1, 'Catatan lama')
        ");

        $_POST = [
            'id'         => '20',
            'action'     => 'approve',
            'csrf_token' => 'test-token',
        ];

        try {
            $this->layananController->updateStatus(['id' => 20]);
            $this->fail("Expected redirect exception");
        } catch (\RuntimeException $e) {
            $this->assertSame("REDIRECT: layanan.php", $e->getMessage());
        }

        $stmt = $this->pdo->query("SELECT * FROM layanan WHERE id_layanan = 20");
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('published', $service['status']);
        $this->assertNull($service['reject_reason']);
        $this->assertEquals(1, $service['updated_by']);

        // Check audit log
        $stmtLog = $this->pdo->query("SELECT * FROM audit_log WHERE entity = 'layanan' AND action = 'approve' AND entity_id = 20");
        $log = $stmtLog->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertStringContainsString('published', $log['meta']);
    }

    public function testAdminItRejectingPendingServiceSetsDraftAndSavesRejectReason(): void
    {
        $this->authenticateUser(1, 'ADMIN IT', 1);
        $_SESSION['csrf_token'] = 'test-token';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->pdo->exec("
            INSERT INTO layanan (id_layanan, id_kategori, nama_layanan, deskripsi_layanan, url, status, unit_id)
            VALUES (30, 1, 'Survei Pertanian', 'Deskripsi survei', 'https://tani.bps.go.id', 'pending', 2)
        ");

        $_POST = [
            'id'            => '30',
            'action'        => 'reject',
            'reject_reason' => 'Tautan belum dapat diakses dari jaringan publik.',
            'csrf_token'    => 'test-token',
        ];

        try {
            $this->layananController->updateStatus(['id' => 30]);
            $this->fail("Expected redirect exception");
        } catch (\RuntimeException $e) {
            $this->assertSame("REDIRECT: layanan.php", $e->getMessage());
        }

        $stmt = $this->pdo->query("SELECT * FROM layanan WHERE id_layanan = 30");
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('draft', $service['status']);
        $this->assertSame('Tautan belum dapat diakses dari jaringan publik.', $service['reject_reason']);
        $this->assertEquals(1, $service['updated_by']);

        // Check audit log
        $stmtLog = $this->pdo->query("SELECT * FROM audit_log WHERE entity = 'layanan' AND action = 'reject' AND entity_id = 30");
        $log = $stmtLog->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertStringContainsString('Tautan belum dapat diakses', $log['meta']);
    }

    public function testNonAdminItCannotCallUpdateStatus(): void
    {
        $this->authenticateUser(2, 'ADMIN DASHBOARD', 2);
        $_SESSION['csrf_token'] = 'test-token';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->pdo->exec("
            INSERT INTO layanan (id_layanan, id_kategori, nama_layanan, deskripsi_layanan, url, status, unit_id)
            VALUES (40, 1, 'Layanan Publik', 'Deskripsi', 'https://bps.go.id', 'pending', 2)
        ");

        $_POST = [
            'id'         => '40',
            'action'     => 'approve',
            'csrf_token' => 'test-token',
        ];

        ob_start();
        $this->layananController->updateStatus(['id' => 40]);
        $output = ob_get_clean();

        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('tidak memiliki hak', (string)$output);

        // Status must remain pending
        $stmt = $this->pdo->query("SELECT status FROM layanan WHERE id_layanan = 40");
        $this->assertSame('pending', $stmt->fetchColumn());
    }

    public function testLeaderCannotEditOrDeleteServiceFromAnotherUnit(): void
    {
        // User is leader of unit 2 (Nerwilis)
        $this->authenticateUser(2, 'ADMIN DASHBOARD', 2);
        $_SESSION['csrf_token'] = 'test-token';

        // Service belongs to unit 1 (Bagian Umum)
        $this->pdo->exec("
            INSERT INTO layanan (id_layanan, id_kategori, nama_layanan, deskripsi_layanan, url, status, unit_id)
            VALUES (50, 2, 'Layanan Bagian Umum', 'Deskripsi', 'https://umum.bps.go.id', 'published', 1)
        ");

        // Attempt edit (GET)
        $editHtml = $this->layananController->edit(['id' => 50]);
        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('hanya berhak mengelola layanan di unit kerja Anda', $editHtml);

        // Attempt update (POST)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'id'                => '50',
            'csrf_token'        => 'test-token',
            'nama_layanan'      => 'Hijack Service',
            'deskripsi_layanan' => 'Deskripsi',
            'url'               => 'https://umum.bps.go.id',
            'id_kategori'       => '2',
            'akses'             => 'Public',
            'vpn'               => 'No',
        ];
        $updateHtml = $this->layananController->update(['id' => 50]);
        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('hanya berhak mengelola layanan di unit kerja Anda', $updateHtml);

        // Attempt destroy (POST)
        ob_start();
        $this->layananController->destroy(['id' => 50]);
        $destroyOutput = ob_get_clean();
        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('hanya berhak mengelola layanan di unit kerja Anda', (string)$destroyOutput);

        // Service must still be published and not archived
        $stmt = $this->pdo->query("SELECT status FROM layanan WHERE id_layanan = 50");
        $this->assertSame('published', $stmt->fetchColumn());
    }

    public function testAuditLogViewerRendersHistory(): void
    {
        $this->authenticateUser(1, 'ADMIN IT', 1);

        $this->pdo->exec("
            INSERT INTO audit_log (id, user_id, action, entity, entity_id, meta, created_at)
            VALUES (1, 1, 'approve', 'layanan', 20, '{\"status\":\"published\"}', '2026-10-09 10:00:00'),
                   (2, 1, 'reject', 'layanan', 30, '{\"status\":\"draft\",\"reject_reason\":\"Perbaiki URL\"}', '2026-10-09 10:05:00')
        ");

        $html = $this->adminController->auditLog();

        $this->assertStringContainsString('Riwayat Audit Sistem', $html);
        $this->assertStringContainsString('Setujui', $html);
        $this->assertStringContainsString('Tolak', $html);
        $this->assertStringContainsString('Perbaiki URL', $html);
    }
}
