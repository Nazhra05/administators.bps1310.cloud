<?php
declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database connection manager using Lazy PDO Singleton pattern.
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Get or create PDO connection instance.
     */
    public static function pdo(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = (int)($_ENV['DB_PORT'] ?? 3306);
        $dbname = $_ENV['DB_NAME'] ?? 'bps_solsel_dev';
        $user = $_ENV['DB_USER'] ?? 'bps_app';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            self::$instance = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new RuntimeException("Koneksi database gagal: " . $e->getMessage(), (int)$e->getCode(), $e);
        }

        return self::$instance;
    }

    /**
     * Alias for pdo() for backward compatibility.
     */
    public static function getConnection(): PDO
    {
        return self::pdo();
    }

    /**
     * Override connection instance (useful for testing or switching database).
     */
    public static function setPdo(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
