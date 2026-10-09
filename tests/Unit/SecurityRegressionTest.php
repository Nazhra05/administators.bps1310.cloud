<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Validator;
use App\Support\SecurityHeaders;
use PDO;
use PHPUnit\Framework\TestCase;

class SecurityRegressionTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE layanan (
                id_layanan INTEGER PRIMARY KEY AUTOINCREMENT,
                nama_layanan TEXT NOT NULL,
                deskripsi_layanan TEXT,
                url TEXT,
                status TEXT DEFAULT 'published'
            )
        ");

        $this->pdo->exec("
            INSERT INTO layanan (nama_layanan, deskripsi_layanan, url, status)
            VALUES ('Layanan Resmi 1', 'Deskripsi resmi', 'https://bps.go.id', 'published'),
                   ('Layanan Resmi 2', 'Deskripsi resmi 2', 'https://bps.go.id/2', 'published')
        ");
    }

    public function testValidatorIntegerCasting(): void
    {
        $this->assertSame(42, Validator::int('42'));
        $this->assertSame(0, Validator::int('invalid'));
        $this->assertSame(0, Validator::int(null));
        $this->assertSame(10, Validator::int('abc', 10));
        $this->assertSame(5, Validator::int('5', 0, 1, 10));
        $this->assertSame(0, Validator::int('15', 0, 1, 10)); // Exceeds max
        $this->assertSame(0, Validator::int('-5', 0, 1, 10)); // Below min
    }

    public function testValidatorUrlSchemes(): void
    {
        // Allowed http/https
        $this->assertSame('https://example.com/api', Validator::url('https://example.com/api'));
        $this->assertSame('http://internal.bps.test', Validator::url('http://internal.bps.test'));

        // Dangerous schemes rejected
        $this->assertNull(Validator::url('javascript:alert(1)'));
        $this->assertNull(Validator::url('data:text/html,<script>alert(1)</script>'));
        $this->assertNull(Validator::url('file:///etc/passwd'));
        $this->assertNull(Validator::url('ftp://ftp.example.com'));
        $this->assertNull(Validator::url('not-a-url'));
        $this->assertNull(Validator::url(''));
    }

    public function testValidatorStringAndEnum(): void
    {
        $this->assertSame('Hello', Validator::string('  Hello  ', 10));
        $this->assertSame('12345', Validator::string('1234567890', 5));
        $this->assertSame('published', Validator::enum('published', ['draft', 'pending', 'published'], 'draft'));
        $this->assertSame('draft', Validator::enum('malicious_status', ['draft', 'pending', 'published'], 'draft'));
    }

    public function testXssEscapingWithHelper(): void
    {
        $xssPayload = "<script>alert('XSS')</script>";
        $escaped = e($xssPayload);

        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
        $this->assertStringContainsString('&quot;', e('"test"'));
    }

    public function testSqlInjectionSafetyWithPreparedStatement(): void
    {
        $sqliPayload = "' OR 1=1 --";

        // Query using prepared statement
        $stmt = $this->pdo->prepare("SELECT * FROM layanan WHERE nama_layanan = ? AND status = 'published'");
        $stmt->execute([$sqliPayload]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // SQL injection payload treated as literal search string; does not return all rows
        $this->assertEmpty($results);

        // Normal query works as expected
        $stmtNormal = $this->pdo->prepare("SELECT * FROM layanan WHERE nama_layanan = ? AND status = 'published'");
        $stmtNormal->execute(['Layanan Resmi 1']);
        $normalResults = $stmtNormal->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(1, $normalResults);
        $this->assertSame('Layanan Resmi 1', $normalResults[0]['nama_layanan']);
    }

    public function testCspNonceGeneration(): void
    {
        $nonce1 = csp_nonce();
        $nonce2 = csp_nonce();

        $this->assertNotEmpty($nonce1);
        $this->assertSame($nonce1, $nonce2, 'CSP nonce must remain consistent per request lifecycle');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $nonce1);
    }
}
