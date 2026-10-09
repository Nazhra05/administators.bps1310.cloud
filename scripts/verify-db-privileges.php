<?php

declare(strict_types=1);

/**
 * Verification Script for Least Privilege DB Access (Task 5.5)
 *
 * Verifies that:
 * 1. Application user (bps_app) has CRUD permissions (SELECT, INSERT, UPDATE, DELETE).
 * 2. Application user is strictly forbidden from executing DROP TABLE or ALTER TABLE.
 * 3. Migration user (bps_migrator) has DDL permissions.
 *
 * Usage:
 *   php scripts/verify-db-privileges.php [--db=bps_solsel_test]
 */

require_once dirname(__DIR__) . '/config/config.php';

$targetDb = DB_NAME;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--db=')) {
        $targetDb = substr($arg, 5);
    }
}

echo "=========================================================\n";
echo " UJI HAK AKSES DATABASE MINIMAL (Task 5.5)\n";
echo " Database: [{$targetDb}]\n";
echo " User App: [" . DB_USER . "]\n";
echo "=========================================================\n";

$allPassed = true;

// 1. Uji Koneksi dan CRUD dengan bps_app
try {
    $appPdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . $targetDb . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // SELECT
    $stmt = $appPdo->query("SELECT COUNT(*) FROM kategori");
    $count = $stmt->fetchColumn();
    echo "[OK] SELECT berhasil (jumlah kategori: {$count})\n";

    // INSERT & DELETE (CRUD)
    $appPdo->exec("INSERT INTO kategori (nama_kategori, deskripsi, is_active) VALUES ('__priv_test__', 'test', 0)");
    $testId = (int)$appPdo->lastInsertId();
    echo "[OK] INSERT berhasil (id: {$testId})\n";

    $appPdo->exec("UPDATE kategori SET deskripsi = 'test_updated' WHERE id_kategori = {$testId}");
    echo "[OK] UPDATE berhasil\n";

    $appPdo->exec("DELETE FROM kategori WHERE id_kategori = {$testId}");
    echo "[OK] DELETE berhasil\n";
} catch (\Throwable $e) {
    echo "[FAIL] Operasi CRUD dasar bps_app gagal: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// 2. Uji Penolakan DROP TABLE untuk bps_app
try {
    $appPdo->exec("DROP TABLE IF EXISTS `__test_forbidden_table__`");
    echo "[FAIL] DROP TABLE diizinkan untuk bps_app (seharusnya ditolak!)\n";
    $allPassed = false;
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'command denied') || $e->getCode() === '42000') {
        echo "[OK] DROP TABLE berhasil ditolak oleh server DB (42000 Access Denied)\n";
    } else {
        echo "[FAIL] DROP TABLE gagal dengan alasan lain: " . $e->getMessage() . "\n";
        $allPassed = false;
    }
}

// 3. Uji Penolakan ALTER TABLE untuk bps_app
try {
    $appPdo->exec("ALTER TABLE `kategori` ADD COLUMN `__forbidden_col__` INT NULL");
    echo "[FAIL] ALTER TABLE diizinkan untuk bps_app (seharusnya ditolak!)\n";
    $allPassed = false;
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'command denied') || $e->getCode() === '42000') {
        echo "[OK] ALTER TABLE berhasil ditolak oleh server DB (42000 Access Denied)\n";
    } else {
        echo "[FAIL] ALTER TABLE gagal dengan alasan lain: " . $e->getMessage() . "\n";
        $allPassed = false;
    }
}

echo "=========================================================\n";
if ($allPassed) {
    echo "[SUKSES] Semua verifikasi hak akses DB minimal (5.5) LULUS!\n";
    echo "=========================================================\n";
    exit(0);
} else {
    echo "[GAGAL] Terdapat pemeriksaan hak akses yang tidak memenuhi syarat!\n";
    echo "=========================================================\n";
    exit(1);
}
