<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Api\LegacyApiController;
use App\Http\Controllers\Api\V1\ApiController as V1ApiController;
use App\Services\LayananService;
use App\Support\Database;
use PDO;
use PHPUnit\Framework\TestCase;

class LayananServiceAndApiTest extends TestCase
{
    private PDO $pdo;
    private LegacyApiController $legacyApi;
    private V1ApiController $v1Api;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE kategori (
                id_kategori INTEGER PRIMARY KEY AUTOINCREMENT,
                nama_kategori TEXT NOT NULL,
                deskripsi TEXT NULL,
                is_active INTEGER NOT NULL DEFAULT 1
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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Seed kategori: 1 active, 1 inactive
        $this->pdo->exec("
            INSERT INTO kategori (id_kategori, nama_kategori, is_active)
            VALUES (1, 'Statistik Sosial', 1),
                   (2, 'Kategori Nonaktif', 0)
        ");

        // Seed layanan:
        // 1. Published + Active Kategori (should appear)
        // 2. Pending + Active Kategori (should NOT appear)
        // 3. Draft + Active Kategori (should NOT appear)
        // 4. Published + Inactive Kategori (should NOT appear)
        $this->pdo->exec("
            INSERT INTO layanan (id_layanan, id_kategori, nama_layanan, deskripsi_layanan, url, logo, akses, vpn, status, search_aliases, updated_at)
            VALUES (1, 1, 'Portal Sirusa', 'Sistem Rujukan Statistik', 'https://sirusa.bps.go.id', 'logo.png', 'Public', 'No', 'published', 'sirusa, portal, rujukan, BPS', '2026-10-09 10:00:00'),
                   (2, 1, 'Layanan Pending', 'Deskripsi Pending', 'https://pending.bps.go.id', NULL, 'Public', 'No', 'pending', 'pending, test', '2026-10-09 10:01:00'),
                   (3, 1, 'Layanan Draft', 'Deskripsi Draft', 'https://draft.bps.go.id', NULL, 'Public', 'No', 'draft', 'draft', '2026-10-09 10:02:00'),
                   (4, 2, 'Layanan Kategori Mati', 'Deskripsi', 'https://mati.bps.go.id', NULL, 'Public', 'No', 'published', 'mati', '2026-10-09 10:03:00')
        ");

        Database::setPdo($this->pdo);

        $this->legacyApi = new LegacyApiController();
        $this->v1Api = new V1ApiController();

        $_SERVER['HTTP_X_API_KEY'] = env('API_KEY_APP_B') ?: env('BPS_API_KEY') ?: 'test_key';
        $_GET = [];
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_API_KEY']);
        $_GET = [];
    }

    public function testNormalizeAliasesRemovesDuplicatesAndLowercases(): void
    {
        $raw = " Portal, SIRUSA, portal , statistik Daerah, , Sirusa , BPS ";
        $normalized = LayananService::normalizeAliases($raw);

        $this->assertSame("portal, sirusa, statistik daerah, bps", $normalized);

        $this->assertSame("", LayananService::normalizeAliases(null));
        $this->assertSame("", LayananService::normalizeAliases("   "));
        $this->assertSame("", LayananService::normalizeAliases(",, , "));
    }

    public function testParseAliasesToArrayReturnsCleanArray(): void
    {
        $stored = "portal, sirusa, statistik daerah, bps";
        $array = LayananService::parseAliasesToArray($stored);

        $this->assertSame(['portal', 'sirusa', 'statistik daerah', 'bps'], $array);
        $this->assertSame([], LayananService::parseAliasesToArray(null));
        $this->assertSame([], LayananService::parseAliasesToArray(""));
    }

    public function testLegacyApiLayananOnlyReturnsPublishedAndActiveCategory(): void
    {
        $jsonStr = $this->legacyApi->layanan();
        $res = json_decode($jsonStr, true);

        $this->assertTrue($res['status']);
        $this->assertIsArray($res['data']);
        $this->assertCount(1, $res['data']); // Only ID 1 should be returned!

        $item = $res['data'][0];
        $this->assertSame(1, $item['id_layanan']);
        $this->assertSame('Portal Sirusa', $item['nama_layanan']);
        $this->assertSame('Statistik Sosial', $item['nama_kategori']);

        // Check new fields
        $this->assertArrayHasKey('aliases', $item);
        $this->assertIsArray($item['aliases']);
        $this->assertSame(['sirusa', 'portal', 'rujukan', 'bps'], $item['aliases']);

        $this->assertArrayHasKey('updated_at', $item);
        $this->assertSame('2026-10-09 10:00:00', $item['updated_at']);

        // Ensure backward compatibility contract: old fields must exist
        $this->assertArrayHasKey('id_layanan', $item);
        $this->assertArrayHasKey('id_kategori', $item);
        $this->assertArrayHasKey('nama_layanan', $item);
        $this->assertArrayHasKey('deskripsi_layanan', $item);
        $this->assertArrayHasKey('url', $item);
        $this->assertArrayHasKey('logo', $item);
        $this->assertArrayHasKey('akses', $item);
        $this->assertArrayHasKey('vpn', $item);
        $this->assertArrayHasKey('nama_kategori', $item);
    }

    public function testLegacyApiSingleLayananDetailReturnsAliasesAndUpdatedAt(): void
    {
        $_GET['id'] = '1';
        $jsonStr = $this->legacyApi->layanan();
        $res = json_decode($jsonStr, true);

        $this->assertTrue($res['status']);
        $this->assertSame(1, $res['data']['id_layanan']);
        $this->assertSame(['sirusa', 'portal', 'rujukan', 'bps'], $res['data']['aliases']);
        $this->assertSame('2026-10-09 10:00:00', $res['data']['updated_at']);
    }

    public function testLegacyApiPendingOrDraftReturns404(): void
    {
        $_GET['id'] = '2'; // Pending
        $jsonStr = $this->legacyApi->layanan();
        $res = json_decode($jsonStr, true);
        $this->assertFalse($res['status']);
        $this->assertSame(404, http_response_code());

        $_GET['id'] = '3'; // Draft
        $jsonStr = $this->legacyApi->layanan();
        $res = json_decode($jsonStr, true);
        $this->assertFalse($res['status']);
        $this->assertSame(404, http_response_code());
    }

    public function testV1ApiLayananReturnsAliasesAndUpdatedAt(): void
    {
        $jsonStr = $this->v1Api->layanan();
        $res = json_decode($jsonStr, true);

        $this->assertNull($res['error']);
        $this->assertIsArray($res['data']);
        $this->assertCount(1, $res['data']);

        $item = $res['data'][0];
        $this->assertSame(1, $item['id']);
        $this->assertSame('Portal Sirusa', $item['name']);
        $this->assertSame('published', $item['status']);
        $this->assertSame(['sirusa', 'portal', 'rujukan', 'bps'], $item['aliases']);
        $this->assertSame('2026-10-09 10:00:00', $item['updated_at']);
    }
}
