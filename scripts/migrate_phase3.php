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

    // 3b. Normalisasi Role admin menjadi canonical ENUM('ADMIN', 'USER')
    echo "3b. Menstandardisasi nilai role pada `admin`... ";
    $conn->exec("ALTER TABLE `admin` MODIFY COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'USER'");
    $conn->exec("UPDATE `admin` SET `role` = 'ADMIN' WHERE `role` LIKE '%ADMIN%'");
    $conn->exec("UPDATE `admin` SET `role` = 'USER' WHERE `role` NOT IN ('ADMIN') OR `role` IS NULL");
    $conn->exec("ALTER TABLE `admin` MODIFY COLUMN `role` ENUM('ADMIN','USER') NOT NULL DEFAULT 'USER'");
    echo "[ROLE DINORMALISASI JADI ADMIN & USER]\n";

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

    // 5. Memeriksa kolom created_by dan constraint fk_layanan_created_by pada layanan
    echo "5. Memeriksa kolom `created_by` pada `layanan`... ";
    $colStmt = $conn->query("SHOW COLUMNS FROM `layanan` LIKE 'created_by'");
    if (!$colStmt->fetch()) {
        $conn->exec("ALTER TABLE `layanan` ADD COLUMN `created_by` INT(11) DEFAULT 1 AFTER `id_kategori`");
        echo "[DITAMBAHKAN kolom created_by]\n";
    } else {
        echo "[SUDAH ADA]\n";
    }

    echo "   Memeriksa constraint `fk_layanan_created_by` pada `layanan`... ";
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

    // 7. Memeriksa tabel-tabel repositori pribadi
    echo "7. Memeriksa tabel repositori pribadi... ";
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `user_favorites` (
          `id_favorite` int(11) NOT NULL AUTO_INCREMENT,
          `id_user` int(11) NOT NULL,
          `id_layanan` int(11) NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_favorite`),
          UNIQUE KEY `idx_user_layanan` (`id_user`, `id_layanan`),
          KEY `fk_fav_user` (`id_user`),
          KEY `fk_fav_layanan` (`id_layanan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `user_links` (
          `id_link` int(11) NOT NULL AUTO_INCREMENT,
          `id_user` int(11) NOT NULL,
          `nama_website` varchar(200) NOT NULL,
          `url_website` varchar(500) NOT NULL,
          `deskripsi` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_link`),
          KEY `idx_link_user` (`id_user`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `user_data_links` (
          `id_data_link` int(11) NOT NULL AUTO_INCREMENT,
          `id_user` int(11) NOT NULL,
          `nama_dokumen` varchar(200) NOT NULL,
          `url_dokumen` varchar(500) NOT NULL,
          `jenis_dokumen` varchar(50) NOT NULL DEFAULT 'Lainnya',
          `deskripsi` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_data_link`),
          KEY `idx_data_link_user` (`id_user`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `user_mobile_apps`;

        CREATE TABLE IF NOT EXISTS `user_drive_links` (
          `id_drive` int(11) NOT NULL AUTO_INCREMENT,
          `id_user` int(11) NOT NULL,
          `nama_folder` varchar(200) NOT NULL,
          `url_folder` varchar(500) NOT NULL,
          `jenis_drive` varchar(50) NOT NULL DEFAULT 'Google Drive',
          `deskripsi` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_drive`),
          KEY `idx_drive_user` (`id_user`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `user_photo_links` (
          `id_photo` int(11) NOT NULL AUTO_INCREMENT,
          `id_user` int(11) NOT NULL,
          `judul_foto` varchar(200) NOT NULL,
          `url_foto` varchar(500) NOT NULL,
          `deskripsi` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_photo`),
          KEY `idx_photo_user` (`id_user`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `user_other_links` (
          `id_other` int(11) NOT NULL AUTO_INCREMENT,
          `id_user` int(11) NOT NULL,
          `nama_link` varchar(200) NOT NULL,
          `url_link` varchar(500) NOT NULL,
          `label` varchar(100) DEFAULT 'Referensi',
          `deskripsi` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_other`),
          KEY `idx_other_user` (`id_user`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK]\n";

    echo "\n=== MIGRASI FASE 3 SELESAI DENGAN SUKSES! ===\n";

} catch (Exception $e) {
    echo "\n[ERROR MIGRASI]: " . $e->getMessage() . "\n";
    exit(1);
}
