<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\ApiController;
use App\Support\Database;
use PDO;
use PHPUnit\Framework\TestCase;

class PreferencesAndBookmarksTest extends TestCase
{
    private PDO $pdo;
    private ApiController $api;

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
                failed_attempts INTEGER NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                last_login_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE sessions (
                token TEXT PRIMARY KEY,
                user_id INTEGER NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                ip TEXT NULL,
                user_agent TEXT NULL
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
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE user_preferences (
                user_id INTEGER PRIMARY KEY,
                pinned_layanan TEXT NULL,
                search_history TEXT NULL,
                theme TEXT DEFAULT 'light',
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE user_bookmarks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                url TEXT NOT NULL,
                note TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Seed users
        $this->pdo->exec("
            INSERT INTO admin (id_admin, username, email, role, is_active, status)
            VALUES (1, 'user_a', 'user_a@bps.go.id', 'USER APP PORTAL', 1, 'approved'),
                   (2, 'user_b', 'user_b@bps.go.id', 'USER APP PORTAL', 1, 'approved')
        ");

        // Seed sessions (8 hours expiry)
        $future = date('Y-m-d H:i:s', time() + 28800);
        $this->pdo->exec("
            INSERT INTO sessions (token, user_id, expires_at)
            VALUES ('token_user_a', 1, '{$future}'),
                   ('token_user_b', 2, '{$future}')
        ");

        // Seed layanan
        $this->pdo->exec("
            INSERT INTO layanan (id_layanan, id_kategori, nama_layanan, deskripsi_layanan, url, status)
            VALUES (101, 1, 'Layanan Statistik Sosial', 'Deskripsi 101', 'https://bps.go.id/sosial', 'published'),
                   (102, 1, 'Layanan Diseminasi', 'Deskripsi 102', 'https://bps.go.id/diseminasi', 'published'),
                   (103, 1, 'Layanan Draft', 'Deskripsi 103', 'https://bps.go.id/draft', 'draft')
        ");

        Database::setPdo($this->pdo);
        $this->api = new ApiController();

        $_SERVER['HTTP_X_SESSION'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        $_SERVER['HTTP_X_SESSION'] = '';
    }

    public function testGetPreferencesReturnsDefaultsWhenEmpty(): void
    {
        $_SERVER['HTTP_X_SESSION'] = 'token_user_a';
        $res = $this->api->getPreferences();
        $json = json_decode($res, true);

        $this->assertNull($json['error']);
        $this->assertSame([], $json['data']['pinned_layanan']);
        $this->assertSame([], $json['data']['search_history']);
        $this->assertSame('light', $json['data']['theme']);
    }

    public function testUpdatePreferencesValidatesTheme(): void
    {
        $_SERVER['HTTP_X_SESSION'] = 'token_user_a';
        $res = $this->api->updatePreferences([], [
            'theme' => 'invalid_theme',
        ]);
        $json = json_decode($res, true);

        $this->assertSame('validation_failed', $json['error']['code']);
        $this->assertStringContainsString('Tema tidak valid', $json['error']['message']);
    }

    public function testUpdatePreferencesValidatesPinnedLayananExistence(): void
    {
        $_SERVER['HTTP_X_SESSION'] = 'token_user_a';

        // 999 does not exist
        $res = $this->api->updatePreferences([], [
            'pinned_layanan' => [101, 999],
        ]);
        $json = json_decode($res, true);

        $this->assertSame('validation_failed', $json['error']['code']);
        $this->assertStringContainsString('999 tidak ditemukan', $json['error']['message']);

        // 103 is not published (draft)
        $resDraft = $this->api->updatePreferences([], [
            'pinned_layanan' => [103],
        ]);
        $jsonDraft = json_decode($resDraft, true);
        $this->assertSame('validation_failed', $jsonDraft['error']['code']);
        $this->assertStringContainsString('103 tidak ditemukan atau belum dipublikasikan', $jsonDraft['error']['message']);
    }

    public function testUpdatePreferencesEnforcesLimitsAndSavesSuccessfully(): void
    {
        $_SERVER['HTTP_X_SESSION'] = 'token_user_a';

        $res = $this->api->updatePreferences([], [
            'theme'          => 'dark',
            'pinned_layanan' => [101, 102],
            'search_history' => ['inflasi solsel', 'kemiskinan 2025', 'data kependudukan'],
        ]);
        $json = json_decode($res, true);

        $this->assertNull($json['error']);
        $this->assertSame('dark', $json['data']['theme']);
        $this->assertEquals([101, 102], $json['data']['pinned_layanan']);
        $this->assertCount(3, $json['data']['search_history']);

        // Verify retrieval matches
        $getRes = $this->api->getPreferences();
        $getJson = json_decode($getRes, true);
        $this->assertSame('dark', $getJson['data']['theme']);
        $this->assertEquals([101, 102], $getJson['data']['pinned_layanan']);
        $this->assertSame(['inflasi solsel', 'kemiskinan 2025', 'data kependudukan'], $getJson['data']['search_history']);
    }

    public function testBookmarksCrudAndStrictUserIsolation(): void
    {
        // 1. User A creates a bookmark
        $_SERVER['HTTP_X_SESSION'] = 'token_user_a';
        $createRes = $this->api->createBookmark([], [
            'title' => 'Portal Data Solsel',
            'url'   => 'https://solselkab.bps.go.id',
            'note'  => '<b>Catatan penting</b> untuk portal internal.',
        ]);
        $created = json_decode($createRes, true);

        $this->assertNull($created['error']);
        $this->assertSame(201, http_response_code());
        $bookmarkId = (int)$created['data']['id'];
        $this->assertGreaterThan(0, $bookmarkId);
        $this->assertSame('Portal Data Solsel', $created['data']['title']);
        $this->assertSame('https://solselkab.bps.go.id', $created['data']['url']);
        // Note must be plain text (tags stripped)
        $this->assertSame('Catatan penting untuk portal internal.', $created['data']['note']);

        // 2. User A reads bookmarks -> returns 1 bookmark
        $listRes = $this->api->getBookmarks();
        $list = json_decode($listRes, true);
        $this->assertCount(1, $list['data']);
        $this->assertSame($bookmarkId, $list['data'][0]['id']);

        // 3. User B reads bookmarks -> returns 0 bookmarks (isolation!)
        $_SERVER['HTTP_X_SESSION'] = 'token_user_b';
        $userBListRes = $this->api->getBookmarks();
        $userBList = json_decode($userBListRes, true);
        $this->assertCount(0, $userBList['data']);

        // 4. User B attempts to UPDATE User A's bookmark -> 404 forbidden/not found
        $updateAttempt = $this->api->updateBookmark(['id' => (string)$bookmarkId], [
            'title' => 'Hacked by User B',
            'url'   => 'https://hacker.com',
        ]);
        $updateAttemptJson = json_decode($updateAttempt, true);
        $this->assertSame(404, http_response_code());
        $this->assertSame('not_found', $updateAttemptJson['error']['code']);

        // 5. User B attempts to DELETE User A's bookmark -> 404 not found
        $deleteAttempt = $this->api->deleteBookmark(['id' => (string)$bookmarkId]);
        $deleteAttemptJson = json_decode($deleteAttempt, true);
        $this->assertSame(404, http_response_code());
        $this->assertSame('not_found', $deleteAttemptJson['error']['code']);

        // 6. User A updates their own bookmark -> 200 OK
        $_SERVER['HTTP_X_SESSION'] = 'token_user_a';
        $updateSuccess = $this->api->updateBookmark(['id' => (string)$bookmarkId], [
            'title' => 'Portal Data Solsel Resmi',
            'url'   => 'https://solselkab.bps.go.id/v2',
            'note'  => 'Catatan diperbarui',
        ]);
        $updateJson = json_decode($updateSuccess, true);
        $this->assertNull($updateJson['error']);
        $this->assertSame('Portal Data Solsel Resmi', $updateJson['data']['title']);

        // 7. User A deletes their own bookmark -> 200 OK
        $deleteSuccess = $this->api->deleteBookmark(['id' => (string)$bookmarkId]);
        $deleteJson = json_decode($deleteSuccess, true);
        $this->assertNull($deleteJson['error']);

        // Verify bookmark is gone
        $afterDeleteList = json_decode($this->api->getBookmarks(), true);
        $this->assertCount(0, $afterDeleteList['data']);
    }

    public function testBookmarkValidationRules(): void
    {
        $_SERVER['HTTP_X_SESSION'] = 'token_user_a';

        // Empty title
        $res = $this->api->createBookmark([], [
            'title' => '',
            'url'   => 'https://bps.go.id',
        ]);
        $json = json_decode($res, true);
        $this->assertSame('validation_failed', $json['error']['code']);
        $this->assertStringContainsString('Judul bookmark wajib diisi', $json['error']['message']);

        // Invalid URL scheme (e.g. javascript: or ftp://)
        $resUrl = $this->api->createBookmark([], [
            'title' => 'Test XSS',
            'url'   => 'javascript:alert(1)',
        ]);
        $jsonUrl = json_decode($resUrl, true);
        $this->assertSame('validation_failed', $jsonUrl['error']['code']);
        $this->assertStringContainsString('URL tidak valid', $jsonUrl['error']['message']);
    }
}
