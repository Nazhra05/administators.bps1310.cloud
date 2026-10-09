<?php

declare(strict_types=1);

/**
 * Database Reset & Seed Script for Development Environments
 *
 * SAFETY RESTRICTION:
 * This script strictly refuses to execute on any database whose name
 * does not end with '_dev' or '_test'.
 *
 * Usage:
 *   php scripts/db-reset-dev.php [--db=custom_dev_db] [--force]
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/database/seeds/dev_seed.php';

$targetDb = DB_NAME;
$force = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--db=')) {
        $targetDb = substr($arg, 5);
    }
    if ($arg === '--force' || $arg === '-f') {
        $force = true;
    }
}

// 1. Strict Safety Guard
if (!str_ends_with($targetDb, '_dev') && !str_ends_with($targetDb, '_test')) {
    fwrite(STDERR, "=========================================================\n");
    fwrite(STDERR, " FATAL: OPERASI DITOLAK!\n");
    fwrite(STDERR, " Skrip db-reset-dev.php hanya diizinkan berjalan pada\n");
    fwrite(STDERR, " database development (*_dev) atau test (*_test).\n");
    fwrite(STDERR, " Database target saat ini: [{$targetDb}]\n");
    fwrite(STDERR, "=========================================================\n");
    exit(1);
}

echo "=========================================================\n";
echo " RESET DATABASE PENGEMBANGAN: [{$targetDb}]\n";
echo "=========================================================\n";

try {
    $migratorUser = env('DB_MIGRATOR_USER') ?: DB_USER;
    $migratorPass = env('DB_MIGRATOR_PASS') ?: DB_PASS;

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . $targetDb . ';charset=utf8mb4',
        $migratorUser,
        $migratorPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "1. Menghapus tabel lama secara aman...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = [
        'link_clicks',
        'user_bookmarks',
        'user_preferences',
        'login_attempts',
        'settings',
        'audit_log',
        'sessions',
        'layanan',
        'units',
        'kategori',
        'admin',
        'schema_migrations',
    ];

    foreach ($tables as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "-> Semua tabel dibersihkan: OK\n";

    echo "2. Menjalankan migrasi database...\n";
    // Execute migrate.php --up
    $migrateScript = __DIR__ . '/migrate.php';
    $output = [];
    $retCode = 0;
    exec("php " . escapeshellarg($migrateScript) . " --up --db=" . escapeshellarg($targetDb), $output, $retCode);

    if ($retCode !== 0) {
        throw new RuntimeException("Migrasi gagal:\n" . implode("\n", $output));
    }
    echo implode("\n", $output) . "\n";

    echo "3. Memasukkan data seed pengembangan...\n";
    runDevSeed($pdo, $targetDb, false);

    echo "\n[SUKSES] Database [{$targetDb}] berhasil di-reset, dimigrasi, dan di-seed!\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "\n[ERROR] Gagal melakukan reset database: " . $e->getMessage() . "\n");
    exit(1);
}
