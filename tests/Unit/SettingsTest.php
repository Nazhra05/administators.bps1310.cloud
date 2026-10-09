<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\PengaturanController;
use App\Support\Database;
use App\Support\Session;
use PDO;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    private PDO $pdo;
    private ApiController $apiController;
    private PengaturanController $pengaturanController;

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
            CREATE TABLE settings (
                skey TEXT PRIMARY KEY,
                svalue TEXT NULL
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

        // Seed admins
        $this->pdo->exec("
            INSERT INTO admin (id_admin, username, email, role, is_active, status)
            VALUES (1, 'it_admin', 'it@bps.go.id', 'ADMIN IT', 1, 'approved'),
                   (2, 'regular_admin', 'leader@bps.go.id', 'ADMIN DASHBOARD', 1, 'approved')
        ");

        Database::setPdo($this->pdo);

        $this->apiController = new ApiController();
        $this->pengaturanController = new PengaturanController();

        Session::start();
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
    }

    private function authenticateUser(int $id, string $role): void
    {
        Session::start();
        Session::set('admin_logged_in', true);
        Session::set('admin_id', $id);
        Session::set('admin_role', $role);
        Session::set('role', $role);
        Session::set('admin_username', 'user_' . $id);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $id;
        $_SESSION['role'] = $role;
        $_SESSION['admin_role'] = $role;
        $_SESSION['admin_username'] = 'user_' . $id;
    }

    public function testPublicSettingsReturnsDefaultWhitelistedKeysWhenEmpty(): void
    {
        $response = $this->apiController->publicSettings();
        $json = json_decode($response, true);

        $this->assertIsArray($json);
        $this->assertArrayHasKey('data', $json);
        $data = $json['data'];

        $this->assertArrayHasKey('app_name', $data);
        $this->assertArrayHasKey('contact_email', $data);
        $this->assertArrayHasKey('contact_whatsapp', $data);
        $this->assertArrayHasKey('service_hours', $data);
        $this->assertArrayHasKey('helpdesk_unit', $data);
        $this->assertArrayHasKey('portal_url', $data);

        $this->assertSame('Portal Internal BPS Kabupaten Solok Selatan', $data['app_name']);
        $this->assertSame('08116661310', $data['contact_whatsapp']);
    }

    public function testPublicSettingsReturnsSavedValuesAndExcludesNonWhitelistedKeys(): void
    {
        $this->pdo->exec("
            INSERT INTO settings (skey, svalue) VALUES
            ('app_name', 'Portal Solsel Hebat'),
            ('contact_email', 'helpdesk@solsel.bps.go.id'),
            ('contact_whatsapp', '081234567890'),
            ('service_hours', '07.30 - 16.00 WIB'),
            ('helpdesk_unit', 'Tim TI & Diseminasi'),
            ('secret_token', 'super-secret-token-do-not-leak'),
            ('smtp_password', 'p@ssw0rd123')
        ");

        $response = $this->apiController->publicSettings();
        $json = json_decode($response, true);

        $this->assertIsArray($json);
        $data = $json['data'];

        $this->assertSame('Portal Solsel Hebat', $data['app_name']);
        $this->assertSame('helpdesk@solsel.bps.go.id', $data['contact_email']);
        $this->assertSame('081234567890', $data['contact_whatsapp']);
        $this->assertSame('07.30 - 16.00 WIB', $data['service_hours']);
        $this->assertSame('Tim TI & Diseminasi', $data['helpdesk_unit']);

        // Strict security: non-whitelisted keys must NEVER be leaked
        $this->assertArrayNotHasKey('secret_token', $data);
        $this->assertArrayNotHasKey('smtp_password', $data);
    }

    public function testAdminItCanSaveSettingsSuccessfully(): void
    {
        $this->authenticateUser(1, 'ADMIN IT');
        $_SESSION['csrf_token'] = 'valid-token';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'action'           => 'update_settings',
            'csrf_token'       => 'valid-token',
            'app_name'         => 'Sistem Layanan BPS Solok Selatan',
            'contact_email'    => 'ti1310@bps.go.id',
            'contact_whatsapp' => '089911223344',
            'service_hours'    => 'Senin - Jumat 08.00 - 16.00',
            'helpdesk_unit'    => 'Seksi IPDS',
            'unauthorized_key' => 'hacked_value',
        ];

        $html = $this->pengaturanController->postHandler();

        $this->assertStringContainsString('Pengaturan kontak bantuan dan portal berhasil disimpan', $html);

        // Verify database updated
        $stmt = $this->pdo->query("SELECT skey, svalue FROM settings");
        $saved = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->assertSame('Sistem Layanan BPS Solok Selatan', $saved['app_name']);
        $this->assertSame('ti1310@bps.go.id', $saved['contact_email']);
        $this->assertSame('089911223344', $saved['contact_whatsapp']);
        $this->assertSame('Senin - Jumat 08.00 - 16.00', $saved['service_hours']);
        $this->assertSame('Seksi IPDS', $saved['helpdesk_unit']);
        $this->assertArrayNotHasKey('unauthorized_key', $saved);

        // Verify audit log
        $stmtAudit = $this->pdo->query("SELECT * FROM audit_log WHERE entity = 'settings' ORDER BY id DESC LIMIT 1");
        $log = $stmtAudit->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertSame('update', $log['action']);
        $meta = json_decode((string)$log['meta'], true);
        $this->assertContains('app_name', $meta['updated_keys']);
    }

    public function testNonAdminItCannotSaveSettings(): void
    {
        $this->authenticateUser(2, 'ADMIN DASHBOARD');
        $_SESSION['csrf_token'] = 'valid-token';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'action'           => 'update_settings',
            'csrf_token'       => 'valid-token',
            'app_name'         => 'Hacked App Name',
        ];

        $html = $this->pengaturanController->postHandler();

        $this->assertSame(403, http_response_code());
        $this->assertStringContainsString('403', $html);
        $this->assertStringContainsString('Hanya Administrator IT', $html);

        // Ensure database untouched
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM settings WHERE skey = 'app_name'");
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testSaveSettingsFailsWithInvalidCsrf(): void
    {
        $this->authenticateUser(1, 'ADMIN IT');
        $_SESSION['csrf_token'] = 'real-token';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'action'        => 'update_settings',
            'csrf_token'    => 'forged-token',
            'app_name'      => 'Forged Name',
        ];

        $html = $this->pengaturanController->postHandler();

        $this->assertStringContainsString('Token keamanan CSRF tidak valid', $html);

        // Ensure database untouched
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM settings WHERE skey = 'app_name'");
        $this->assertEquals(0, $stmt->fetchColumn());
    }
}
