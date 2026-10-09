<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\LayananController;
use App\Support\Database;
use App\Support\Session;
use PDO;
use PHPUnit\Framework\TestCase;

class TestableLayananControllerForCsv extends LayananController
{
    public ?string $lastRedirect = null;

    protected function redirect(string $path, int $status = 302): never
    {
        $this->lastRedirect = $path;
        throw new \RuntimeException("REDIRECT: {$path}");
    }
}

class LayananCsvExportImportTest extends TestCase
{
    private PDO $pdo;
    private TestableLayananControllerForCsv $controller;

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
                role TEXT NOT NULL DEFAULT 'ADMIN IT',
                unit_id INTEGER NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                status TEXT NOT NULL DEFAULT 'approved'
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

        $this->pdo->exec("
            INSERT INTO admin (id_admin, username, email, first_name, last_name, role, is_active, status)
            VALUES (1, 'superadmin', 'super@bps1310.cloud', 'Super', 'Admin', 'ADMIN IT', 1, 'approved')
        ");

        $this->pdo->exec("
            INSERT INTO kategori (id_kategori, nama_kategori, is_active)
            VALUES (1, 'Sosial Kependudukan', 1), (2, 'Ekonomi Kreatif', 1)
        ");

        Session::start();
        Session::set('admin_id', 1);
        Session::set('admin_role', 'ADMIN IT');
        Session::set('csrf_token', 'test_csrf_token');

        $this->controller = new TestableLayananControllerForCsv();
        $_GET = [];
        $_POST = [];
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        Session::destroy();
    }

    public function testImportCsvValidRowsInserted(): void
    {
        $csvContent = "nama_layanan,kategori,url,akses,vpn,deskripsi_layanan,search_aliases\n";
        $csvContent .= "Portal Satu,Sosial Kependudukan,https://satu.bps.go.id,Public,No,Deskripsi Satu,\"satu, data\"\n";
        $csvContent .= "Portal Dua,Ekonomi Kreatif,https://dua.bps.go.id,Internal,Yes,Deskripsi Dua,\"dua, internal\"\n";

        $tmpFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tmpFile, $csvContent);

        $_POST['csrf_token'] = 'test_csrf_token';
        $_FILES['csv_file'] = [
            'name'     => 'layanan_import.csv',
            'type'     => 'text/csv',
            'tmp_name' => $tmpFile,
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($csvContent),
        ];

        try {
            $this->controller->importCsv();
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('REDIRECT: layanan.php', $e->getMessage());
        }

        // Verify rows inserted in database
        $stmt = $this->pdo->query("SELECT * FROM layanan WHERE nama_layanan = 'Portal Satu'");
        $srv1 = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($srv1);
        $this->assertSame(1, (int)$srv1['id_kategori']);
        $this->assertSame('Public', $srv1['akses']);
        $this->assertSame('No', $srv1['vpn']);
        $this->assertSame('published', $srv1['status']);
        $this->assertSame('satu, data', $srv1['search_aliases']);

        $stmt2 = $this->pdo->query("SELECT * FROM layanan WHERE nama_layanan = 'Portal Dua'");
        $srv2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($srv2);
        $this->assertSame(2, (int)$srv2['id_kategori']);
        $this->assertSame('Internal', $srv2['akses']);
        $this->assertSame('Yes', $srv2['vpn']);

        $successMsg = flash('success');
        $this->assertNotEmpty($successMsg);
        $this->assertStringContainsString('Berhasil mengimpor 2 data layanan', $successMsg);

        @unlink($tmpFile);
    }

    public function testImportCsvRowByRowValidationWithPartialErrors(): void
    {
        $csvContent = "nama_layanan,kategori,url,akses,vpn,deskripsi_layanan,search_aliases\n";
        // Row 1: Valid
        $csvContent .= "Layanan Valid,Sosial Kependudukan,https://valid.bps.go.id,Public,No,Layanan Bagus,valid\n";
        // Row 2: Invalid category
        $csvContent .= "Layanan Kategori Salah,Kategori Gaib,https://gaib.bps.go.id,Public,No,Deskripsi,gaib\n";
        // Row 3: Invalid URL
        $csvContent .= "Layanan URL Rusak,Sosial Kependudukan,bukan-url-valid,Public,No,Deskripsi,rusak\n";

        $tmpFile = tempnam(sys_get_temp_dir(), 'csv_partial_');
        file_put_contents($tmpFile, $csvContent);

        $_POST['csrf_token'] = 'test_csrf_token';
        $_FILES['csv_file'] = [
            'name'     => 'partial.csv',
            'type'     => 'text/csv',
            'tmp_name' => $tmpFile,
            'error'    => UPLOAD_ERR_OK,
            'size'     => strlen($csvContent),
        ];

        try {
            $this->controller->importCsv();
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('REDIRECT: layanan.php', $e->getMessage());
        }

        // Row 1 MUST be inserted
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM layanan WHERE nama_layanan = 'Layanan Valid'");
        $this->assertSame(1, (int)$stmt->fetchColumn());

        // Row 2 & 3 MUST NOT be inserted
        $stmt2 = $this->pdo->query("SELECT COUNT(*) FROM layanan WHERE nama_layanan = 'Layanan Kategori Salah'");
        $this->assertSame(0, (int)$stmt2->fetchColumn());

        $stmt3 = $this->pdo->query("SELECT COUNT(*) FROM layanan WHERE nama_layanan = 'Layanan URL Rusak'");
        $this->assertSame(0, (int)$stmt3->fetchColumn());

        // Flash warning must detail the errors
        $warning = flash('warning');
        $this->assertNotEmpty($warning);
        $this->assertStringContainsString('Berhasil mengimpor 1 layanan', $warning);
        $this->assertStringContainsString('2 baris gagal/dilewati', $warning);
        $this->assertStringContainsString("Kategori 'Kategori Gaib' tidak ditemukan", $warning);
        $this->assertStringContainsString("URL tidak valid", $warning);

        @unlink($tmpFile);
    }

    public function testImportCsvRejectsNonCsvFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'txt_test_');
        file_put_contents($tmpFile, "Some text content");

        $_POST['csrf_token'] = 'test_csrf_token';
        $_FILES['csv_file'] = [
            'name'     => 'malicious.php',
            'type'     => 'application/x-php',
            'tmp_name' => $tmpFile,
            'error'    => UPLOAD_ERR_OK,
            'size'     => 15,
        ];

        try {
            $this->controller->importCsv();
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('REDIRECT: layanan.php', $e->getMessage());
        }

        $error = flash('error');
        $this->assertNotEmpty($error);
        $this->assertStringContainsString('Hanya berkas berformat .csv yang diperbolehkan', $error);

        @unlink($tmpFile);
    }
}
