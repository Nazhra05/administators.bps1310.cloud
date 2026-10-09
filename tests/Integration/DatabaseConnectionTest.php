<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;

class DatabaseConnectionTest extends TestCase
{
    public function testCanConnectToTestDatabase(): void
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $db   = getenv('DB_NAME') ?: 'bps_solsel_test';
        $user = getenv('DB_USER') ?: 'bps_app';
        $pass = getenv('DB_PASS') ?: 'change_me_dev';

        $this->assertStringEndsWith('_test', $db, 'Database name must end with _test for tests');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $stmt = $pdo->query('SELECT DATABASE() as current_db');
        $row = $stmt->fetch();
        $this->assertSame($db, $row['current_db']);
    }
}
