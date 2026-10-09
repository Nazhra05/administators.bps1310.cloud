<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Support\Database;
use App\Support\Session;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
    }

    public function testDatabasePdoIsAvailable(): void
    {
        $pdo = Database::pdo();
        $this->assertInstanceOf(\PDO::class, $pdo);

        $stmt = $pdo->query('SELECT 1 as val');
        $row = $stmt->fetch();
        $this->assertSame(1, (int)$row['val']);
    }

    public function testCsrfTokenAndVerification(): void
    {
        $token = csrf_token();
        $this->assertSame(64, strlen($token));
        $this->assertTrue(csrf_verify($token));
        $this->assertFalse(csrf_verify('token_palsu_123'));
    }

    public function testHelpersSanitizationAndUrls(): void
    {
        $escaped = e('<script>alert("xss")</script>');
        $this->assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $escaped);

        $this->assertStringStartsWith('/', url('/layanan'));
    }

    public function testFlashMessaging(): void
    {
        flash('pesan', 'Operasi berhasil');
        $this->assertSame('Operasi berhasil', flash('pesan'));
        $this->assertNull(flash('pesan')); // Once consumed, it is cleared
    }

    public function testErrorLoggingWritesToAppLog(): void
    {
        $logFile = \App\Support\Logger::getLogFile();
        $testMarker = 'TEST_LOG_' . bin2hex(random_bytes(8));

        \App\Support\Logger::error($testMarker, ['component' => 'bootstrap_test']);

        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString($testMarker, $content);
    }
}
