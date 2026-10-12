<?php
/**
 * Migration Script: Setup Table audit_logs
 * BPS Kabupaten Solok Selatan
 */

require_once __DIR__ . '/../config/config.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS audit_logs (
        id_log INT AUTO_INCREMENT PRIMARY KEY,
        waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        id_admin INT NULL,
        aksi VARCHAR(100) NOT NULL,
        tabel_target VARCHAR(100) NOT NULL,
        id_target INT NULL,
        deskripsi TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        INDEX idx_audit_waktu (waktu),
        INDEX idx_audit_admin (id_admin),
        INDEX idx_audit_aksi (aksi)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $conn->exec($sql);
    echo "SUCCESS: Table audit_logs created or already exists.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
