<?php
/**
 * Migration Script: Phase 3 Database & Integrity Hardening
 * BPS Kabupaten Solok Selatan — Ekosistem RANCAK
 */

require_once __DIR__ . '/../config/config.php';

echo "=== MEMULAI MIGRASI BASIS DATA FASE 3 ===\n\n";

try {
    // 1. Buat Tabel login_attempts (Fondasi SEC-09)
    echo "1. Memeriksa tabel `login_attempts`... ";
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `login_attempts` (
          `id_attempt` int(11) NOT NULL AUTO_INCREMENT,
          `ip_address` varchar(45) NOT NULL,
          `identifier` varchar(100) NOT NULL,
          `attempt_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `is_success` tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id_attempt`),
          KEY `idx_ip_time` (`ip_address`, `attempt_time`),
          KEY `idx_ident_time` (`identifier`, `attempt_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK]\n";

    // 2. Optimasi Indeks audit_logs (idx_audit_target)
    echo "2. Memeriksa indeks pada `audit_logs`... ";
    $hasAuditTarget = false;
    $idxStmt = $conn->query("SHOW INDEX FROM `audit_logs`");
    while ($row = $idxStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Key_name'] === 'idx_audit_target') {
            $hasAuditTarget = true;
            break;
        }
    }
    if (!$hasAuditTarget) {
        $conn->exec("ALTER TABLE `audit_logs` ADD INDEX `idx_audit_target` (`tabel_target`, `id_target`)");
        echo "[DITAMBAHKAN idx_audit_target]\n";
    } else {
        echo "[SUDAH ADA]\n";
    }

    // 3. Tambahkan UNIQUE KEY idx_email pada tabel admin
    echo "3. Memeriksa indeks unik `idx_email` pada `admin`... ";
    $hasEmailIdx = false;
    $idxStmt = $conn->query("SHOW INDEX FROM `admin`");
    while ($row = $idxStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Key_name'] === 'idx_email' || $row['Key_name'] === 'email') {
            $hasEmailIdx = true;
            break;
        }
    }
    if (!$hasEmailIdx) {
        $conn->exec("ALTER TABLE `admin` ADD UNIQUE KEY `idx_email` (`email`)");
        echo "[DITAMBAHKAN idx_email]\n";
    } else {
        echo "[SUDAH ADA]\n";
    }

    // 4. Tambahkan UNIQUE KEY idx_nama_kategori pada tabel kategori
    echo "4. Memeriksa indeks unik `idx_nama_kategori` pada `kategori`... ";
    $hasKatIdx = false;
    $idxStmt = $conn->query("SHOW INDEX FROM `kategori`");
    while ($row = $idxStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Key_name'] === 'idx_nama_kategori') {
            $hasKatIdx = true;
            break;
        }
    }
    if (!$hasKatIdx) {
        $conn->exec("ALTER TABLE `kategori` ADD UNIQUE KEY `idx_nama_kategori` (`nama_kategori`)");
        echo "[DITAMBAHKAN idx_nama_kategori]\n";
    } else {
        echo "[SUDAH ADA]\n";
    }

    // 5. Sanitasi & Pemasangan Foreign Key fk_layanan_created_by pada layanan
    echo "5. Memeriksa constraint `fk_layanan_created_by` pada `layanan`... ";
    
    // Pastikan nilai invalid/0/empty menjadi NULL
    $conn->exec("UPDATE `layanan` SET `created_by` = NULL WHERE `created_by` IS NOT NULL AND `created_by` NOT IN (SELECT `id_admin` FROM `admin`)");
    
    $hasCreatedByFk = false;
    $fkStmt = $conn->prepare("
        SELECT CONSTRAINT_NAME 
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'layanan'
          AND CONSTRAINT_NAME = 'fk_layanan_created_by'
    ");
    $fkStmt->execute();
    if ($fkStmt->fetch()) {
        $hasCreatedByFk = true;
    }

    if (!$hasCreatedByFk) {
        $conn->exec("
            ALTER TABLE `layanan`
            ADD CONSTRAINT `fk_layanan_created_by` 
            FOREIGN KEY (`created_by`) REFERENCES `admin` (`id_admin`) 
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
        echo "[DITAMBAHKAN fk_layanan_created_by]\n";
    } else {
        echo "[SUDAH ADA]\n";
    }

    // 6. Tambahkan composite index idx_layanan_kategori_akses
    echo "6. Memeriksa composite index `idx_layanan_kategori_akses`... ";
    $hasKatAksesIdx = false;
    $idxStmt = $conn->query("SHOW INDEX FROM `layanan`");
    while ($row = $idxStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Key_name'] === 'idx_layanan_kategori_akses') {
            $hasKatAksesIdx = true;
            break;
        }
    }
    if (!$hasKatAksesIdx) {
        $conn->exec("ALTER TABLE `layanan` ADD INDEX `idx_layanan_kategori_akses` (`id_kategori`, `akses`)");
        echo "[DITAMBAHKAN idx_layanan_kategori_akses]\n";
    } else {
        echo "[SUDAH ADA]\n";
    }

    echo "\n=== MIGRASI FASE 3 SELESAI DENGAN SUKSES! ===\n";

} catch (Exception $e) {
    echo "\n[ERROR MIGRASI]: " . $e->getMessage() . "\n";
    exit(1);
}
