-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 08, 2026 at 04:01 AM
-- Server version: 8.4.10-10
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bps-solsel`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'approved',
  `role` enum('ADMIN','USER') NOT NULL DEFAULT 'USER',
  `email_verified` tinyint(1) NOT NULL DEFAULT '1',
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `first_name`, `last_name`, `username`, `email`, `password`, `status`, `role`, `email_verified`, `verification_token`, `verification_expires`, `created_at`) VALUES
(1, NULL, NULL, 'admin', NULL, '$2y$10$npJzA8bGxqVdbteHxoUqReSQXImNXS.l2znCFsoFXvqGGUFltnSHm', 'approved', 'ADMIN', 1, NULL, NULL, '2026-09-28 01:41:38'),
(4, 'Hamdan', 'Keren', 'Haker', 'nazhrahamdani05@gmail.com', 'ce9a37dc4ae392c2882da0bcae979ca16febd268d6dd6195d8d1b4ffa98ff1ac', 'approved', 'ADMIN', 1, NULL, NULL, '2026-09-28 03:45:47'),
(13, 'Andre', 'Fanni', 'andrefanni', 'andrewaew@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'approved', 'USER', 1, NULL, NULL, '2026-10-02 08:01:58'),
(16, NULL, NULL, 'Luce', 'hamdanwindy24@gmail.com', '7045076dc248b6aef8c396d15dcdc2049f3df28d0add75fb2d23189920974fb5', 'approved', 'USER', 1, NULL, NULL, '2026-10-03 07:26:25'),
(19, 'Papa', 'Info', 'Papainfo', 'papainfo31@gmail.com', '1665430605ff70bff3ea93b8590e6cc27199928ca0b0618d44226e8144153feb', 'approved', 'USER', 1, NULL, NULL, '2026-10-07 08:02:51'),
(20, NULL, NULL, NULL, 'andrefanni@bps.go.id', NULL, 'pending', 'USER', 0, 'b8c12812c33c5a341a08a88e173652925c160ae303eb94a594ea6387d90c5d44', '2026-10-09 01:31:02', '2026-10-08 01:31:02'),
(21, NULL, NULL, NULL, 'nadiva.ns20@gmail.com', NULL, 'pending', 'USER', 1, NULL, NULL, '2026-10-08 01:33:12');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(1, 'Distribusi', 'Layanan yang berkaitan dengan pelaporan perusahaan dan kegiatan sensus ekonomi.'),
(2, 'Produksi', 'Layanan yang berkaitan dengan pendataan dan statistik sektor pertanian serta produksi.'),
(3, 'Neraca', 'Layanan yang berkaitan dengan data PDRB, neraca wilayah, dan kompilasi data ekonomi daerah.'),
(4, 'Sosial', 'Layanan yang berkaitan dengan data kependudukan, pembangunan manusia, dan kondisi sosial masyarakat.'),
(5, 'TI', 'Sistem dan layanan teknologi informasi untuk pengelolaan, integrasi, pemetaan, serta penyediaan data statistik.'),
(6, 'Umum', 'Layanan pendukung administrasi, kepegawaian, kemitraan, dan keterbukaan informasi BPS.'),
(7, 'Diseminasi', 'Layanan penyebaran, akses, dan pelayanan data statistik kepada pengguna.');

-- --------------------------------------------------------

--
-- Table structure for table `layanan`
--

CREATE TABLE `layanan` (
  `id_layanan` int NOT NULL,
  `id_kategori` int NOT NULL,
  `created_by` int DEFAULT 1,
  `akses` enum('Internal','Public','Private') NOT NULL DEFAULT 'Public',
  `vpn` enum('Yes','No') NOT NULL DEFAULT 'No',
  `nama_layanan` varchar(200) NOT NULL,
  `deskripsi_layanan` text,
  `url` varchar(500) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `keyword` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `layanan`
--

INSERT INTO `layanan` (`id_layanan`, `id_kategori`, `akses`, `vpn`, `nama_layanan`, `deskripsi_layanan`, `url`, `logo`, `keyword`) VALUES
(1, 1, 'Public', 'Yes', 'Portal Pelaporan Perusahaan (SIPP)', 'Layanan pengisian kuesioner mandiri (CAWI) untuk survei ekonomi dan survei perusahaan rutin BPS di Solok Selatan.', 'https://sensus.bps.go.id', 'sipp_logo.png', NULL),
(2, 1, 'Public', 'No', 'Sensus Ekonomi 2026 (SE2026)', 'Portal informasi, pemetaan wilayah kerja, dan instrumen pendataan untuk persiapan Sensus Ekonomi 2026.', 'https://se2026.bps.go.id', 'se2026_logo.png', NULL),
(3, 2, 'Internal', 'No', 'FASIH (Pendataan Lapangan & CAPI/CAWI)', 'Sistem pengumpulan data survei dan sensus lapangan terintegrasi berbasis Android dan Web untuk pencacahan sektor pertanian, industri, dan hortikultura.', 'https://fasih.bps.go.id', 'fasih_logo.png', NULL),
(4, 2, 'Public', 'No', 'KSA - Kerangka Sampel Area', 'Aplikasi pemantauan fase tumbuh padi dan estimasi produksi pertanian berbasis citra satelit dan geospasial di Solok Selatan.', 'https://ksa.bps.go.id', 'ksa_logo.png', NULL),
(5, 3, 'Internal', 'No', 'SIMDASI', 'Sistem Manajemen Data Statistik Terintegrasi untuk kompilasi data sektoral dan penyusunan publikasi PDRB serta Kabupaten Solok Selatan Dalam Angka.', 'https://simdasi.bps.go.id', 'simdasi_logo.png', NULL),
(6, 3, 'Public', 'Yes', 'Data PDRB Solok Selatan', 'Akses langsung ke tabel dinamis Produk Domestik Regional Bruto (PDRB) Kabupaten Solok Selatan menurut lapangan usaha.', 'https://solokselatankab.bps.go.id/subject/52/produk-domestik-regional-bruto--lapangan-usaha-.html', 'pdrb_logo.png', NULL),
(7, 4, 'Public', 'No', 'Data IPM & Kemiskinan', 'Direktori tabel dinamis Indeks Pembangunan Manusia (IPM) dan indikator kemiskinan makro Kabupaten Solok Selatan.', 'https://solokselatankab.bps.go.id/subject/26/indeks-pembangunan-manusia.html', 'ipm_logo.png', NULL),
(8, 4, 'Public', 'No', 'E-Sensus Kependudukan', 'Portal partisipasi mandiri masyarakat untuk pemutakhiran data kependudukan dalam rangkaian Sensus Penduduk.', 'https://sensus.bps.go.id', 'sensus_logo.png', NULL),
(9, 5, 'Public', 'No', 'ROMANTIK Online', 'Portal pengajuan dan evaluasi Rekomendasi Kegiatan Statistik Sektoral untuk Dinas/OPD Pemerintah Daerah Solok Selatan.', 'https://romantik.bps.go.id', 'romantik_logo.png', NULL),
(10, 5, 'Internal', 'No', 'Wilkerstat', 'Sistem informasi pemetaan wilayah kerja statistik dan batas blok sensus secara geospasial untuk wilayah Solok Selatan.', 'https://wilkerstat.bps.go.id', 'wilkerstat_logo.png', NULL),
(11, 5, 'Public', 'Yes', 'WebAPI BPS', 'Layanan antarmuka (API) untuk penarikan data statistik BPS secara otomatis untuk diintegrasikan ke dashboard Pemda.', 'https://webapi.bps.go.id', 'webapi_logo.png', NULL),
(12, 5, 'Public', 'No', 'SIRUSA BPS', 'Sistem Rujukan Statistik Nasional. Kamus metadata berisi konsep, definisi, dan metodologi seluruh indikator BPS.', 'https://sirusa.bps.go.id', 'sirusa_logo.png', NULL),
(13, 6, 'Public', 'No', 'Backoffice Selindo (BOS)', 'Sistem administrasi internal untuk presensi harian, pengajuan cuti, dan capaian kinerja (CKP) ASN BPS Solok Selatan.', 'https://backofficeselindo.bps.go.id', 'bos_logo.png', NULL),
(14, 6, 'Internal', 'Yes', 'SOBAT BPS (Mitra)', 'Portal rekrutmen, manajemen kontrak, dan e-learning bagi mitra statistik survei lapangan di Solok Selatan.', 'https://mitra.bps.go.id', 'sobat_logo.png', NULL),
(15, 6, 'Public', 'No', 'PPID BPS', 'Pejabat Pengelola Informasi dan Dokumentasi. Portal permohonan informasi publik dan keterbukaan informasi.', 'https://ppid.bps.go.id', '1790925715_PPID.png', NULL),
(16, 7, 'Public', 'No', 'Website Resmi BPS Solok Selatan', 'Portal induk penyedia Berita Resmi Statistik (BRS), publikasi daerah, dan infografis Kabupaten Solok Selatan.', 'https://solokselatankab.bps.go.id', 'web_solsel_logo.png', NULL),
(17, 7, 'Public', 'Yes', 'PST Online', 'Layanan Pelayanan Statistik Terpadu (PST) untuk pengajuan permintaan data mikro dan konsultasi statistik virtual.', 'https://pst.bps.go.id', 'pst_logo.png', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id_layanan`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id_layanan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `layanan`
--
ALTER TABLE `layanan`
  ADD CONSTRAINT `layanan_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`);

-- --------------------------------------------------------
-- Table structure for table `user_favorites`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_favorites` (
  `id_favorite` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `id_layanan` int NOT NULL,
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
  `id_link` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
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
  `id_data_link` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `nama_dokumen` varchar(200) NOT NULL,
  `url_dokumen` varchar(500) NOT NULL,
  `jenis_dokumen` varchar(50) NOT NULL DEFAULT 'Lainnya',
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_data_link`),
  KEY `idx_data_link_user` (`id_user`),
  CONSTRAINT `fk_data_link_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
-- --------------------------------------------------------
-- Table structure for table `user_mobile_apps`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_mobile_apps` (
  `id_app` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
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
  `id_drive` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
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
  `id_photo` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
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
  `id_other` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
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

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;



