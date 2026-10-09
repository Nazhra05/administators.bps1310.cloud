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

class TestableAdminControllerForForm extends AdminController
{
    public ?string $lastRedirect = null;

    protected function redirect(string $path, int $status = 302): never
    {
        $this->lastRedirect = $path;
        throw new \RuntimeException("REDIRECT: {$path}");
    }
}

class FormReliabilityTest extends TestCase
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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_by INTEGER NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                action TEXT,
                entity TEXT,
                entity_id INTEGER,
                user_id INTEGER,
                role TEXT,
                details TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        Database::setPdo($this->pdo);

        // Seed data dasar
        $this->pdo->exec("INSERT INTO units (id, nama) VALUES (1, 'Umum'), (2, 'Sosial')");
        $this->pdo->exec("INSERT INTO kategori (id_kategori, nama_kategori, is_active) VALUES (1, 'Sosial Kependudukan', 1)");
        $this->pdo->exec("
            INSERT INTO admin (id_admin, username, email, first_name, last_name, role, is_active, status)
            VALUES (1, 'superadmin', 'super@bps.go.id', 'Super', 'Admin', 'ADMIN IT', 1, 'approved')
        ");

        Session::start();
        Session::set('admin_id', 1);
        Session::set('admin_role', 'ADMIN IT');
        $_SESSION['csrf_token'] = 'test-form-token';
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        Session::remove('form_errors');
        Session::remove('form_input');
    }

    /**
     * Test helper service_initials
     */
    public function testServiceInitialsHelper(): void
    {
        $this->assertSame('SI', service_initials('Simpeg'));
        $this->assertSame('WP', service_initials('Web Portal'));
        $this->assertSame('BP', service_initials('Badan Pusat Statistik'));
        $this->assertSame('HB', service_initials('Halo-BPS'));
        $this->assertSame('BP', service_initials(''));
        $this->assertSame('BP', service_initials('   '));
        $this->assertSame('1L', service_initials('123 Layanan'));
    }

    /**
     * Test helper service_color
     */
    public function testServiceColorHelper(): void
    {
        $color1 = service_color('Simpeg');
        $color2 = service_color('Simpeg');
        $this->assertSame($color1, $color2, 'service_color harus deterministik');
        $this->assertStringStartsWith('linear-gradient(135deg,', $color1);
    }

    /**
     * Test per-field validation and input retention on LayananController::store()
     */
    public function testLayananPerFieldValidationFailsWithErrorsAndRetainsInput(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token'        => 'test-form-token',
            'nama_layanan'      => 'ab', // < 3 karakter
            'id_kategori'       => '0',  // invalid
            'url'               => 'bukan-url-valid',
            'deskripsi_layanan' => '',   // kosong
            'akses'             => 'InvalidAccess',
            'vpn'               => 'InvalidVpn',
            'search_aliases'    => 'alias1, alias2',
        ];

        $controller = new LayananController();
        $html = $controller->store();

        // Verifikasi pesan error per field muncul di HTML
        $this->assertStringContainsString('Nama layanan wajib diisi minimal 3 karakter.', $html);
        $this->assertStringContainsString('Silakan pilih kategori yang valid.', $html);
        $this->assertStringContainsString('Format URL tidak valid.', $html);
        $this->assertStringContainsString('Deskripsi layanan wajib diisi.', $html);
        $this->assertStringContainsString('Pilihan akses harus Public atau Internal.', $html);
        $this->assertStringContainsString('Pilihan VPN harus Ya (Yes) atau Tidak (No).', $html);

        // Verifikasi input retention (nilai sebelumnya tetap terisi)
        $this->assertStringContainsString('value="ab"', $html);
        $this->assertStringContainsString('value="bukan-url-valid"', $html);
        $this->assertStringContainsString('value="alias1, alias2"', $html);
        $this->assertStringContainsString('class="input-error"', $html);
    }

    /**
     * Test per-field validation and input retention on LayananController::update()
     */
    public function testLayananEditValidationFailsWithErrorsAndRetainsInput(): void
    {
        // Insert dummy layanan
        $this->pdo->exec("
            INSERT INTO layanan (id_layanan, id_kategori, nama_layanan, deskripsi_layanan, url, akses, vpn, status, unit_id)
            VALUES (10, 1, 'Layanan Awal', 'Deskripsi Awal', 'https://bps.go.id', 'Public', 'No', 'published', 1)
        ");

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token'        => 'test-form-token',
            'id'                => 10,
            'nama_layanan'      => 'xy', // too short
            'id_kategori'       => 1,
            'url'               => 'https://valid-url.com',
            'deskripsi_layanan' => 'Deskripsi Baru',
            'akses'             => 'Public',
            'vpn'               => 'No',
        ];

        $controller = new LayananController();
        $html = $controller->update(['id' => 10]);

        $this->assertStringContainsString('Nama layanan wajib diisi minimal 3 karakter.', $html);
        $this->assertStringContainsString('value="xy"', $html);
        $this->assertStringContainsString('value="https://valid-url.com"', $html);
    }

    /**
     * Test per-field validation on KategoriController::store()
     */
    public function testKategoriPerFieldValidationFailsWithErrorsAndRetainsInput(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // 1. Tes nama kategori terlalu pendek
        $_POST = [
            'csrf_token'    => 'test-form-token',
            'nama_kategori' => 'k', // 1 karakter
        ];

        $controller = new KategoriController();
        $html = $controller->store();

        $this->assertStringContainsString('Nama kategori wajib diisi minimal 2 karakter.', $html);
        $this->assertStringContainsString('value="k"', $html);

        // 2. Tes duplikasi kategori
        $_POST = [
            'csrf_token'    => 'test-form-token',
            'nama_kategori' => 'Sosial Kependudukan',
        ];

        $htmlDup = $controller->store();
        $this->assertStringContainsString('Kategori dengan nama tersebut sudah ada.', $htmlDup);
        $this->assertStringContainsString('value="Sosial Kependudukan"', $htmlDup);
    }

    /**
     * Test per-field validation on KategoriController::update()
     */
    public function testKategoriUpdateValidationFailsWithErrors(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token'    => 'test-form-token',
            'id'            => 1,
            'nama_kategori' => '', // kosong
        ];

        $controller = new KategoriController();
        $html = $controller->update(['id' => 1]);

        $this->assertStringContainsString('Nama kategori wajib diisi.', $html);
    }

    /**
     * Test AdminController::storeUser validation stores per-field errors and input in Session
     */
    public function testAdminControllerStoreUserValidationStoresErrorsInSession(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => 'test-form-token',
            'username'   => 'ab', // < 3 karakter
            'email'      => 'email-tidak-valid',
            'first_name' => 'Budi',
            'last_name'  => 'Santoso',
            'role'       => 'ADMIN DASHBOARD',
        ];

        $controller = new TestableAdminControllerForForm();
        try {
            $controller->storeUser();
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('REDIRECT', $e->getMessage());
        }

        $formErrors = Session::get('form_errors');
        $formInput = Session::get('form_input');

        $this->assertIsArray($formErrors);
        $this->assertArrayHasKey('username', $formErrors);
        $this->assertArrayHasKey('email', $formErrors);
        $this->assertSame('Username wajib diisi 3-50 karakter alfanumerik/underscore.', $formErrors['username']);
        $this->assertSame('Format email tidak valid.', $formErrors['email']);

        $this->assertIsArray($formInput);
        $this->assertSame('ab', $formInput['username']);
        $this->assertSame('email-tidak-valid', $formInput['email']);
        $this->assertSame('Budi', $formInput['first_name']);
    }
}
