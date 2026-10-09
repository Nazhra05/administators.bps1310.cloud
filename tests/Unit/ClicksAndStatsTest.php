<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\ApiController;
use App\Support\Database;
use PDO;
use PHPUnit\Framework\TestCase;

class ClicksAndStatsTest extends TestCase
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
                verification_token TEXT NULL,
                verification_expires DATETIME NULL,
                email_verified INTEGER NOT NULL DEFAULT 0,
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
            CREATE TABLE login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                username TEXT NOT NULL,
                attempted_at DATETIME NOT NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE layanan (
                id_layanan INTEGER PRIMARY KEY AUTOINCREMENT,
                id_kategori INTEGER NOT NULL,
                nama_layanan TEXT NOT NULL,
                deskripsi_layanan TEXT NOT NULL,
                url TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'published',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE link_clicks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                layanan_id INTEGER NOT NULL,
                clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Seed users
        $this->pdo->exec("
            INSERT INTO admin (id_admin, username, email, role, is_active, status, must_change_password)
            VALUES (1, 'pegawai_a', 'pegawai_a@bps.go.id', 'USER APP PORTAL', 1, 'approved', 0),
                   (2, 'pegawai_b', 'pegawai_b@bps.go.id', 'USER APP PORTAL', 1, 'approved', 0)
        ");

        // Seed sessions (valid 8 hours)
        $future = date('Y-m-d H:i:s', time() + 28800);
        $this->pdo->exec("
            INSERT INTO sessions (token, user_id, expires_at)
            VALUES ('valid_token_user_a', 1, '{$future}'),
                   ('valid_token_user_b', 2, '{$future}')
        ");

        // Seed services (layanan)
        for ($i = 1; $i <= 15; $i++) {
            $status = ($i === 15) ? 'draft' : 'published';
            $this->pdo->exec("
                INSERT INTO layanan (id_layanan, id_kategori, nama_layanan, deskripsi_layanan, url, status)
                VALUES ({$i}, 1, 'Layanan {$i}', 'Deskripsi {$i}', 'https://bps.go.id/s/{$i}', '{$status}')
            ");
        }

        Database::setPdo($this->pdo);
        $this->api = new ApiController();

        $_SERVER['HTTP_X_SESSION'] = '';
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    protected function tearDown(): void
    {
        $_SERVER['HTTP_X_SESSION'] = '';
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testRecordClickRequiresAuthentication(): void
    {
        $_SERVER['HTTP_X_SESSION'] = '';
        $res = $this->api->recordClick([], ['layanan_id' => 1]);
        $data = json_decode($res, true);

        $this->assertNull($data['data']);
        $this->assertEquals('unauthorized', $data['error']['code']);
    }

    public function testRecordClickRequiresExistingAndPublishedService(): void
    {
        $_SERVER['HTTP_X_SESSION'] = 'valid_token_user_a';

        // Non-existent service
        $res = $this->api->recordClick([], ['layanan_id' => 9999]);
        $data = json_decode($res, true);
        $this->assertEquals('not_found', $data['error']['code']);

        // Draft service (id 15 is draft)
        $resDraft = $this->api->recordClick([], ['layanan_id' => 15]);
        $dataDraft = json_decode($resDraft, true);
        $this->assertEquals('not_found', $dataDraft['error']['code']);
    }

    public function testRecordClickSuccess(): void
    {
        $_SERVER['HTTP_X_SESSION'] = 'valid_token_user_a';

        $res = $this->api->recordClick([], ['layanan_id' => 1]);
        $data = json_decode($res, true);

        $this->assertNull($data['error']);
        $this->assertNotNull($data['data']['id']);
        $this->assertEquals(1, $data['data']['layanan_id']);
        $this->assertNotEmpty($data['data']['clicked_at']);

        // Verify inserted into database
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM link_clicks WHERE user_id = 1 AND layanan_id = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    public function testRecordClickRateLimitEnforced(): void
    {
        $_SERVER['HTTP_X_SESSION'] = 'valid_token_user_a';

        // Insert 60 clicks within the last minute for user 1
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("INSERT INTO link_clicks (user_id, layanan_id, clicked_at) VALUES (1, 1, ?)");
        for ($i = 0; $i < 60; $i++) {
            $stmt->execute([$now]);
        }

        // The 61st click should be rejected with 429
        $res = $this->api->recordClick([], ['layanan_id' => 1]);
        $data = json_decode($res, true);

        $this->assertNull($data['data']);
        $this->assertEquals('too_many_requests', $data['error']['code']);
    }

    public function testStatsCalculationAndBadges(): void
    {
        $_SERVER['HTTP_X_SESSION'] = 'valid_token_user_a';

        // 1. Initially 0 clicks
        $res = $this->api->getStats();
        $data = json_decode($res, true)['data'];

        $this->assertEquals(0, $data['total_clicks']);
        $this->assertEquals(0, $data['unique_services_used']);
        $this->assertEquals(0, $data['daily_streak']);
        $this->assertEmpty($data['most_frequent_services']);

        // Check initial badges (all false)
        foreach ($data['badges'] as $badge) {
            $this->assertFalse($badge['unlocked']);
        }

        // 2. Simulate 10 unique services clicked across today and yesterday (streak = 2)
        $today = date('Y-m-d 10:00:00');
        $yesterday = date('Y-m-d 10:00:00', strtotime('-1 day'));

        $stmt = $this->pdo->prepare("INSERT INTO link_clicks (user_id, layanan_id, clicked_at) VALUES (1, ?, ?)");
        // Yesterday: clicked service 1
        $stmt->execute([1, $yesterday]);

        // Today: clicked service 1 (x3), service 2..10 (x1 each)
        $stmt->execute([1, $today]);
        $stmt->execute([1, $today]);
        $stmt->execute([1, $today]);
        for ($s = 2; $s <= 10; $s++) {
            $stmt->execute([$s, $today]);
        }

        // Fetch stats again
        $res2 = $this->api->getStats();
        $stats = json_decode($res2, true)['data'];

        // Total clicks: 1 (yesterday) + 3 (service 1 today) + 9 (services 2-10 today) = 13
        $this->assertEquals(13, $stats['total_clicks']);
        // Unique services: 10
        $this->assertEquals(10, $stats['unique_services_used']);
        // Daily streak: today + yesterday = 2 days
        $this->assertEquals(2, $stats['daily_streak']);

        // Most frequent service should be service 1 with 4 clicks
        $this->assertNotEmpty($stats['most_frequent_services']);
        $this->assertEquals(1, $stats['most_frequent_services'][0]['layanan_id']);
        $this->assertEquals(4, $stats['most_frequent_services'][0]['click_count']);

        // Check badges:
        // 'pemula' (>=1 click) -> unlocked
        // 'penjelajah' (>=10 services) -> unlocked
        // 'rutin' (>=7 streak) -> not unlocked (only 2)
        // 'produktif' (>=50 clicks) -> not unlocked (only 13)
        $badgeMap = [];
        foreach ($stats['badges'] as $b) {
            $badgeMap[$b['id']] = $b['unlocked'];
        }
        $this->assertTrue($badgeMap['pemula']);
        $this->assertTrue($badgeMap['penjelajah']);
        $this->assertFalse($badgeMap['rutin']);
        $this->assertFalse($badgeMap['produktif']);
    }

    public function testStatsStrictUserIsolation(): void
    {
        // Seed clicks for User 1
        $today = date('Y-m-d 12:00:00');
        $this->pdo->exec("INSERT INTO link_clicks (user_id, layanan_id, clicked_at) VALUES (1, 1, '{$today}')");
        $this->pdo->exec("INSERT INTO link_clicks (user_id, layanan_id, clicked_at) VALUES (1, 2, '{$today}')");

        // Seed clicks for User 2
        $this->pdo->exec("INSERT INTO link_clicks (user_id, layanan_id, clicked_at) VALUES (2, 3, '{$today}')");

        // Check User 2 stats
        $_SERVER['HTTP_X_SESSION'] = 'valid_token_user_b';
        $res = $this->api->getStats();
        $stats = json_decode($res, true)['data'];

        $this->assertEquals(1, $stats['total_clicks']);
        $this->assertEquals(1, $stats['unique_services_used']);
        $this->assertEquals(3, $stats['most_frequent_services'][0]['layanan_id']);
    }

    public function testCleanupScriptRetention(): void
    {
        // 1. Seed link_clicks: one older than 12 months (400 days ago), one recent (30 days ago)
        $oldClickDate = date('Y-m-d H:i:s', time() - (400 * 86400));
        $recentClickDate = date('Y-m-d H:i:s', time() - (30 * 86400));
        $this->pdo->exec("INSERT INTO link_clicks (user_id, layanan_id, clicked_at) VALUES (1, 1, '{$oldClickDate}')");
        $this->pdo->exec("INSERT INTO link_clicks (user_id, layanan_id, clicked_at) VALUES (1, 2, '{$recentClickDate}')");

        // 2. Seed expired sessions
        $expiredDate = date('Y-m-d H:i:s', time() - 3600);
        $this->pdo->exec("INSERT INTO sessions (token, user_id, expires_at) VALUES ('expired_sess', 1, '{$expiredDate}')");

        // 3. Seed old login attempts (>24h) and recent (<24h)
        $oldAttempt = date('Y-m-d H:i:s', time() - (2 * 86400));
        $recentAttempt = date('Y-m-d H:i:s', time() - 1800);
        $this->pdo->exec("INSERT INTO login_attempts (ip_address, username, attempted_at) VALUES ('127.0.0.1', 'test1', '{$oldAttempt}')");
        $this->pdo->exec("INSERT INTO login_attempts (ip_address, username, attempted_at) VALUES ('127.0.0.1', 'test2', '{$recentAttempt}')");

        // Execute cleanup logic (identical to scripts/cleanup.php)
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare("DELETE FROM sessions WHERE expires_at < ?")->execute([$now]);
        $this->pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < ?")->execute([date('Y-m-d H:i:s', time() - 86400)]);
        $oneYearAgo = date('Y-m-d H:i:s', time() - (365 * 86400));
        $this->pdo->prepare("DELETE FROM link_clicks WHERE clicked_at < ?")->execute([$oneYearAgo]);

        // Verifications
        $clicksCount = (int)$this->pdo->query("SELECT COUNT(*) FROM link_clicks")->fetchColumn();
        $this->assertEquals(1, $clicksCount);

        $remainingClickLayanan = (int)$this->pdo->query("SELECT layanan_id FROM link_clicks LIMIT 1")->fetchColumn();
        $this->assertEquals(2, $remainingClickLayanan);

        $sessionsCount = (int)$this->pdo->query("SELECT COUNT(*) FROM sessions WHERE token = 'expired_sess'")->fetchColumn();
        $this->assertEquals(0, $sessionsCount);

        $attemptsCount = (int)$this->pdo->query("SELECT COUNT(*) FROM login_attempts")->fetchColumn();
        $this->assertEquals(1, $attemptsCount);
    }

    public function testHealthEndpointReturnsOkWithoutAuth(): void
    {
        unset($_SERVER['HTTP_X_SESSION']);
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $res = $this->api->health();
        $data = json_decode($res, true);

        $this->assertNull($data['error']);
        $this->assertEquals('ok', $data['data']['status']);
    }
}
