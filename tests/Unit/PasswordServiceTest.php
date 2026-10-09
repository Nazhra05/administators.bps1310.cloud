<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PasswordService;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class PasswordServiceTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
    }

    public function testHashGeneratesValidBcryptCost10(): void
    {
        $hash = PasswordService::hash('P@ssword12345');

        $this->assertStringStartsWith('$2y$10$', $hash);
        $this->assertTrue(password_verify('P@ssword12345', $hash));
        $this->assertFalse(password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 10]));
    }

    public function testPolicyValidatesMinimumLength(): void
    {
        // When minimum length is 10
        $_ENV['PASSWORD_MIN_LENGTH'] = '10';

        $error = PasswordService::validatePolicy('short', 'someuser');
        $this->assertNotNull($error);
        $this->assertStringContainsString('minimal harus 10 karakter', $error);

        $valid = PasswordService::validatePolicy('Valid10Chars!', 'someuser');
        $this->assertNull($valid);

        unset($_ENV['PASSWORD_MIN_LENGTH']);
    }

    public function testPolicyPreventsPasswordMatchingUsername(): void
    {
        $error = PasswordService::validatePolicy('admin_solsel', 'admin_solsel');
        $this->assertNotNull($error);
        $this->assertStringContainsString('tidak boleh sama dengan username', $error);

        // Case-insensitive check
        $errorCase = PasswordService::validatePolicy('Admin_Solsel', 'admin_solsel');
        $this->assertNotNull($errorCase);
        $this->assertStringContainsString('tidak boleh sama dengan username', $errorCase);
    }

    public function testPolicyEnforcesMaximumLength(): void
    {
        $tooLong = str_repeat('a', 256);
        $error = PasswordService::validatePolicy($tooLong, 'username');
        $this->assertNotNull($error);
        $this->assertStringContainsString('maksimal 255 karakter', $error);
    }

    public function testVerifyModernBcrypt(): void
    {
        $hash = password_hash('Secret12345!', PASSWORD_BCRYPT, ['cost' => 10]);

        $this->assertTrue(PasswordService::verify('Secret12345!', $hash));
        $this->assertFalse(PasswordService::verify('WrongPassword', $hash));
        $this->assertFalse(PasswordService::verify('Secret12345!', ''));
    }

    public function testVerifyLegacySha256AndUpgrades(): void
    {
        $plain = 'old_sha_secret';
        $sha256Hash = hash('sha256', $plain);

        // Mock PDO to verify auto-upgrade
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params) {
                // First parameter is new Bcrypt hash, second is user ID
                return str_starts_with($params[0], '$2y$10$') && $params[1] === 42;
            }))
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with("UPDATE admin SET password = ? WHERE id_admin = ?")
            ->willReturn($stmtMock);

        $verified = PasswordService::verify($plain, $sha256Hash, 42, $pdoMock);
        $this->assertTrue($verified);
    }

    public function testVerifyLegacyMd5AndUpgrades(): void
    {
        $plain = 'old_md5_secret';
        $md5Hash = md5($plain);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params) {
                return str_starts_with($params[0], '$2y$10$') && $params[1] === 99;
            }))
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with("UPDATE admin SET password = ? WHERE id_admin = ?")
            ->willReturn($stmtMock);

        $verified = PasswordService::verify($plain, $md5Hash, 99, $pdoMock);
        $this->assertTrue($verified);
    }

    public function testDummyVerifyExecutesSafely(): void
    {
        // Must complete without exceptions
        PasswordService::dummyVerify();
        $this->assertTrue(true);
    }
}
