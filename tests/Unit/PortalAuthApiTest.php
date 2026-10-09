<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\ApiController;
use App\Support\Database;
use App\Support\Logger;
use PDO;
use PHPUnit\Framework\TestCase;

class PortalAuthApiTest extends TestCase
{
    private PDO $pdo;
    private ApiController $controller;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQLite schema for admin, sessions, login_attempts
        $this->pdo->exec("
            CREATE TABLE admin (
                id_admin INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT,
                last_name TEXT,
                username TEXT NOT NULL,
                email TEXT NOT NULL,
                password TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'approved',
                role TEXT NOT NULL DEFAULT 'user',
                unit_id INTEGER NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                must_change_password INTEGER NOT NULL DEFAULT 0,
                failed_attempts INTEGER NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                last_login_at DATETIME NULL
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
                identity TEXT NOT NULL,
                attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                success INTEGER DEFAULT 0
            )
        ");

        // Seed test users
        // 1. Approved active user
        $passwordHash = password_hash('SecretPassword123', PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt = $this->pdo->prepare("
            INSERT INTO admin (id_admin, first_name, last_name, username, email, password, status, role, unit_id, is_active)
            VALUES (1, 'Budi', 'Santoso', 'budi_portal', 'budi@bps.go.id', ?, 'approved', 'user', 2, 1)
        ");
        $stmt->execute([$passwordHash]);

        // 2. Inactive user
        $stmt = $this->pdo->prepare("
            INSERT INTO admin (id_admin, first_name, last_name, username, email, password, status, role, unit_id, is_active)
            VALUES (2, 'Non', 'Aktif', 'inactive_user', 'inactive@bps.go.id', ?, 'approved', 'user', 2, 0)
        ");
        $stmt->execute([$passwordHash]);

        // 3. Pending approval user
        $stmt = $this->pdo->prepare("
            INSERT INTO admin (id_admin, first_name, last_name, username, email, password, status, role, unit_id, is_active)
            VALUES (3, 'Calon', 'User', 'pending_user', 'pending@bps.go.id', ?, 'pending', 'user', 1, 1)
        ");
        $stmt->execute([$passwordHash]);

        // 4. Locked user
        $futureLock = date('Y-m-d H:i:s', time() + 600);
        $stmt = $this->pdo->prepare("
            INSERT INTO admin (id_admin, first_name, last_name, username, email, password, status, role, unit_id, is_active, failed_attempts, locked_until)
            VALUES (4, 'Locked', 'User', 'locked_user', 'locked@bps.go.id', ?, 'approved', 'user', 1, 1, 5, ?)
        ");
        $stmt->execute([$passwordHash, $futureLock]);

        Database::setPdo($this->pdo);
        $this->controller = new ApiController();

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Agent';
        unset($_SERVER['HTTP_X_SESSION'], $_SERVER['HTTP_AUTHORIZATION']);
    }

    protected function tearDown(): void
    {
        Database::setPdo(null);
        unset($_SERVER['HTTP_X_SESSION'], $_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testLoginRequiresUsernameAndPassword(): void
    {
        $resJson = $this->controller->login([], ['username' => '', 'password' => '']);
        $res = json_decode($resJson, true);

        $this->assertNull($res['data']);
        $this->assertSame('validation_failed', $res['error']['code']);
        $this->assertSame(422, http_response_code());
    }

    public function testLoginRejectsUnknownUser(): void
    {
        $resJson = $this->controller->login([], ['username' => 'unknown_user', 'password' => 'somePassword123']);
        $res = json_decode($resJson, true);

        $this->assertNull($res['data']);
        $this->assertSame('unauthorized', $res['error']['code']);
        $this->assertSame(401, http_response_code());
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $resJson = $this->controller->login([], ['username' => 'budi_portal', 'password' => 'WrongPassword!']);
        $res = json_decode($resJson, true);

        $this->assertNull($res['data']);
        $this->assertSame('unauthorized', $res['error']['code']);
        $this->assertSame(401, http_response_code());
    }

    public function testLoginRejectsLockedAccount(): void
    {
        $resJson = $this->controller->login([], ['username' => 'locked_user', 'password' => 'SecretPassword123']);
        $res = json_decode($resJson, true);

        $this->assertNull($res['data']);
        $this->assertSame('unauthorized', $res['error']['code']);
        $this->assertStringContainsString('terkunci', $res['error']['message']);
        $this->assertSame(401, http_response_code());
    }

    public function testLoginRejectsInactiveAccount(): void
    {
        $resJson = $this->controller->login([], ['username' => 'inactive_user', 'password' => 'SecretPassword123']);
        $res = json_decode($resJson, true);

        $this->assertNull($res['data']);
        $this->assertSame('forbidden', $res['error']['code']);
        $this->assertSame(403, http_response_code());
    }

    public function testLoginRejectsPendingAccount(): void
    {
        $resJson = $this->controller->login([], ['username' => 'pending_user', 'password' => 'SecretPassword123']);
        $res = json_decode($resJson, true);

        $this->assertNull($res['data']);
        $this->assertSame('forbidden', $res['error']['code']);
        $this->assertSame(403, http_response_code());
    }

    public function testLoginSuccessCreatesEightHourSession(): void
    {
        $resJson = $this->controller->login([], ['username' => 'budi_portal', 'password' => 'SecretPassword123']);
        $res = json_decode($resJson, true);

        $this->assertNull($res['error']);
        $this->assertNotNull($res['data']);
        $this->assertSame(200, http_response_code());

        $token = $res['data']['token'];
        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);

        // Verify expires_at is roughly 8 hours in future (28800 seconds ± 15s)
        $expiresAt = strtotime($res['data']['expires_at']);
        $expectedExpires = time() + (8 * 3600);
        $this->assertEqualsWithDelta($expectedExpires, $expiresAt, 15);

        // Verify user profile payload
        $user = $res['data']['user'];
        $this->assertSame(1, $user['id']);
        $this->assertSame('budi_portal', $user['username']);
        $this->assertSame('Budi Santoso', $user['display_name']);
        $this->assertSame('budi@bps.go.id', $user['email']);
        $this->assertSame('user', $user['role']);
        $this->assertSame(2, $user['unit_id']);

        // Verify row exists in sessions table
        $stmt = $this->pdo->prepare("SELECT * FROM sessions WHERE token = ?");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($row);
        $this->assertSame(1, (int)$row['user_id']);
    }

    public function testLoginOpportunisticallyCleansExpiredSessions(): void
    {
        // Insert an expired session
        $oldToken = bin2hex(random_bytes(32));
        $pastDate = date('Y-m-d H:i:s', time() - 3600);
        $stmt = $this->pdo->prepare("
            INSERT INTO sessions (token, user_id, expires_at)
            VALUES (?, 1, ?)
        ");
        $stmt->execute([$oldToken, $pastDate]);

        // Login as valid user
        $this->controller->login([], ['username' => 'budi_portal', 'password' => 'SecretPassword123']);

        // Check that old expired session was removed
        $check = $this->pdo->prepare("SELECT COUNT(*) FROM sessions WHERE token = ?");
        $check->execute([$oldToken]);
        $this->assertSame(0, (int)$check->fetchColumn());
    }

    public function testSessionEndpointRequiresHeader(): void
    {
        $resJson = $this->controller->session();
        $res = json_decode($resJson, true);

        $this->assertNull($res['data']);
        $this->assertSame('unauthorized', $res['error']['code']);
        $this->assertSame(401, http_response_code());
    }

    public function testSessionEndpointRejectsInvalidToken(): void
    {
        $_SERVER['HTTP_X_SESSION'] = str_repeat('a', 64);
        $resJson = $this->controller->session();
        $res = json_decode($resJson, true);

        $this->assertNull($res['data']);
        $this->assertSame('unauthorized', $res['error']['code']);
        $this->assertSame(401, http_response_code());
    }

    public function testSessionEndpointRejectsAndPurgesExpiredToken(): void
    {
        $expiredToken = bin2hex(random_bytes(32));
        $pastDate = date('Y-m-d H:i:s', time() - 100);
        $stmt = $this->pdo->prepare("
            INSERT INTO sessions (token, user_id, expires_at)
            VALUES (?, 1, ?)
        ");
        $stmt->execute([$expiredToken, $pastDate]);

        $_SERVER['HTTP_X_SESSION'] = $expiredToken;
        $resJson = $this->controller->session();
        $res = json_decode($resJson, true);

        $this->assertNull($res['data']);
        $this->assertSame('unauthorized', $res['error']['code']);
        $this->assertSame(401, http_response_code());

        // Ensure record was deleted
        $check = $this->pdo->prepare("SELECT COUNT(*) FROM sessions WHERE token = ?");
        $check->execute([$expiredToken]);
        $this->assertSame(0, (int)$check->fetchColumn());
    }

    public function testSessionEndpointReturnsUserWithValidSession(): void
    {
        $token = bin2hex(random_bytes(32));
        $futureDate = date('Y-m-d H:i:s', time() + (8 * 3600));
        $stmt = $this->pdo->prepare("
            INSERT INTO sessions (token, user_id, expires_at)
            VALUES (?, 1, ?)
        ");
        $stmt->execute([$token, $futureDate]);

        $_SERVER['HTTP_X_SESSION'] = $token;
        $resJson = $this->controller->session();
        $res = json_decode($resJson, true);

        $this->assertNull($res['error']);
        $this->assertSame(200, http_response_code());
        $this->assertSame($token, $res['data']['token']);
        $this->assertSame('budi_portal', $res['data']['user']['username']);
        $this->assertSame('Budi Santoso', $res['data']['user']['display_name']);
        $this->assertSame(2, $res['data']['user']['unit_id']);
    }

    public function testLogoutDeletesSessionFromDatabase(): void
    {
        $token = bin2hex(random_bytes(32));
        $futureDate = date('Y-m-d H:i:s', time() + (8 * 3600));
        $stmt = $this->pdo->prepare("
            INSERT INTO sessions (token, user_id, expires_at)
            VALUES (?, 1, ?)
        ");
        $stmt->execute([$token, $futureDate]);

        $_SERVER['HTTP_X_SESSION'] = $token;

        $logoutRes = $this->controller->logout();
        $this->assertSame('', $logoutRes);
        $this->assertSame(204, http_response_code());

        // Ensure session record deleted
        $check = $this->pdo->prepare("SELECT COUNT(*) FROM sessions WHERE token = ?");
        $check->execute([$token]);
        $this->assertSame(0, (int)$check->fetchColumn());

        // Subsequent session query must fail with 401
        $sessionResJson = $this->controller->session();
        $sessionRes = json_decode($sessionResJson, true);
        $this->assertSame(401, http_response_code());
        $this->assertSame('unauthorized', $sessionRes['error']['code']);
    }

    public function testSensitiveSessionKeysAreRedactedInLogger(): void
    {
        $context = [
            'username'  => 'budi_portal',
            'token'     => bin2hex(random_bytes(32)),
            'x_session' => bin2hex(random_bytes(32)),
            'session'   => bin2hex(random_bytes(32)),
        ];

        $sanitized = Logger::sanitize($context);
        $this->assertSame('budi_portal', $sanitized['username']);
        $this->assertSame('[REDACTED]', $sanitized['token']);
        $this->assertSame('[REDACTED]', $sanitized['x_session']);
        $this->assertSame('[REDACTED]', $sanitized['session']);
    }
}
