-- =========================================================
-- Database Schema for Dashboard Admin BPS Solok Selatan
-- Database: bps_solsel
-- Canonical Consolidated Schema (Phase 3 Audit v1.1)
-- =========================================================

CREATE DATABASE IF NOT EXISTS `bps_solsel` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `bps_solsel`;

-- --------------------------------------------------------
-- Table structure for table `admin`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admin` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'approved',
  `role` enum('ADMIN','USER') NOT NULL DEFAULT 'USER',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `kategori`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `kategori` (
  `id_kategori` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  PRIMARY KEY (`id_kategori`),
  UNIQUE KEY `idx_nama_kategori` (`nama_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `layanan`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `layanan` (
  `id_layanan` int(11) NOT NULL AUTO_INCREMENT,
  `id_kategori` int(11) NOT NULL,
  `created_by` int(11) DEFAULT 1,
  `akses` enum('Internal','Public','Private') NOT NULL DEFAULT 'Public',
  `vpn` enum('Yes','No') NOT NULL DEFAULT 'No',
  `nama_layanan` varchar(200) NOT NULL,
  `deskripsi_layanan` text DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `keyword` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_layanan`),
  KEY `fk_layanan_kategori` (`id_kategori`),
  KEY `idx_layanan_created_by` (`created_by`),
  KEY `idx_layanan_kategori_akses` (`id_kategori`, `akses`),
  CONSTRAINT `fk_layanan_kategori` FOREIGN KEY (`id_kategori`) 
    REFERENCES `kategori` (`id_kategori`) 
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_layanan_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `admin` (`id_admin`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `user_favorites`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id_favorite` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_layanan` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_favorite`),
  UNIQUE KEY `idx_user_layanan` (`id_user`, `id_layanan`),
  KEY `fk_fav_user` (`id_user`),
  KEY `fk_fav_layanan` (`id_layanan`),
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fav_layanan` FOREIGN KEY (`id_layanan`) REFERENCES `layanan` (`id_layanan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `user_links`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_links` (
  `id_link` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `nama_website` varchar(200) NOT NULL,
  `url_website` varchar(500) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_link`),
  KEY `idx_link_user` (`id_user`),
  CONSTRAINT `fk_link_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `user_data_links`
-- --------------------------------------------------------

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
  KEY `idx_data_link_user` (`id_user`),
  CONSTRAINT `fk_data_link_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `user_mobile_apps`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_mobile_apps` (
  `id_app` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `nama_aplikasi` varchar(200) NOT NULL,
  `url_aplikasi` varchar(500) NOT NULL,
  `platform` varchar(50) NOT NULL DEFAULT 'Android',
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_app`),
  KEY `idx_app_user` (`id_user`),
  CONSTRAINT `fk_app_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `user_drive_links`
-- --------------------------------------------------------

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
  KEY `idx_drive_user` (`id_user`),
  CONSTRAINT `fk_drive_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `user_photo_links`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_photo_links` (
  `id_photo` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `judul_foto` varchar(200) NOT NULL,
  `url_foto` varchar(500) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_photo`),
  KEY `idx_photo_user` (`id_user`),
  CONSTRAINT `fk_photo_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `user_other_links`
-- --------------------------------------------------------

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
  KEY `idx_other_user` (`id_user`),
  CONSTRAINT `fk_other_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `audit_logs`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `waktu` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_admin` int(11) DEFAULT NULL,
  `aksi` varchar(100) NOT NULL,
  `tabel_target` varchar(100) NOT NULL,
  `id_target` int(11) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  KEY `idx_audit_waktu` (`waktu`),
  KEY `idx_audit_admin` (`id_admin`),
  KEY `idx_audit_aksi` (`aksi`),
  KEY `idx_audit_target` (`tabel_target`, `id_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `login_attempts`
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Seed Data: Default Categories
-- --------------------------------------------------------

INSERT IGNORE INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(1, 'Distribusi', 'Perdagangan, harga, transportasi, pariwisata, dan distribusi barang/jasa.'),
(2, 'Produksi', 'Pertanian, industri, pertambangan, energi, konstruksi, peternakan, kehutanan, perikanan.'),
(3, 'Neraca', 'PDRB, neraca wilayah, neraca produksi/pengeluaran, dan analisis ekonomi makro.'),
(4, 'Sosial', 'Penduduk, ketenagakerjaan, kemiskinan, pendidikan, kesehatan, sosial, dan kesejahteraan.'),
(5, 'TI', 'Sistem dan infrastruktur/alat BPS — bukan data statistik bidang tertentu, melainkan sistem nasional.'),
(6, 'Umum', 'Administrasi, kepegawaian, pengadaan, regulasi, dan urusan internal.'),
(7, 'Diseminasi', 'Kategori dengan isi paling banyak — tugasnya menyebarkan data ke pengguna.');

-- --------------------------------------------------------
-- Seed Data: Default Administrator
-- Default credentials: username 'admin', password 'admin123' (Bcrypt)
-- IMPORTANT: Change the password immediately after initial setup!
-- --------------------------------------------------------

INSERT IGNORE INTO `admin` (
  `id_admin`,
  `first_name`,
  `last_name`,
  `username`,
  `email`,
  `password`,
  `status`,
  `role`,
  `email_verified`,
  `created_at`
) VALUES (
  1,
  'Admin',
  'Utama',
  'admin',
  'admin@bps1310.cloud',
  '$2y$10$npJzA8bGxqVdbteHxoUqReSQXImNXS.l2znCFsoFXvqGGUFltnSHm',
  'approved',
  'ADMIN',
  1,
  NOW()
);
