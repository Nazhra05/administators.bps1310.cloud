<?php
/**
 * Test Database Reset Script
 * Digunakan untuk mengosongkan dan mengimpor ulang schema & dev_seed pada database pengujian (*_test).
 * Keamanan: Skrip ini WAJIB menolak berjalan jika target DB_NAME bukan berakhiran '_test'.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

// Ambil parameter atau env
$options = getopt('', ['db:']);
$targetDb = $options['db'] ?? env('DB_NAME_TEST', 'bps_solsel_test');

echo "=== BPS Solok Selatan: Reset Database Pengujian ===\n";
echo "Target database: {$targetDb}\n";

// Validasi ketat nama database
if (!str_ends_with($targetDb, '_test')) {
    fwrite(STDERR, "FATAL ERROR: Operasi dibatalkan!\n");
    fwrite(STDERR, "Skrip ini hanya boleh dijalankan pada database pengujian yang berakhiran '_test' (contoh: bps_solsel_test).\n");
    fwrite(STDERR, "Database target saat ini: '{$targetDb}'.\n");
    exit(1);
}

$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');
$user = env('DB_MIGRATOR_USER', 'bps_migrator');
$pass = env('DB_MIGRATOR_PASS', 'change_me_migrator_dev');

$schemaFile = dirname(__DIR__) . '/database/schema.sql';
$seedFile   = dirname(__DIR__) . '/database/seeds/dev_seed.sql';

if (!file_exists($schemaFile)) {
    fwrite(STDERR, "Berkas schema tidak ditemukan: {$schemaFile}\n");
    exit(1);
}
if (!file_exists($seedFile)) {
    fwrite(STDERR, "Berkas seed tidak ditemukan: {$seedFile}\n");
    exit(1);
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$targetDb};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    echo "1. Mengosongkan tabel lama...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `{$table}`;");
        echo "   - Tabel `{$table}` dihapus.\n";
    }

    echo "2. Mengimpor skema (database/schema.sql)...\n";
    $schemaSql = file_get_contents($schemaFile);
    $pdo->exec($schemaSql);
    echo "   - Skema berhasil diimpor.\n";

    echo "3. Mengimpor data seed (database/seeds/dev_seed.sql)...\n";
    $seedSql = file_get_contents($seedFile);
    $pdo->exec($seedSql);
    echo "   - Data seed berhasil diimpor.\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Hitung baris verifikasi
    $adminCount    = $pdo->query("SELECT COUNT(*) FROM `admin`")->fetchColumn();
    $kategoriCount = $pdo->query("SELECT COUNT(*) FROM `kategori`")->fetchColumn();
    $layananCount  = $pdo->query("SELECT COUNT(*) FROM `layanan`")->fetchColumn();

    echo "========================================================\n";
    echo "Reset database pengujian '{$targetDb}' SUKSES!\n";
    echo "Total data:\n";
    echo "- Admin    : {$adminCount} baris\n";
    echo "- Kategori : {$kategoriCount} baris\n";
    echo "- Layanan  : {$layananCount} baris\n";
    echo "========================================================\n";

} catch (PDOException $e) {
    fwrite(STDERR, "PDO Error: " . $e->getMessage() . "\n");
    exit(1);
}
