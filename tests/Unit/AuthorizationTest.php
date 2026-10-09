<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\RequireRole;
use App\Support\AuditLogger;
use App\Support\Session;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
        Session::destroy();
        $_SESSION = [];
        $_SERVER['REQUEST_URI'] = '/admin';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
    }

    protected function tearDown(): void
    {
        Session::destroy();
        $_SESSION = [];
    }

    public function testRequireRoleAllowsAuthorizedRole(): void
    {
        Session::start();
        Session::set('admin_role', 'ADMIN IT');

        $middleware = new RequireRole(['ADMIN IT']);
        $executed = false;

        $result = $middleware->handle([], function () use (&$executed) {
            $executed = true;
            return 'granted';
        });

        $this->assertTrue($executed);
        $this->assertSame('granted', $result);
    }

    public function testRequireRoleAllowsDefaultRoles(): void
    {
        Session::start();
        Session::set('admin_role', 'ADMIN DASHBOARD');

        $middleware = new RequireRole(); // default ['ADMIN IT', 'ADMIN DASHBOARD']
        $executed = false;

        $result = $middleware->handle([], function () use (&$executed) {
            $executed = true;
            return 'granted';
        });

        $this->assertTrue($executed);
        $this->assertSame('granted', $result);
    }

    public function testAuditLoggerInsertsIntoDatabase(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params) {
                // [$userId, $action, $entity, $entityId, $metaJson]
                return $params[0] === 5 &&
                       $params[1] === 'update' &&
                       $params[2] === 'layanan' &&
                       $params[3] === 12 &&
                       str_contains($params[4], 'published');
            }))
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        AuditLogger::log(
            action: 'update',
            entity: 'layanan',
            entityId: 12,
            meta: ['status' => 'published'],
            userId: 5,
            pdo: $pdoMock
        );
    }
}
