-- =========================================================
-- Dev Seed Data for Dashboard Admin BPS Solok Selatan
-- For Local Development & Testing Environments ONLY
-- Database target: bps_solsel_dev / bps_solsel_test
-- =========================================================

-- 1. Kategori BPS
INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(1, 'Distribusi', 'Layanan yang berkaitan dengan pelaporan perusahaan, harga komoditas, dan kegiatan sensus ekonomi.')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`), `deskripsi` = VALUES(`deskripsi`);

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(2, 'Produksi', 'Layanan yang berkaitan dengan pendataan dan statistik sektor pertanian, perkebunan, dan industri.')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`), `deskripsi` = VALUES(`deskripsi`);

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(3, 'Neraca', 'Layanan yang berkaitan dengan data PDRB, neraca wilayah, dan kompilasi data ekonomi daerah.')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`), `deskripsi` = VALUES(`deskripsi`);

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(4, 'Sosial', 'Layanan yang berkaitan dengan kependudukan, kemiskinan, ketenagakerjaan, dan pembangunan manusia.')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`), `deskripsi` = VALUES(`deskripsi`);

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(5, 'TI', 'Sistem teknologi informasi, integrasi aplikasi portal, pemetaan spasial, dan infrastruktur jaringan.')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`), `deskripsi` = VALUES(`deskripsi`);

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(6, 'Umum', 'Layanan pendukung administrasi kepegawaian, surat menyurat, pengadaan, dan PPID.')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`), `deskripsi` = VALUES(`deskripsi`);

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(7, 'Diseminasi', 'Layanan penyebaran publikasi, portal data terbuka, dan konsultasi statistik terpadu.')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`), `deskripsi` = VALUES(`deskripsi`);

-- 2. Akun Dev Administrator
-- Password default: 'admin123' (bcrypt hash)
INSERT INTO `admin` (
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
) VALUES
(
  1,
  'Admin',
  'Pengembang',
  'admin',
  'admin@bps1310.cloud',
  '$2y$10$WPStqy5c/MblYyyFG48cmub1BR/mhYEqNzYTP3PhJvHqUlG3N40Em',
  'approved',
  'ADMIN IT',
  1,
  NOW()
),
(
  2,
  'Operator',
  'Kategori',
  'operator',
  'operator@bps1310.cloud',
  '$2y$10$WPStqy5c/MblYyyFG48cmub1BR/mhYEqNzYTP3PhJvHqUlG3N40Em',
  'approved',
  'ADMIN DASHBOARD',
  1,
  NOW()
)
ON DUPLICATE KEY UPDATE 
  `first_name` = VALUES(`first_name`),
  `last_name` = VALUES(`last_name`),
  `password` = VALUES(`password`),
  `status` = VALUES(`status`),
  `role` = VALUES(`role`),
  `email_verified` = VALUES(`email_verified`);

-- 3. Data Layanan Contoh (Mix Public & Internal VPN)
INSERT INTO `layanan` (
  `id_layanan`,
  `id_kategori`,
  `akses`,
  `vpn`,
  `nama_layanan`,
  `deskripsi_layanan`,
  `url`,
  `logo`,
  `keyword`
) VALUES
(
  1,
  1,
  'Internal',
  'Yes',
  'Sistem Monitoring Survei Harga Konsumen (Simonev SHK)',
  'Pemantauan berkala pencacahan harga komoditas pasar tradisional di Solok Selatan.',
  'https://shk.internal.bps1310.test/',
  'logo_bps.jpg',
  'distribusi, shk, harga, inflasi'
),
(
  2,
  2,
  'Internal',
  'Yes',
  'Aplikasi Entri Data Statistik Pertanian Hortikultura (SPH)',
  'Entri dan validasi data laporan statistik pertanian tanaman pangan dan hortikultura tingkat kecamatan.',
  'https://pertanian.internal.bps1310.test/',
  'logo_bps.jpg',
  'produksi, sph, pertanian, panen'
),
(
  3,
  3,
  'Public',
  'No',
  'Kompilasi Tabel PDRB Kabupaten Solok Selatan',
  'Tabel ringkasan Produk Domestik Regional Bruto menurut lapangan usaha dan pengeluaran.',
  'https://solselkab.bps.go.id/subject/52/produk-domestik-regional-bruto--lapangan-usaha-.html',
  '1790220841_logo bps solsel.jpg',
  'neraca, pdrb, ekonomi, pendapatan regional'
),
(
  4,
  4,
  'Public',
  'No',
  'Portal Indikator Kemiskinan dan IPM Solok Selatan',
  'Informasi agregat persentase penduduk miskin, garis kemiskinan, dan indeks pembangunan manusia.',
  'https://solselkab.bps.go.id/subject/23/kemiskinan-dan-ketimpangan.html',
  '1790220841_logo bps solsel.jpg',
  'sosial, ipm, kemiskinan, susenas'
),
(
  5,
  5,
  'Internal',
  'Yes',
  'Dasbor Presensi & Monitoring Jaringan BPS 1310',
  'Portal monitoring perangkat router, server lokal, dan kehadiran aparatur BPS Solok Selatan.',
  'https://noc.internal.bps1310.test/',
  'logo_bps.jpg',
  'ti, server, presensi, noc, vpn'
),
(
  6,
  6,
  'Public',
  'No',
  'Pejabat Pengelola Informasi dan Dokumentasi (PPID)',
  'Layanan permohonan informasi publik dan keterbukaan dokumentasi BPS Kabupaten Solok Selatan.',
  'https://ppid.bps.go.id/',
  '1790925715_PPID.png',
  'umum, ppid, permohonan, informasi publik'
),
(
  7,
  7,
  'Public',
  'No',
  'Pelayanan Statistik Terpadu (PST) Online',
  'Pemesanan konsultasi statistik, pembelian publikasi, dan rekomendasi kegiatan statistik.',
  'https://pst.bps.go.id/',
  '1790220841_logo bps solsel.jpg',
  'diseminasi, pst, konsultasi, mikrodata, publikasi'
),
(
  8,
  5,
  'Internal',
  'No',
  'Manajemen Repositori Dokumen Kantor (DocHub)',
  'Penyimpanan arsip digital surat keputusan, nota dinas, dan notulensi rapat kantor.',
  'http://dochub.local/',
  'logo_bps.jpg',
  'ti, arsip, dokumen, surat'
)
ON DUPLICATE KEY UPDATE
  `id_kategori` = VALUES(`id_kategori`),
  `akses` = VALUES(`akses`),
  `vpn` = VALUES(`vpn`),
  `nama_layanan` = VALUES(`nama_layanan`),
  `deskripsi_layanan` = VALUES(`deskripsi_layanan`),
  `url` = VALUES(`url`),
  `logo` = VALUES(`logo`),
  `keyword` = VALUES(`keyword`);
