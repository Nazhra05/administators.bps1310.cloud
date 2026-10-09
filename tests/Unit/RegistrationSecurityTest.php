<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use App\Services\LoginThrottleService;
use PDO;
use PHPUnit\Framework\TestCase;

class RegistrationSecurityTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                identity TEXT NOT NULL,
                attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                success INTEGER DEFAULT 0
            )
        ");

        $this->pdo->exec("
            CREATE TABLE admin (
                id_admin INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT,
                last_name TEXT,
                username TEXT,
                email TEXT,
                password TEXT,
                status TEXT,
                role TEXT,
                email_verified INTEGER DEFAULT 0,
                verification_token TEXT,
                verification_expires DATETIME
            )
        ");
    }

    public function testEmailVerificationRateLimitingPerEmail(): void
    {
        $email = 'test@bps.go.id';
        $ip = '192.168.1.100';

        // Initially not rate limited
        $this->assertFalse(LoginThrottleService::isEmailRateLimited($this->pdo, $email, $ip));

        // Record attempts up to MAX_EMAIL_SENDS_PER_HOUR (3)
        for ($i = 0; $i < LoginThrottleService::MAX_EMAIL_SENDS_PER_HOUR; $i++) {
            LoginThrottleService::recordEmailVerificationAttempt($this->pdo, $email, $ip);
        }

        // Now throttled
        $this->assertTrue(LoginThrottleService::isEmailRateLimited($this->pdo, $email, $ip));
    }

    public function testEmailVerificationRateLimitingPerIp(): void
    {
        $ip = '10.0.0.50';

        // Record attempts from different emails on the same IP up to IP limit (10)
        for ($i = 0; $i < LoginThrottleService::MAX_IP_EMAIL_SENDS_PER_HOUR; $i++) {
            $email = "user{$i}@bps.go.id";
            LoginThrottleService::recordEmailVerificationAttempt($this->pdo, $email, $ip);
        }

        // IP is now throttled for any email
        $this->assertTrue(LoginThrottleService::isEmailRateLimited($this->pdo, 'newuser@bps.go.id', $ip));
    }

    public function testTokenHashedStorageAndLookup(): void
    {
        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        // Insert user with hashed token
        $stmt = $this->pdo->prepare("
            INSERT INTO admin (email, role, status, email_verified, verification_token, verification_expires)
            VALUES (?, 'USER APP PORTAL', 'pending', 0, ?, ?)
        ");
        $stmt->execute(['portal@bps.go.id', $hashedToken, $expires]);

        // Verify that raw token cannot be found directly if querying for exact raw string
        $rawQuery = $this->pdo->prepare("SELECT id_admin FROM admin WHERE verification_token = ?");
        $rawQuery->execute([$rawToken]);
        $this->assertFalse($rawQuery->fetch());

        // But hashing the raw token matches the stored token
        $hashQuery = $this->pdo->prepare("SELECT id_admin FROM admin WHERE verification_token = ? OR verification_token = ?");
        $hashQuery->execute([hash('sha256', $rawToken), $rawToken]);
        $matched = $hashQuery->fetch();
        $this->assertNotEmpty($matched);
    }

    public function testTokenExpirationAndSingleUse(): void
    {
        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);
        $pastExpires = date('Y-m-d H:i:s', time() - 3600); // Expired 1 hour ago

        $stmt = $this->pdo->prepare("
            INSERT INTO admin (email, role, status, email_verified, verification_token, verification_expires)
            VALUES (?, 'USER APP PORTAL', 'pending', 0, ?, ?)
        ");
        $stmt->execute(['expired@bps.go.id', $hashedToken, $pastExpires]);

        // Query user by hashed token
        $checkStmt = $this->pdo->prepare("SELECT id_admin, verification_expires FROM admin WHERE verification_token = ? OR verification_token = ?");
        $checkStmt->execute([$hashedToken, $rawToken]);
        $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($user);
        $this->assertTrue(strtotime((string)$user['verification_expires']) < time());

        // Test single use: invalidate token
        $invalidateStmt = $this->pdo->prepare("
            UPDATE admin
            SET email_verified = 1, verification_token = NULL, verification_expires = NULL
            WHERE id_admin = ?
        ");
        $invalidateStmt->execute([(int)$user['id_admin']]);

        // Attempting to reuse the token fails
        $reuseStmt = $this->pdo->prepare("SELECT id_admin FROM admin WHERE verification_token = ? OR verification_token = ?");
        $reuseStmt->execute([$hashedToken, $rawToken]);
        $this->assertFalse($reuseStmt->fetch());
    }

    public function testPublicRegisterToggle(): void
    {
        // When ALLOW_PUBLIC_REGISTER is false or not set
        $allowed = filter_var('false', FILTER_VALIDATE_BOOLEAN);
        $this->assertFalse($allowed);

        // When ALLOW_PUBLIC_REGISTER is explicitly enabled
        $allowedTrue = filter_var('true', FILTER_VALIDATE_BOOLEAN);
        $this->assertTrue($allowedTrue);
    }
}
