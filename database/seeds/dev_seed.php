<?php

declare(strict_types=1);

/**
 * Deterministic Development Seed Script
 * Target: bps_solsel_dev / bps_solsel_test
 *
 * Usage:
 *   php database/seeds/dev_seed.php [--db=database_name] [--quiet]
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

function runDevSeed(?PDO $pdo = null, ?string $targetDb = null, bool $quiet = false): void
{
    if ($pdo === null) {
        $dbName = $targetDb ?: DB_NAME;
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . $dbName . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    // 1. Password generation (from DEV_ADMIN_PASSWORD or random 12-char hex)
    $devPasswordEnv = env('DEV_ADMIN_PASSWORD') ?: getenv('DEV_ADMIN_PASSWORD');
    $isRandom = false;

    if (!empty($devPasswordEnv)) {
        $rawPassword = (string)$devPasswordEnv;
    } else {
        $rawPassword = bin2hex(random_bytes(6));
        $isRandom = true;
    }

    $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);

    if (!$quiet) {
        echo "Menjalankan Dev Seed...\n";
    }

    // 2. Units (Unit Kerja BPS Kabupaten Solok Selatan)
    $units = [
        [1, 'Subbagian Umum'],
        [2, 'Fungsi Statistik Sosial'],
        [3, 'Fungsi Statistik Produksi'],
        [4, 'Fungsi Statistik Distribusi'],
        [5, 'Fungsi Neraca Wilayah dan Analisis Statistik'],
        [6, 'Fungsi Integrasi Pengolahan dan Diseminasi Statistik'],
    ];

    $stmtUnit = $pdo->prepare("
        INSERT INTO `units` (`id`, `nama`)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`)
    ");
    foreach ($units as $u) {
        $stmtUnit->execute($u);
    }
    if (!$quiet) {
        echo "-> Seed units: OK (6 baris)\n";
    }

    // 3. Kategori (7 Kategori Standar)
    $kategori = [
        [1, 'Distribusi', 'Layanan yang berkaitan dengan pelaporan perusahaan, harga komoditas, dan kegiatan sensus ekonomi.', 1],
        [2, 'Produksi', 'Layanan yang berkaitan dengan pendataan dan statistik sektor pertanian, perkebunan, dan industri.', 1],
        [3, 'Neraca', 'Layanan yang berkaitan dengan data PDRB, neraca wilayah, dan kompilasi data ekonomi daerah.', 1],
        [4, 'Sosial', 'Layanan yang berkaitan dengan kependudukan, kemiskinan, ketenagakerjaan, dan pembangunan manusia.', 1],
        [5, 'TI', 'Sistem teknologi informasi, integrasi aplikasi portal, pemetaan spasial, dan infrastruktur jaringan.', 1],
        [6, 'Umum', 'Layanan pendukung administrasi kepegawaian, surat menyurat, pengadaan, dan PPID.', 1],
        [7, 'Diseminasi', 'Layanan penyebaran publikasi, portal data terbuka, dan konsultasi statistik terpadu.', 1],
    ];

    $stmtKat = $pdo->prepare("
        INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`, `is_active`)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            `nama_kategori` = VALUES(`nama_kategori`),
            `deskripsi` = VALUES(`deskripsi`),
            `is_active` = VALUES(`is_active`)
    ");
    foreach ($kategori as $k) {
        $stmtKat->execute($k);
    }
    if (!$quiet) {
        echo "-> Seed kategori: OK (7 baris)\n";
    }

    // 4. Admin & Pengguna (Admin IT, Admin Dashboard / Leader, User Portal, & Calon User Pending)
    $admins = [
        [
            1, 'Admin', 'Pengembang', 'admin', 'admin@bps1310.cloud',
            $hashedPassword, 'approved', 'ADMIN IT', 6, 1, 1, null, null
        ],
        [
            2, 'Operator', 'Kategori', 'operator', 'operator@bps1310.cloud',
            $hashedPassword, 'approved', 'ADMIN DASHBOARD', 1, 1, 1, null, null
        ],
        [
            3, 'Pegawai', 'Portal', 'pegawai_portal', 'pegawai@bps1310.cloud',
            $hashedPassword, 'approved', 'USER APP PORTAL', 2, 1, 1, null, null
        ],
        [
            4, 'Calon', 'User', null, 'calon_user@bps1310.cloud',
            null, 'pending', 'USER APP PORTAL', 4, 1, 0, 'token_test_portal_aktif', date('Y-m-d H:i:s', time() + 86400)
        ],
    ];

    $stmtAdmin = $pdo->prepare("
        INSERT INTO `admin` (
            `id_admin`, `first_name`, `last_name`, `username`, `email`,
            `password`, `status`, `role`, `unit_id`, `is_active`,
            `email_verified`, `verification_token`, `verification_expires`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            `first_name` = VALUES(`first_name`),
            `last_name` = VALUES(`last_name`),
            `username` = VALUES(`username`),
            `email` = VALUES(`email`),
            `password` = VALUES(`password`),
            `status` = VALUES(`status`),
            `role` = VALUES(`role`),
            `unit_id` = VALUES(`unit_id`),
            `is_active` = VALUES(`is_active`),
            `email_verified` = VALUES(`email_verified`),
            `verification_token` = VALUES(`verification_token`),
            `verification_expires` = VALUES(`verification_expires`)
    ");
    foreach ($admins as $adm) {
        $stmtAdmin->execute($adm);
    }
    if (!$quiet) {
        echo "-> Seed akun admin/pengguna: OK (4 akun)\n";
    }

    // 5. Layanan Katalog
    $layanan = [
        [
            1, 1, 'Internal', 'Yes',
            'Sistem Monitoring Survei Harga Konsumen (Simonev SHK)',
            'Pemantauan berkala pencacahan harga komoditas pasar tradisional di Solok Selatan.',
            'https://shk.internal.bps1310.test/', 'logo_bps.jpg', 'distribusi, shk, harga, inflasi',
            'published', 4
        ],
        [
            2, 2, 'Internal', 'Yes',
            'Aplikasi Entri Data Statistik Pertanian Hortikultura (SPH)',
            'Entri dan validasi data laporan statistik pertanian tanaman pangan dan hortikultura tingkat kecamatan.',
            'https://pertanian.internal.bps1310.test/', 'logo_bps.jpg', 'produksi, sph, pertanian, panen',
            'published', 3
        ],
        [
            3, 3, 'Public', 'No',
            'Kompilasi Tabel PDRB Kabupaten Solok Selatan',
            'Tabel ringkasan Produk Domestik Regional Bruto menurut lapangan usaha dan pengeluaran.',
            'https://solselkab.bps.go.id/subject/52/produk-domestik-regional-bruto--lapangan-usaha-.html',
            '1790220841_logo bps solsel.jpg', 'neraca, pdrb, ekonomi, pendapatan regional',
            'published', 5
        ],
        [
            4, 4, 'Public', 'No',
            'Portal Indikator Kemiskinan dan IPM Solok Selatan',
            'Informasi agregat persentase penduduk miskin, garis kemiskinan, dan indeks pembangunan manusia.',
            'https://solselkab.bps.go.id/subject/23/kemiskinan-dan-ketimpangan.html',
            '1790220841_logo bps solsel.jpg', 'sosial, ipm, kemiskinan, susenas',
            'published', 2
        ],
        [
            5, 5, 'Internal', 'Yes',
            'Dasbor Presensi & Monitoring Jaringan BPS 1310',
            'Portal monitoring perangkat router, server lokal, dan kehadiran aparatur BPS Solok Selatan.',
            'https://noc.internal.bps1310.test/', 'logo_bps.jpg', 'ti, server, presensi, noc, vpn',
            'published', 6
        ],
        [
            6, 6, 'Public', 'No',
            'Pejabat Pengelola Informasi dan Dokumentasi (PPID)',
            'Layanan permohonan informasi publik dan keterbukaan dokumentasi BPS Kabupaten Solok Selatan.',
            'https://ppid.bps.go.id/', '1790925715_PPID.png', 'umum, ppid, permohonan, informasi publik',
            'published', 1
        ],
        [
            7, 7, 'Public', 'No',
            'Pelayanan Statistik Terpadu (PST) Online',
            'Pemesanan konsultasi statistik, pembelian publikasi, dan rekomendasi kegiatan statistik.',
            'https://pst.bps.go.id/', '1790220841_logo bps solsel.jpg', 'diseminasi, pst, konsultasi, mikrodata, publikasi',
            'published', 6
        ],
        [
            8, 5, 'Internal', 'No',
            'Manajemen Repositori Dokumen Kantor (DocHub)',
            'Penyimpanan arsip digital surat keputusan, nota dinas, dan notulensi rapat kantor.',
            'http://dochub.local/', 'logo_bps.jpg', 'ti, arsip, dokumen, surat',
            'published', 6
        ],
    ];

    $stmtLay = $pdo->prepare("
        INSERT INTO `layanan` (
            `id_layanan`, `id_kategori`, `akses`, `vpn`, `nama_layanan`,
            `deskripsi_layanan`, `url`, `logo`, `keyword`, `status`, `unit_id`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            `id_kategori` = VALUES(`id_kategori`),
            `akses` = VALUES(`akses`),
            `vpn` = VALUES(`vpn`),
            `nama_layanan` = VALUES(`nama_layanan`),
            `deskripsi_layanan` = VALUES(`deskripsi_layanan`),
            `url` = VALUES(`url`),
            `logo` = VALUES(`logo`),
            `keyword` = VALUES(`keyword`),
            `status` = VALUES(`status`),
            `unit_id` = VALUES(`unit_id`)
    ");
    foreach ($layanan as $l) {
        $stmtLay->execute($l);
    }
    if (!$quiet) {
        echo "-> Seed layanan katalog: OK (8 baris)\n";
    }

    // 6. Settings default
    $settings = [
        ['site_title', 'Portal Layanan Internal BPS Kabupaten Solok Selatan'],
        ['admin_contact', 'admin@bps1310.cloud'],
        ['app_version', '1.0.0-dev'],
        ['allow_public_registration', '0'],
    ];

    $stmtSet = $pdo->prepare("
        INSERT INTO `settings` (`skey`, `svalue`)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `svalue` = VALUES(`svalue`)
    ");
    foreach ($settings as $s) {
        $stmtSet->execute($s);
    }
    if (!$quiet) {
        echo "-> Seed settings: OK (4 baris)\n";
    }

    // 7. Output kredensial
    if (!$quiet) {
        echo "=========================================================\n";
        echo " SEED DEV SELESAI\n";
        echo "---------------------------------------------------------\n";
        if ($isRandom) {
            echo " [INFO] Password acak baru dibuat untuk akun dev:\n";
            echo " -> Password: {$rawPassword}\n";
            echo " (Catat password ini atau atur DEV_ADMIN_PASSWORD di .env)\n";
        } else {
            echo " [INFO] Akun dev berhasil diinisialisasi memakai DEV_ADMIN_PASSWORD dari .env.\n";
        }
        echo " Akun yang tersedia: admin, operator, pegawai_portal\n";
        echo "=========================================================\n";
    }
}

// CLI Execution Entry Point
if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $targetDb = null;
    $quiet = false;

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--db=')) {
            $targetDb = substr($arg, 5);
        }
        if ($arg === '--quiet') {
            $quiet = true;
        }
    }

    try {
        runDevSeed(null, $targetDb, $quiet);
    } catch (\Throwable $e) {
        fwrite(STDERR, "Error saat menjalankan dev seed: " . $e->getMessage() . "\n");
        exit(1);
    }
}
