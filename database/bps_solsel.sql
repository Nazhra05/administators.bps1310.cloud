-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 29, 2026 at 06:16 AM
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
CREATE DATABASE IF NOT EXISTS `bps-solsel` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `bps-solsel`;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'approved',
  `email_verified` tinyint(1) NOT NULL DEFAULT '1',
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `first_name`, `last_name`, `username`, `email`, `password`, `status`, `email_verified`, `verification_token`, `verification_expires`, `created_at`) VALUES
(1, 'Admin', 'Utama', 'admin', 'admin@bps1310.cloud', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'approved', 1, NULL, NULL, '2026-09-28 01:41:38'),
(4, 'Hamdan', 'Keren', 'Haker', 'nazhrahamdani05@gmail.com', 'ce9a37dc4ae392c2882da0bcae979ca16febd268d6dd6195d8d1b4ffa98ff1ac', 'approved', 1, NULL, NULL, '2026-09-28 03:45:47'),
(11, 'nadiva', 'salsabilla', 'nadivasalsabilla', 'nadiva.ns20@gmail.com', 'c85018fb11ab5dcc04c04d4929a00d004a79af1bb4b318b8c79823b9ac6b1de3', 'approved', 1, NULL, NULL, '2026-09-29 01:39:22');

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
(1, 'Distribusi', 'Fokus: perdagangan, harga, transportasi, pariwisata, distribusi barang/jasa.'),
(2, 'Produksi', 'Fokus: pertanian, industri, pertambangan, energi, konstruksi, peternakan, kehutanan, perikanan.'),
(3, 'Neraca', 'Fokus: PDRB, neraca wilayah, neraca produksi/pengeluaran, analisis ekonomi makro.'),
(4, 'Sosial', 'Fokus: penduduk, ketenagakerjaan, kemiskinan, pendidikan, kesehatan, sosial, kesejahteraan.'),
(5, 'TI', 'Sistem yang menjadi infrastruktur/alat BPS.'),
(6, 'Umum', 'Untuk administrasi, kepegawaian, pengadaan, regulasi, dan urusan internal.'),
(7, 'Diseminasi', 'Penyebaran data dan pelayanan data kepada pengguna.');

-- --------------------------------------------------------

--
-- Table structure for table `layanan`
--

CREATE TABLE `layanan` (
  `id_layanan` int NOT NULL,
  `id_kategori` int NOT NULL,
  `nama_layanan` varchar(200) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `keyword` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `layanan`
--

INSERT INTO `layanan` (`id_layanan`, `id_kategori`, `nama_layanan`, `url`, `logo`, `keyword`) VALUES
(1, 1, 'Website BPS Kabupaten Solok Selatan', 'https://solokselatankab.bps.go.id/id', '1790220841_logo bps solsel.jpg', NULL),
(2, 1, 'Web Sensus / kegiatan sensus yang berkaitan dengan distribusi', '', 'logo-web-sensus.png', NULL),
(3, 1, 'Sistem/Aplikasi Statistik Distribusi', '', 'logo-statistik-distribusi.png', NULL),
(4, 1, 'Akses Data Ekspor-Impor', '', 'logo-ekspor-impor.png', NULL),
(5, 1, 'Akses Data Harga / Inflasi', '', 'logo-harga-inflasi.png', NULL),
(6, 1, 'Data Perdagangan', '', 'logo-perdagangan.png', NULL),
(7, 1, 'Data Transportasi', '', 'logo-transportasi.png', NULL),
(8, 1, 'Data Pariwisata', '', 'logo-pariwisata.png', NULL),
(9, 1, 'KBLI', '', 'logo-kbli.png', NULL),
(10, 2, 'Sistem Statistik Produksi', '', 'logo-statistik-produksi.png', NULL),
(11, 2, 'Data Pertanian', '', 'logo-pertanian.png', NULL),
(12, 2, 'Data Tanaman Pangan', '', 'logo-tanaman-pangan.png', NULL),
(13, 2, 'Data Hortikultura', '', 'logo-hortikultura.png', NULL),
(14, 2, 'Data Perkebunan', '', 'logo-perkebunan.png', NULL),
(15, 2, 'Data Peternakan', '', 'logo-peternakan.png', NULL),
(16, 2, 'Data Perikanan', '', 'logo-perikanan.png', NULL),
(17, 2, 'Data Kehutanan', '', 'logo-kehutanan.png', NULL),
(18, 2, 'Data Industri', '', 'logo-industri.png', NULL),
(19, 2, 'Data Pertambangan & Penggalian', '', 'logo-pertambangan.png', NULL),
(20, 2, 'Data Energi', '', 'logo-energi.png', NULL),
(21, 2, 'Data Konstruksi', '', 'logo-konstruksi.png', NULL),
(22, 2, 'Sensus Pertanian / ST2023 dan turunannya', '', 'logo-sensus-pertanian.png', NULL),
(23, 2, 'SIGESIT', '', 'logo-sigesit.png', NULL),
(24, 3, 'Sistem/Website Neraca Wilayah', '', 'logo-neraca-wilayah.png', NULL),
(25, 3, 'PDRB', '', 'logo-pdrb.png', NULL),
(26, 3, 'Data Pendapatan Regional', '', 'logo-pendapatan-regional.png', NULL),
(27, 3, 'Neraca Produksi', '', 'logo-neraca-produksi.png', NULL),
(28, 3, 'Neraca Pengeluaran', '', 'logo-neraca-pengeluaran.png', NULL),
(29, 3, 'Tabel/Database PDRB', '', 'logo-database-pdrb.png', NULL),
(30, 3, 'Analisis Statistik', '', 'logo-analisis-statistik.png', NULL),
(31, 3, 'Indikator ekonomi makro', '', 'logo-ekonomi-makro.png', NULL),
(32, 4, 'Sistem Statistik Sosial', '', 'logo-statistik-sosial.png', NULL),
(33, 4, 'Data Kependudukan', '', 'logo-kependudukan.png', NULL),
(34, 4, 'Data Ketenagakerjaan', '', 'logo-ketenagakerjaan.png', NULL),
(35, 4, 'Data Kemiskinan', '', 'logo-kemiskinan.png', NULL),
(36, 4, 'Data Kesejahteraan Rakyat', '', 'logo-kesejahteraan-rakyat.png', NULL),
(37, 4, 'Data Pendidikan', '', 'logo-pendidikan.png', NULL),
(38, 4, 'Data Kesehatan', '', 'logo-kesehatan.png', NULL),
(39, 4, 'Data Ketahanan Sosial', '', 'logo-ketahanan-sosial.png', NULL),
(40, 4, 'Sensus Penduduk', '', 'logo-sensus-penduduk.png', NULL),
(41, 4, 'Susenas', '', 'logo-susenas.png', NULL),
(42, 4, 'Sakernas', '', 'logo-sakernas.png', NULL),
(43, 4, 'Long Form SP2020 / hasil sensus penduduk', '', 'logo-long-form-sp2020.png', NULL),
(44, 4, 'Data sosial ekonomi masyarakat', '', 'logo-sosial-ekonomi.png', NULL),
(45, 5, 'INDAH — Indonesia Data Hub', '', 'logo-indah.png', NULL),
(46, 5, 'BPS WebAPI', '', 'logo-webapi-bps.png', NULL),
(47, 5, 'MMS — Metadata Management System', '', 'logo-mms.png', NULL),
(48, 5, 'KBLI', '', 'logo-kbli.png', NULL),
(49, 5, 'KBKI', '', 'logo-kbki.png', NULL),
(50, 5, 'Geoportal BPS', '', 'logo-geoportal-bps.png', NULL),
(51, 5, 'Sistem/API internal BPS yang digunakan untuk integrasi data', '', 'logo-api-internal-bps.png', NULL),
(52, 5, 'Sistem autentikasi/layanan internal BPS', '', 'logo-autentikasi-bps.png', NULL),
(53, 6, 'PPID BPS', '', 'logo-ppid-bps.png', NULL),
(54, 6, 'JDIH BPS', '', 'logo-jdih-bps.png', NULL),
(55, 6, 'Portal Kepegawaian', '', 'logo-kepegawaian.png', NULL),
(56, 6, 'Sistem Pengadaan / LPSE', '', 'logo-lpse.png', NULL),
(57, 6, 'Sistem keuangan', '', 'logo-keuangan.png', NULL),
(58, 6, 'Sistem kinerja', '', 'logo-kinerja.png', NULL),
(59, 6, 'Sistem persuratan', '', 'logo-persuratan.png', NULL),
(60, 6, 'Sistem arsip', '', 'logo-arsip.png', NULL),
(61, 6, 'Sistem perjalanan dinas', '', 'logo-perjalanan-dinas.png', NULL),
(62, 6, 'Sistem administrasi internal', '', 'logo-administrasi-internal.png', NULL),
(63, 6, 'Portal BPS / informasi kelembagaan', '', 'logo-portal-bps.png', NULL),
(64, 6, 'LAPOR! / kanal pengaduan', '', 'logo-lapor.png', NULL),
(65, 7, 'Website BPS Kabupaten Solok Selatan', 'https://solokselatankab.bps.go.id/id', 'logo-bps-solsel.png', NULL),
(66, 7, 'Website BPS RI', '', 'logo-bps-ri.png', NULL),
(67, 7, 'AllStats BPS', '', 'logo-allstats.png', NULL),
(68, 7, 'Tabel Statistik', '', 'logo-tabel-statistik.png', NULL),
(69, 7, 'Tabel Dinamis', '', 'logo-tabel-dinamis.png', NULL),
(70, 7, 'Publikasi', '', 'logo-publikasi.png', NULL),
(71, 7, 'Berita Resmi Statistik (BRS)', '', 'logo-brs.png', NULL),
(72, 7, 'Infografik', '', 'logo-infografik.png', NULL),
(73, 7, 'Data sektoral', '', 'logo-data-sektoral.png', NULL),
(74, 7, 'SILASTIK', '', 'logo-silastik.png', NULL),
(75, 7, 'PST / Pelayanan Statistik Terpadu', '', 'logo-pst.png', NULL),
(76, 7, 'Perpustakaan BPS / OPAC', '', 'logo-perpustakaan-bps.png', NULL),
(77, 7, 'Reservasi PST', '', 'logo-reservasi-pst.png', NULL),
(78, 7, 'Pojok Statistik', '', 'logo-pojok-statistik.png', NULL),
(79, 7, 'Konsultasi Statistik', '', 'logo-konsultasi-statistik.png', NULL),
(80, 7, 'SIRuSa', '', 'logo-sirusa.png', NULL),
(81, 7, 'ROMANTIK', '', 'logo-romantik.png', NULL),
(82, 7, 'INDAH', '', 'logo-indah.png', NULL),
(83, 7, 'Metadata Statistik', '', 'logo-metadata-statistik.png', NULL),
(84, 7, 'Standar Data Statistik Nasional', '', 'logo-standar-data-statistik.png', NULL),
(85, 7, 'Pojok Statistik Virtual', '', 'logo-pojok-statistik-virtual.png', NULL),
(86, 7, 'Forum BPS', '', 'logo-forum-bps.png', NULL),
(87, 7, 'Website/portal sensus', '', 'logo-portal-sensus.png', NULL),
(88, 7, 'Portal PPID', '', 'logo-ppid-bps.png', NULL),
(89, 7, 'Media sosial resmi BPS', '', 'logo-media-sosial-bps.png', NULL),
(90, 7, 'Bank gambar/Pikart', '', 'logo-pikart.png', NULL);

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
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id_layanan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `layanan`
--
ALTER TABLE `layanan`
  ADD CONSTRAINT `layanan_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
