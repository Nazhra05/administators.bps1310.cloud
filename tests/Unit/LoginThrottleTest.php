<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LoginThrottleService;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class LoginThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
    }

    public function testIsAccountLocked(): void
    {
        // Null locked_until
        $this->assertFalse(LoginThrottleService::isAccountLocked([
            'locked_until' => null,
        ]));

        // Expired locked_until (past)
        $pastDate = date('Y-m-d H:i:s', time() - 300);
        $this->assertFalse(LoginThrottleService::isAccountLocked([
            'locked_until' => $pastDate,
        ]));

        // Active locked_until (future)
        $futureDate = date('Y-m-d H:i:s', time() + 600);
        $this->assertTrue(LoginThrottleService::isAccountLocked([
            'locked_until' => $futureDate,
        ]));
    }

    public function testIsIpThrottled(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchColumn')->willReturn('25'); // Exceeds default 20

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $this->assertTrue(LoginThrottleService::isIpThrottled($pdoMock, '192.168.1.100'));
    }

    public function testIsIpNotThrottledUnderLimit(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchColumn')->willReturn('3'); // Under default 20

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $this->assertFalse(LoginThrottleService::isIpThrottled($pdoMock, '192.168.1.100'));
    }

    public function testHandleFailedLoginIncrementsFailureCount(): void
    {
        $insertStmt = $this->createMock(PDOStatement::class);
        $insertStmt->expects($this->once())
            ->method('execute')
            ->with(['10.0.0.1', 'test_user', 0]);

        $updateStmt = $this->createMock(PDOStatement::class);
        $updateStmt->expects($this->once())
            ->method('execute')
            ->with([3, 1]); // newFailed = 2 + 1 = 3, id_admin = 1

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($insertStmt, $updateStmt);

        $admin = [
            'id_admin'        => 1,
            'failed_attempts' => 2,
            'locked_until'    => null,
        ];

        LoginThrottleService::handleFailedLogin($pdoMock, $admin, '10.0.0.1', 'test_user');
    }

    public function testHandleFailedLoginLocksAccountOnFifthFailure(): void
    {
        $insertStmt = $this->createMock(PDOStatement::class);
        $updateStmt = $this->createMock(PDOStatement::class);
        $updateStmt->expects($this->once())
            ->method('execute')
            ->with([5, 10, 1]); // newFailed = 5, duration = 10 mins, id_admin = 1

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($insertStmt, $updateStmt);

        $admin = [
            'id_admin'        => 1,
            'failed_attempts' => 4,
            'locked_until'    => null,
        ];

        LoginThrottleService::handleFailedLogin($pdoMock, $admin, '10.0.0.1', 'test_user');
    }

    public function testHandleSuccessfulLoginResetsAttemptsAndLockout(): void
    {
        $insertStmt = $this->createMock(PDOStatement::class);
        $insertStmt->expects($this->once())
            ->method('execute')
            ->with(['10.0.0.1', 'test_user', 1]);

        $updateStmt = $this->createMock(PDOStatement::class);
        $updateStmt->expects($this->once())
            ->method('execute')
            ->with([1]); // id_admin = 1

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($insertStmt, $updateStmt);

        LoginThrottleService::handleSuccessfulLogin($pdoMock, 1, '10.0.0.1', 'test_user');
    }
}
