-- =========================================================
-- Database Schema for Dashboard Admin BPS Solok Selatan
-- Database: bps_solsel
-- =========================================================

CREATE DATABASE IF NOT EXISTS `bps_solsel` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `bps_solsel`;

-- --------------------------------------------------------
-- Table structure for table `admin`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admin` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) DEFAULT 'Admin',
  `last_name` varchar(50) DEFAULT 'Utama',
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
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
  `nama_layanan` varchar(200) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_layanan`),
  KEY `fk_layanan_kategori` (`id_kategori`),
  CONSTRAINT `fk_layanan_kategori` FOREIGN KEY (`id_kategori`) 
    REFERENCES `kategori` (`id_kategori`) 
    ON DELETE RESTRICT ON UPDATE CASCADE
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
-- Default credentials: username 'admin', password 'admin123'
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
  `email_verified`,
  `created_at`
) VALUES (
  1,
  'Admin',
  'Utama',
  'admin',
  'admin@bps1310.cloud',
  -- SHA-256 hash of 'admin123'
  '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9',
  'approved',
  1,
  NOW()
);
