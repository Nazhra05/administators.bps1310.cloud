<?php
/**
 * Simple Database Migration Runner
 * Usage:
 *   php scripts/migrate.php --status [--db=dbname]
 *   php scripts/migrate.php --up [--db=dbname]
 *   php scripts/migrate.php --down=1 [--db=dbname]
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

$options = getopt('', ['status', 'up', 'down::', 'db::', 'help']);

if (isset($options['help']) || (empty($options) && !isset($argv[1]))) {
    echo "=========================================================\n";
    echo " BPS Solok Selatan - Database Migration Runner\n";
    echo "=========================================================\n";
    echo "Perintah yang tersedia:\n";
    echo "  php scripts/migrate.php --status    Lihat status semua migrasi\n";
    echo "  php scripts/migrate.php --up        Jalankan semua migrasi pending\n";
    echo "  php scripts/migrate.php --down=1    Rollback N migrasi terakhir\n";
    echo "  --db=dbname                         Spesifikasikan target database\n";
    echo "=========================================================\n";
    exit(0);
}

$targetDb = $options['db'] ?? env('DB_NAME', 'bps_solsel_dev');
$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');

// Try migrator user first, fallback to app user
$migratorUser = env('DB_MIGRATOR_USER', 'bps_migrator');
$migratorPass = env('DB_MIGRATOR_PASS', 'change_me_migrator_dev');
$appUser = env('DB_USER', 'bps_app');
$appPass = env('DB_PASS', '');

$pdo = null;
try {
    $dsn = "mysql:host={$host};port={$port};dbname={$targetDb};charset=utf8mb4";
    $pdo = new PDO($dsn, $migratorUser, $migratorPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
} catch (\Throwable $e) {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$targetDb};charset=utf8mb4";
        $pdo = new PDO($dsn, $appUser, $appPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ]);
    } catch (\Throwable $e2) {
        fwrite(STDERR, "Gagal terhubung ke database target '{$targetDb}': " . $e2->getMessage() . "\n");
        exit(1);
    }
}

// Ensure schema_migrations table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `version` VARCHAR(100) NOT NULL PRIMARY KEY,
        `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Scan migration files
$migrationDir = dirname(__DIR__) . '/database/migrations';
if (!is_dir($migrationDir)) {
    mkdir($migrationDir, 0755, true);
}

$files = glob($migrationDir . '/*.sql') ?: [];
$availableMigrations = [];
foreach ($files as $file) {
    $basename = basename($file);
    if (str_ends_with($basename, '.down.sql')) {
        continue;
    }
    if (preg_match('/^(\d{3}_[a-zA-Z0-9_-]+)\.sql$/', $basename, $matches)) {
        $availableMigrations[$matches[1]] = [
            'version'   => $matches[1],
            'up_file'   => $file,
            'down_file' => $migrationDir . '/' . $matches[1] . '.down.sql',
        ];
    }
}
ksort($availableMigrations);

// Fetch applied migrations
$appliedStmt = $pdo->query("SELECT version, applied_at FROM schema_migrations ORDER BY version ASC");
$appliedRows = $appliedStmt->fetchAll();
$appliedMap = [];
foreach ($appliedRows as $row) {
    $appliedMap[$row['version']] = $row['applied_at'];
}

// -------------------------------------------------------------
// 1. STATUS
// -------------------------------------------------------------
if (isset($options['status'])) {
    echo "=========================================================\n";
    echo " Status Migrasi Database: [{$targetDb}]\n";
    echo "=========================================================\n";
    printf("%-30s | %-20s | %-10s\n", "Migrasi", "Diterapkan Pada", "Status");
    echo str_repeat("-", 68) . "\n";

    if (empty($availableMigrations)) {
        echo "Belum ada berkas migrasi di folder database/migrations/.\n";
    }

    foreach ($availableMigrations as $version => $info) {
        $isApplied = isset($appliedMap[$version]);
        $appliedAt = $isApplied ? $appliedMap[$version] : '-';
        $statusStr = $isApplied ? "APPLIED" : "PENDING";
        printf("%-30s | %-20s | %-10s\n", $version, $appliedAt, $statusStr);
    }
    echo "=========================================================\n";
    exit(0);
}

// -------------------------------------------------------------
// 2. UP (Apply pending migrations)
// -------------------------------------------------------------
if (isset($options['up'])) {
    echo "Menjalankan migrasi pada database: [{$targetDb}]...\n";
    $count = 0;

    foreach ($availableMigrations as $version => $info) {
        if (isset($appliedMap[$version])) {
            continue; // Already applied
        }

        echo "-> Menerapkan migrasi: {$version}... ";
        $sql = file_get_contents($info['up_file']);
        if ($sql === false || trim($sql) === '') {
            echo "KOSONG (Dilewati)\n";
            continue;
        }

        try {
            $pdo->exec($sql);
            // Clear any lingering result sets from multi-queries
            while ($pdo->query("SELECT 1")->fetch()) {
                break;
            }
            $ins = $pdo->prepare("INSERT INTO schema_migrations (version, applied_at) VALUES (?, NOW())");
            $ins->execute([$version]);
            echo "OK\n";
            $count++;
        } catch (\Throwable $e) {
            echo "GAGAL!\n";
            fwrite(STDERR, "Error saat menjalankan {$version}: " . $e->getMessage() . "\n");
            exit(1);
        }
    }

    if ($count === 0) {
        echo "Database sudah mutakhir. Tidak ada migrasi pending (Idempotent).\n";
    } else {
        echo "Selesai! {$count} migrasi berhasil diterapkan.\n";
    }
    exit(0);
}

// -------------------------------------------------------------
// 3. DOWN (Rollback N migrations)
// -------------------------------------------------------------
if (isset($options['down'])) {
    $steps = (int)($options['down'] ?: 1);
    if ($steps <= 0) {
        $steps = 1;
    }

    echo "Menjalankan rollback ({$steps} langkah) pada database: [{$targetDb}]...\n";
    $rollbackStmt = $pdo->query("SELECT version FROM schema_migrations ORDER BY version DESC LIMIT {$steps}");
    $toRollback = $rollbackStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($toRollback)) {
        echo "Tidak ada migrasi yang dapat di-rollback.\n";
        exit(0);
    }

    foreach ($toRollback as $version) {
        echo "-> Rollback: {$version}... ";
        $downFile = $availableMigrations[$version]['down_file'] ?? ($migrationDir . '/' . $version . '.down.sql');

        if (!file_exists($downFile)) {
            echo "GAGAL!\n";
            fwrite(STDERR, "Berkas rollback tidak ditemukan: {$downFile}\n");
            exit(1);
        }

        $sql = file_get_contents($downFile);
        if ($sql === false || trim($sql) === '') {
            echo "KOSONG (Dilewati)\n";
            continue;
        }

        try {
            $pdo->exec($sql);
            $del = $pdo->prepare("DELETE FROM schema_migrations WHERE version = ?");
            $del->execute([$version]);
            echo "OK\n";
        } catch (\Throwable $e) {
            echo "GAGAL!\n";
            fwrite(STDERR, "Error saat rollback {$version}: " . $e->getMessage() . "\n");
            exit(1);
        }
    }

    echo "Selesai rollback.\n";
    exit(0);
}
