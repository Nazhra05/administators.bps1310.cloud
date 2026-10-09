<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Database;
use App\Support\Session;
use PDO;
use PHPUnit\Framework\TestCase;

class AccessibilityAndErrorPagesTest extends TestCase
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
                role TEXT NOT NULL DEFAULT 'ADMIN IT'
            )
        ");

        Database::setPdo($this->pdo);

        Session::start();
        Session::set('admin_id', 1);
        Session::set('admin_username', 'admin_test');
        Session::set('admin_role', 'ADMIN IT');
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }

    public function testErrorPage403DoesNotLeakTechnicalDetails(): void
    {
        $html = view('errors/403', [
            'title'   => '403 - Akses Ditolak',
            'message' => 'Anda tidak memiliki izin untuk mengakses halaman ini.',
        ], null);

        $this->assertStringContainsString('403 Forbidden', $html);
        $this->assertStringContainsString('Akses Ditolak', $html);
        $this->assertStringContainsString('Kembali ke Beranda', $html);

        // Technical leakage prevention
        $this->assertStringNotContainsString('Stack trace:', $html);
        $this->assertStringNotContainsString('PDOException', $html);
        $this->assertStringNotContainsString('SELECT * FROM', $html);
        $this->assertStringNotContainsString('c:\\xampp', strtolower($html));
        $this->assertStringNotContainsString('vendor/', $html);
    }

    public function testErrorPage404DoesNotLeakTechnicalDetails(): void
    {
        $html = view('errors/404', [
            'title'   => '404 - Halaman Tidak Ditemukan',
            'message' => 'Halaman atau berkas yang Anda tuju tidak ditemukan atau telah dipindahkan.',
        ], null);

        $this->assertStringContainsString('404 Error', $html);
        $this->assertStringContainsString('Halaman Tidak Ditemukan', $html);
        $this->assertStringContainsString('Kembali ke Beranda', $html);

        // Technical leakage prevention
        $this->assertStringNotContainsString('Stack trace:', $html);
        $this->assertStringNotContainsString('FastRoute', $html);
        $this->assertStringNotContainsString('Dispatcher', $html);
        $this->assertStringNotContainsString('c:\\xampp', strtolower($html));
    }

    public function testErrorPage500DoesNotLeakTechnicalDetails(): void
    {
        $testRequestId = 'req-test-uuid-12345';
        $html = view('errors/500', [
            'title'     => '500 - Kesalahan Server',
            'message'   => 'Sistem kami sedang mengalami kendala sementara. Tim administrator telah diberitahu dan sedang menangani masalah ini.',
            'requestId' => $testRequestId,
        ], null);

        $this->assertStringContainsString('500 Server Error', $html);
        $this->assertStringContainsString('Terjadi Kesalahan Server', $html);
        $this->assertStringContainsString('Request ID: ' . $testRequestId, $html);
        $this->assertStringContainsString('Kembali ke Beranda', $html);

        // Technical leakage prevention
        $this->assertStringNotContainsString('Stack trace:', $html);
        $this->assertStringNotContainsString('SQLSTATE', $html);
        $this->assertStringNotContainsString('PDOException', $html);
        $this->assertStringNotContainsString('throw new', $html);
        $this->assertStringNotContainsString('c:\\xampp', strtolower($html));
    }

    public function testFormLabelsHaveMatchingInputIdsInLayananViews(): void
    {
        $categories = [
            ['id_kategori' => 1, 'nama_kategori' => 'Kategori 1'],
        ];

        // 1. Layanan Create View
        $htmlCreate = view('layanan/create', [
            'categories' => $categories,
            'units'      => [],
            'user'       => ['role' => 'ADMIN IT'],
            'errors'     => [],
            'form'       => [],
        ], 'layouts/main');

        $this->assertFormLabelsMatchInputs($htmlCreate);

        // 2. Layanan Edit View
        $htmlEdit = view('layanan/edit', [
            'layanan'    => [
                'id_layanan'        => 99,
                'id_kategori'       => 1,
                'nama_layanan'      => 'Test Layanan',
                'deskripsi_layanan' => 'Deskripsi',
                'url'               => 'https://example.com',
                'akses'             => 'Public',
                'vpn'               => 'No',
                'logo'              => null,
            ],
            'categories' => $categories,
            'units'      => [],
            'user'       => ['role' => 'ADMIN IT'],
            'errors'     => [],
        ], 'layouts/main');

        $this->assertFormLabelsMatchInputs($htmlEdit);
    }

    public function testFormLabelsHaveMatchingInputIdsInKategoriViews(): void
    {
        // 1. Kategori Create View
        $htmlCreate = view('kategori/create', [
            'nama_kategori' => '',
            'errors'        => [],
        ], 'layouts/main');

        $this->assertFormLabelsMatchInputs($htmlCreate);

        // 2. Kategori Edit View
        $htmlEdit = view('kategori/edit', [
            'kategori' => [
                'id_kategori'   => 1,
                'nama_kategori' => 'Ekonomi',
            ],
            'errors'   => [],
        ], 'layouts/main');

        $this->assertFormLabelsMatchInputs($htmlEdit);
    }

    public function testSkipLinkAndNavbarAriaPresentInMainLayout(): void
    {
        $html = view('dashboard/index', [
            'title'            => 'Dashboard Admin',
            'subtitle'         => 'Ringkasan operasional',
            'categories'       => [],
            'services'         => [],
            'metrics'          => [
                'total_layanan'  => 0,
                'total_publik'   => 0,
                'total_vpn'      => 0,
                'total_pending'  => 0,
                'total_kategori' => 0,
            ],
            'recentActivities' => [],
            'filterKategori'   => '',
            'user'             => ['role' => 'ADMIN IT'],
        ], 'layouts/main');

        // Skip to main content link exists
        $this->assertStringContainsString('class="skip-link"', $html);
        $this->assertStringContainsString('href="#mainContent"', $html);

        // Main content landmark exists
        $this->assertStringContainsString('id="mainContent"', $html);

        // Admin status button has ARIA attributes
        $this->assertStringContainsString('aria-haspopup="true"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);

        // Table container is responsive
        $this->assertStringContainsString('class="table-container"', $html);
    }

    /**
     * Helper to verify all <label for="xxx"> have a corresponding id="xxx"
     */
    private function assertFormLabelsMatchInputs(string $html): void
    {
        preg_match_all('/<label\s+[^>]*for=["\']([^"\']+)["\']/i', $html, $matches);
        $this->assertNotEmpty($matches[1], 'View should contain <label for="..."> elements');

        foreach ($matches[1] as $labelFor) {
            $hasMatchingId = (bool)preg_match('/<[a-z0-9]+\s+[^>]*id=["\']' . preg_quote($labelFor, '/') . '["\']/i', $html);
            $this->assertTrue(
                $hasMatchingId,
                "Failed asserting that <label for=\"{$labelFor}\"> has a matching id=\"{$labelFor}\" in the document."
            );
        }
    }
}
