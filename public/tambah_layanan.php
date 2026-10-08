<?php

require_once '../config/config.php';

/* =========================
   CEK LOGIN ADMIN
========================= */

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_role = $_SESSION['admin_role'] ?? '';

/* =========================
   AMBIL DATA KATEGORI
========================= */

$stmt = $conn->query("
    SELECT id_kategori, nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");

$kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   AMBIL DATA LAYANAN
   UNTUK AUTOCOMPLETE
========================= */

$stmt_saran = $conn->query("
    SELECT
        l.nama_layanan,
        k.nama_kategori
    FROM layanan l
    LEFT JOIN kategori k
        ON l.id_kategori = k.id_kategori
    WHERE l.nama_layanan IS NOT NULL
    AND l.nama_layanan <> ''
    ORDER BY l.nama_layanan ASC
");

$data_autocomplete = $stmt_saran->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   PESAN
========================= */

$pesan = '';


/* =========================
   NILAI FORM
========================= */

$nama_layanan = '';
$deskripsi_layanan = '';
$url = '';
$id_kategori = '';
$akses = '';
$vpn = '';


/* =========================
   PROSES SIMPAN
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_layanan = trim($_POST['nama_layanan'] ?? '');
    $deskripsi_layanan = trim($_POST['deskripsi_layanan'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $id_kategori = $_POST['id_kategori'] ?? '';
    $akses = $_POST['akses'] ?? '';
    $vpn = $_POST['vpn'] ?? '';

    $logo = '';


    /* =========================
       VALIDASI
    ========================= */

    if (isset($_POST['csrf_token']) && !csrf_verify()) {

        $pesan = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';

    } elseif (
        $nama_layanan === '' ||
        $deskripsi_layanan === '' ||
        $url === '' ||
        $id_kategori === '' ||
        $akses === '' ||
        $vpn === ''
    ) {

        $pesan = 'Semua data wajib diisi.';

    } elseif (!in_array($akses, ['Internal', 'Public'], true)) {

        $pesan = 'Pilihan akses tidak valid.';

    } elseif (!in_array($vpn, ['Yes', 'No'], true)) {

        $pesan = 'Pilihan VPN tidak valid.';

    } else {

        /* =========================
           UPLOAD LOGO
        ========================= */

        if (
            isset($_FILES['logo']) &&
            $_FILES['logo']['error'] === UPLOAD_ERR_OK
        ) {

            $folder_upload = __DIR__ . '/uploads/';

            if (!is_dir($folder_upload)) {
                mkdir($folder_upload, 0777, true);
            }

            $nama_asli = basename($_FILES['logo']['name']);

            $ekstensi = strtolower(
                pathinfo($nama_asli, PATHINFO_EXTENSION)
            );

            $format_diizinkan = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_tipe = $finfo->file($_FILES['logo']['tmp_name']);
            $mime_diizinkan = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            if (
                in_array($ekstensi, $format_diizinkan, true) &&
                in_array($mime_tipe, $mime_diizinkan, true) &&
                $_FILES['logo']['size'] <= 2 * 1024 * 1024
            ) {

                $nama_file =
                    time() . '_' .
                    preg_replace(
                        '/[^A-Za-z0-9._-]/',
                        '_',
                        $nama_asli
                    );

                $target = $folder_upload . $nama_file;

                if (
                    move_uploaded_file(
                        $_FILES['logo']['tmp_name'],
                        $target
                    )
                ) {

                    $logo = $nama_file;
                }
            }
        }


        /* =========================
           SIMPAN DATABASE
        ========================= */

        try {

            $stmt_insert = $conn->prepare("
                INSERT INTO layanan
                (
                    id_kategori,
                    nama_layanan,
                    deskripsi_layanan,
                    url,
                    logo,
                    akses,
                    vpn
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $stmt_insert->execute([
                (int) $id_kategori,
                $nama_layanan,
                $deskripsi_layanan,
                $url,
                $logo,
                $akses,
                $vpn
            ]);


            /* =========================
               KEMBALI KE KELOLA LAYANAN
            ========================= */

            header('Location: layanan.php');
            exit;

        } catch (PDOException $e) {

            $pesan = 'Gagal menyimpan data layanan ke database.';
        }
    }
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Tambah Layanan - BPS Solok Selatan</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =====================================================
   BODY
===================================================== */

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f4f8fc;
    color: #333;
    overflow-x: hidden;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {
    width: 240px;
    height: 100vh;
    background: #0066b3;
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    padding: 25px 15px;
    z-index: 9999;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.logo {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 40px;
    padding: 0 15px;
}

.menu {
    list-style: none;
}

.menu li {
    margin-bottom: 8px;
    width: 100%;
}

.menu a {
    display: block;
    width: 100%;
    color: #dceeff;
    text-decoration: none;
    padding: 12px 15px;
    border-radius: 8px;
    transition: 0.2s;
}

.menu a:hover {
    background: rgba(255, 255, 255, 0.15);
    color: white;
}

.menu .active {
    background: white;
    color: #0066b3;
    font-weight: bold;
}


/* =====================================================
   MOBILE MENU
===================================================== */

.mobile-menu-button {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    width: 45px;
    height: 45px;
    border: none;
    border-radius: 9px;
    background: #0066b3;
    color: white;
    font-size: 24px;
    cursor: pointer;
    z-index: 10001;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
    align-items: center;
    justify-content: center;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
    z-index: 9998;
}

.sidebar-overlay.show {
    display: block;
}


/* =====================================================
   MAIN
===================================================== */

.main {
    margin-left: 240px;
    padding: 30px;
    min-width: 0;
}

.header {
    margin-bottom: 25px;
}

.header h1 {
    font-size: 28px;
    margin-bottom: 8px;
    color: #005a9c;
}

.header p {
    color: #6b7280;
    line-height: 1.5;
}


/* =====================================================
   ALERT
===================================================== */

.alert {
    max-width: 850px;
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}


/* =====================================================
   FORM
===================================================== */

.form-container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);
    border-left: 4px solid #0066b3;
    max-width: 850px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    color: #005a9c;
}

input,
select,
textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d6e3ef;
    border-radius: 6px;
    font-size: 14px;
    background: white;
    font-family: inherit;
    resize: vertical;
}

textarea {
    min-height: 100px;
    line-height: 1.5;
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: #0066b3;
    box-shadow: 0 0 0 2px rgba(0, 102, 179, 0.08);
}


/* =====================================================
   AUTOCOMPLETE
===================================================== */

.autocomplete-wrapper {
    position: relative;
    width: 100%;
}

.autocomplete-list {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #d6e3ef;
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    z-index: 999;
    max-height: 300px;
    overflow-y: auto;
    display: none;
}

.autocomplete-list.show {
    display: block;
}

.autocomplete-item {
    padding: 12px 14px;
    cursor: pointer;
    border-bottom: 1px solid #edf2f7;
    transition: background 0.15s ease;
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.autocomplete-item:hover,
.autocomplete-item.active {
    background: #eef7ff;
}

.autocomplete-name {
    color: #005a9c;
    font-weight: 600;
    font-size: 14px;
    line-height: 1.4;
}

.autocomplete-category {
    margin-top: 3px;
    color: #7b8794;
    font-size: 12px;
}

.autocomplete-icon {
    color: #0066b3;
    margin-right: 7px;
}

.autocomplete-empty {
    padding: 13px 14px;
    color: #7b8794;
    font-size: 13px;
    line-height: 1.5;
}

.autocomplete-hint {
    margin-top: 7px;
    color: #7b8794;
    font-size: 12px;
}

mark {
    background: #fff2a8;
    color: inherit;
    padding: 0 1px;
    border-radius: 2px;
}


/* =====================================================
   TYPO / SARAN OTOMATIS
===================================================== */

.typo-suggestion {
    display: none;
    margin-top: 8px;
    padding: 10px 12px;
    border-radius: 7px;
    background: #fff8e1;
    border: 1px solid #ffe082;
    color: #765500;
    font-size: 13px;
    line-height: 1.6;
}

.typo-suggestion.show {
    display: block;
}

.typo-button {
    border: none;
    background: transparent;
    color: #0066b3;
    font-weight: bold;
    cursor: pointer;
    padding: 0;
    font-size: 13px;
}

.typo-button:hover {
    text-decoration: underline;
}


/* =====================================================
   BUTTON
===================================================== */

.buttons {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn {
    padding: 11px 18px;
    border-radius: 6px;
    border: none;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
    text-align: center;
}

.btn-save {
    background: #0066b3;
    color: white;
}

.btn-save:hover {
    background: #005a9c;
}

.btn-cancel {
    background: #e5edf5;
    color: #005a9c;
}

.btn-cancel:hover {
    background: #d6e5f2;
}


/* =====================================================
   RESPONSIVE MOBILE
===================================================== */

@media (max-width: 768px) {

    .sidebar {
        width: 250px;
        height: 100vh;
        transform: translateX(-100%);
        padding: 25px 15px;
    }

    .sidebar.mobile-open {
        transform: translateX(0);
    }

    .mobile-menu-button {
        display: flex;
    }

    .main {
        margin-left: 0;
        padding: 75px 15px 25px;
        width: 100%;
    }

    .header {
        margin-bottom: 20px;
    }

    .header h1 {
        font-size: 24px;
    }

    .header p {
        font-size: 14px;
    }

    .alert {
        width: 100%;
        font-size: 14px;
        line-height: 1.5;
    }

    .form-container {
        width: 100%;
        max-width: none;
        padding: 20px;
        border-left: 4px solid #0066b3;
    }

    .form-group {
        margin-bottom: 18px;
    }

    label {
        font-size: 14px;
    }

    input,
    select,
    textarea {
        font-size: 14px;
        padding: 12px;
    }

    .buttons {
        flex-direction: column;
        gap: 10px;
    }

    .btn {
        width: 100%;
        padding: 12px;
    }

    .autocomplete-list {
        max-height: 250px;
    }
}


/* =====================================================
   HP SANGAT KECIL
===================================================== */

@media (max-width: 480px) {

    .main {
        padding-left: 12px;
        padding-right: 12px;
    }

    .header h1 {
        font-size: 22px;
    }

    .header p {
        font-size: 13px;
    }

    .form-container {
        padding: 18px 15px;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     TOMBOL MENU MOBILE
===================================================== -->

<button
    type="button"
    class="mobile-menu-button"
    id="mobileMenuButton"
    onclick="toggleMobileSidebar()"
    aria-label="Buka menu"
>
    ☰
</button>


<!-- =====================================================
     OVERLAY MOBILE
===================================================== -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
    onclick="closeMobileSidebar()"
></div>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar" id="sidebar">

    <div class="logo">
        Dashboard Admin
    </div>

    <ul class="menu">

        <li>
            <a href="index.php">
                Dashboard
            </a>
        </li>

        <li>
            <a href="layanan.php" class="active">
                Kelola Layanan
            </a>
        </li>

        <li>
            <a href="kategori.php">
                Kategori
            </a>
        </li>

        <?php if ($admin_role === 'ADMIN IT'): ?>

            <li>
                <a href="kelola_admin.php">
                    Kelola Admin
                </a>
            </li>

            <li>
                <a href="kelola_user.php">
                    Kelola User
                </a>
            </li>

        <?php endif; ?>

        <li>
            <a href="pengaturan.php">
                Pengaturan
            </a>
        </li>

    </ul>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">

    <div class="header">

        <h1>Tambah Layanan</h1>

        <p>
            Tambahkan layanan baru BPS Kabupaten Solok Selatan.
        </p>

    </div>


    <?php if ($pesan !== ''): ?>

        <div class="alert">
            <?= htmlspecialchars($pesan); ?>
        </div>

    <?php endif; ?>


    <div class="form-container">

        <form
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off"
        >

            <?= csrf_field(); ?>


            <!-- =================================================
                 1. NAMA LAYANAN
            ================================================== -->

            <div class="form-group">

                <label for="nama_layanan">
                    Nama Layanan
                </label>


                <div class="autocomplete-wrapper">

                    <input
                        type="text"
                        id="nama_layanan"
                        name="nama_layanan"
                        value="<?= htmlspecialchars($nama_layanan); ?>"
                        placeholder="Contoh: Sistem Statistik Produksi"
                        required
                    >


                    <div
                        class="autocomplete-list"
                        id="autocompleteList"
                    ></div>

                </div>


                <div class="autocomplete-hint">
                    💡 Ketik nama layanan untuk mendapatkan
                    saran otomatis. Layanan baru juga tetap
                    akan mendapatkan saran.
                </div>


                <div
                    class="typo-suggestion"
                    id="typoSuggestion"
                ></div>

            </div>


            <!-- =================================================
                 2. DESKRIPSI LAYANAN
            ================================================== -->

            <div class="form-group">

                <label for="deskripsi_layanan">
                    Deskripsi Layanan
                </label>

                <textarea
                    id="deskripsi_layanan"
                    name="deskripsi_layanan"
                    placeholder="Masukkan deskripsi layanan"
                    rows="4"
                    required
                ><?= htmlspecialchars($deskripsi_layanan); ?></textarea>

            </div>


            <!-- =================================================
                 3. URL / LINK
            ================================================== -->

            <div class="form-group">

                <label for="url">
                    URL / Link
                </label>

                <input
                    type="url"
                    id="url"
                    name="url"
                    value="<?= htmlspecialchars($url); ?>"
                    placeholder="https://contoh.bps.go.id"
                    required
                >

            </div>


            <!-- =================================================
                 4. KATEGORI
            ================================================== -->

            <div class="form-group">

                <label for="id_kategori">
                    Kategori
                </label>

                <select
                    id="id_kategori"
                    name="id_kategori"
                    required
                >

                    <option value="">
                        -- Pilih Kategori --
                    </option>

                    <?php foreach ($kategori as $k): ?>

                        <option
                            value="<?= $k['id_kategori']; ?>"
                            <?= (
                                (string) $id_kategori ===
                                (string) $k['id_kategori']
                            ) ? 'selected' : ''; ?>
                        >

                            <?= htmlspecialchars(
                                $k['nama_kategori']
                            ); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =================================================
                 5. AKSES
            ================================================== -->

            <div class="form-group">

                <label for="akses">
                    Akses
                </label>

                <select
                    id="akses"
                    name="akses"
                    required
                >

                    <option value="">
                        -- Pilih Akses --
                    </option>

                    <option
                        value="Internal"
                        <?= $akses === 'Internal'
                            ? 'selected'
                            : ''; ?>
                    >
                        Internal
                    </option>

                    <option
                        value="Public"
                        <?= $akses === 'Public'
                            ? 'selected'
                            : ''; ?>
                    >
                        Public
                    </option>

                </select>

            </div>


            <!-- =================================================
                 6. VPN
            ================================================== -->

            <div class="form-group">

                <label for="vpn">
                    VPN
                </label>

                <select
                    id="vpn"
                    name="vpn"
                    required
                >

                    <option value="">
                        -- Pilih VPN --
                    </option>

                    <option
                        value="Yes"
                        <?= $vpn === 'Yes'
                            ? 'selected'
                            : ''; ?>
                    >
                        Yes
                    </option>

                    <option
                        value="No"
                        <?= $vpn === 'No'
                            ? 'selected'
                            : ''; ?>
                    >
                        No
                    </option>

                </select>

            </div>


            <!-- =================================================
                 7. LOGO
            ================================================== -->

            <div class="form-group">

                <label for="logo">
                    Logo
                </label>

                <input
                    type="file"
                    id="logo"
                    name="logo"
                    accept=".jpg,.jpeg,.png,.webp"
                >

            </div>


            <!-- =================================================
                 BUTTON
===================================================== -->

            <div class="buttons">

                <button
                    type="submit"
                    class="btn btn-save"
                >
                    Simpan Layanan
                </button>

                <a
                    href="layanan.php"
                    class="btn btn-cancel"
                >
                    Batal
                </a>

            </div>

        </form>

    </div>

</main>


<script>

/* =====================================================
   DATA AUTOCOMPLETE DARI DATABASE
===================================================== */

const layananDatabase = <?= json_encode(
    $data_autocomplete,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
); ?>;


/* =====================================================
   SARAN TAMBAHAN
===================================================== */

const saranTambahan = [

    {
        nama: 'Sistem Statistik Produksi',
        kategori: 'Statistik Produksi'
    },

    {
        nama: 'Sistem Statistik Sosial',
        kategori: 'Statistik Sosial'
    },

    {
        nama: 'Sistem Statistik Distribusi',
        kategori: 'Statistik Distribusi'
    },

    {
        nama: 'Sistem Statistik Neraca',
        kategori: 'Statistik Neraca'
    },

    {
        nama: 'Sistem Informasi Kepegawaian',
        kategori: 'Sistem Informasi'
    },

    {
        nama: 'Sistem Informasi Manajemen',
        kategori: 'Sistem Informasi'
    },

    {
        nama: 'Sistem Informasi Data Statistik',
        kategori: 'Sistem Informasi'
    },

    {
        nama: 'Dashboard BPS',
        kategori: 'Dashboard'
    },

    {
        nama: 'Dashboard Statistik',
        kategori: 'Dashboard'
    },

    {
        nama: 'Dashboard Monitoring Data',
        kategori: 'Dashboard'
    },

    {
        nama: 'Portal Data Statistik',
        kategori: 'Portal Data'
    },

    {
        nama: 'Portal Layanan BPS',
        kategori: 'Portal Layanan'
    },

    {
        nama: 'Layanan Statistik',
        kategori: 'Layanan BPS'
    },

    {
        nama: 'Data Statistik',
        kategori: 'Data BPS'
    },

    {
        nama: 'Pengelolaan Data Statistik',
        kategori: 'Data BPS'
    },

    {
        nama: 'Monitoring Data Statistik',
        kategori: 'Monitoring'
    },

    {
        nama: 'Indonesia Data Hub',
        kategori: 'INDAH'
    },

    {
        nama: 'INDAH - Indonesia Data Hub',
        kategori: 'INDAH'
    },

    {
        nama: 'PPID BPS',
        kategori: 'PPID'
    },

    {
        nama: 'Website BPS Kabupaten Solok Selatan',
        kategori: 'Website BPS'
    },

    {
        nama: 'Aplikasi Statistik',
        kategori: 'Aplikasi BPS'
    },

    {
        nama: 'Aplikasi Pengolahan Data',
        kategori: 'Aplikasi BPS'
    },

    {
        nama: 'Sistem Pengolahan Data',
        kategori: 'Pengolahan Data'
    },

    {
        nama: 'Sistem Monitoring',
        kategori: 'Monitoring'
    },

    {
        nama: 'Sistem Manajemen Data',
        kategori: 'Manajemen Data'
    },

    {
        nama: 'Sistem Pelayanan Publik',
        kategori: 'Pelayanan Publik'
    }

];


/* =====================================================
   GABUNGKAN DATABASE + SARAN TAMBAHAN
===================================================== */

const semuaSaran = [];


layananDatabase.forEach(function(item) {

    if (!item.nama_layanan) {
        return;
    }

    semuaSaran.push({
        nama: item.nama_layanan,
        kategori: item.nama_kategori || 'Layanan BPS'
    });

});


saranTambahan.forEach(function(item) {

    semuaSaran.push({
        nama: item.nama,
        kategori: item.kategori
    });

});


/* =====================================================
   HAPUS DUPLIKAT
===================================================== */

const saranUnik = [];

const sudahAda = new Set();

semuaSaran.forEach(function(item) {

    const key = item.nama
        .trim()
        .toLowerCase();

    if (!sudahAda.has(key)) {

        sudahAda.add(key);

        saranUnik.push(item);

    }

});


/* =====================================================
   ELEMENT
===================================================== */

const namaInput =
    document.getElementById('nama_layanan');

const autocompleteList =
    document.getElementById('autocompleteList');

const typoSuggestion =
    document.getElementById('typoSuggestion');


/* =====================================================
   STATE
===================================================== */

let hasilSaatIni = [];

let indexAktif = -1;


/* =====================================================
   ESCAPE HTML
===================================================== */

function escapeHtml(text) {

    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

}


/* =====================================================
   NORMALISASI TEKS
===================================================== */

function normalisasi(text) {

    return String(text)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();

}


/* =====================================================
   UBAH KE TITLE CASE
===================================================== */

function titleCase(text) {

    return String(text)
        .toLowerCase()
        .replace(/\b\w/g, function(char) {
            return char.toUpperCase();
        });

}


/* =====================================================
   PISAHKAN KATA / TEKS YANG MENEMPEL
===================================================== */

function pecahKata(text) {

    let hasil = String(text).trim();


    /*
     * Pisahkan huruf dan angka.
     *
     * ANOMALISE2026
     * menjadi:
     *
     * ANOMALISE 2026
     */

    hasil = hasil.replace(
        /([A-Za-z])(\d)/g,
        '$1 $2'
    );

    hasil = hasil.replace(
        /(\d)([A-Za-z])/g,
        '$1 $2'
    );


    /*
     * Pisahkan huruf kapital yang menempel.
     *
     * DataBPS
     * menjadi:
     *
     * Data BPS
     */

    hasil = hasil.replace(
        /([a-z])([A-Z])/g,
        '$1 $2'
    );


    /*
     * Rapikan spasi.
     */

    hasil = hasil
        .replace(/\s+/g, ' ')
        .trim();


    return hasil;

}


/* =====================================================
   HITUNG RELEVANSI
===================================================== */

function hitungRelevansi(nama, keyword) {

    const teks = normalisasi(nama);

    const query = normalisasi(keyword);

    if (!query) {
        return 0;
    }


    if (teks === query) {
        return 1000;
    }


    if (teks.startsWith(query)) {
        return 900;
    }


    const kata = teks.split(/\s+/);


    if (
        kata.some(function(k) {
            return k.startsWith(query);
        })
    ) {

        return 800;

    }


    if (teks.includes(query)) {
        return 700;
    }


    let skor = 0;

    kata.forEach(function(k) {

        if (k.includes(query)) {

            skor = Math.max(
                skor,
                600
            );

        }

    });


    return skor;

}


/* =====================================================
   LEVENSHTEIN
===================================================== */

function levenshtein(a, b) {

    a = normalisasi(a);

    b = normalisasi(b);

    const matrix = [];


    for (
        let i = 0;
        i <= b.length;
        i++
    ) {

        matrix[i] = [i];

    }


    for (
        let j = 0;
        j <= a.length;
        j++
    ) {

        matrix[0][j] = j;

    }


    for (
        let i = 1;
        i <= b.length;
        i++
    ) {

        for (
            let j = 1;
            j <= a.length;
            j++
        ) {

            if (
                b.charAt(i - 1) ===
                a.charAt(j - 1)
            ) {

                matrix[i][j] =
                    matrix[i - 1][j - 1];

            } else {

                matrix[i][j] = Math.min(

                    matrix[i - 1][j - 1] + 1,

                    matrix[i][j - 1] + 1,

                    matrix[i - 1][j] + 1

                );

            }

        }

    }


    return matrix[b.length][a.length];

}


/* =====================================================
   CARI TYPO
===================================================== */

function cariTypo(keyword) {

    const query =
        normalisasi(keyword);


    if (
        query.length < 4
    ) {

        return null;

    }


    let kandidatTerbaik = null;

    let skorTerbaik = Infinity;


    saranUnik.forEach(function(item) {

        const nama =
            normalisasi(item.nama);


        const jarak =
            levenshtein(
                query,
                nama
            );


        if (
            jarak < skorTerbaik
        ) {

            skorTerbaik = jarak;

            kandidatTerbaik = item;

        }

    });


    const batas = Math.max(

        2,

        Math.floor(
            query.length * 0.35
        )

    );


    if (
        kandidatTerbaik &&
        skorTerbaik <= batas
    ) {

        return kandidatTerbaik;

    }


    return null;

}


/* =====================================================
   BUAT SARAN UNTUK LAYANAN BARU
===================================================== */

function buatSaranLayananBaru(keyword) {

    const teksAsli =
        String(keyword).trim();


    if (!teksAsli) {
        return [];
    }


    const teksPecah =
        pecahKata(teksAsli);


    const title =
        titleCase(teksPecah);


    const hasil = [];


    function tambahSaran(
        nama,
        kategori
    ) {

        if (!nama) {
            return;
        }


        const sudahAda =
            hasil.some(function(item) {

                return normalisasi(
                    item.nama
                ) === normalisasi(nama);

            });


        if (!sudahAda) {

            hasil.push({

                nama: nama,

                kategori: kategori

            });

        }

    }


    /*
     * =================================================
     * SARAN 1
     *
     * Bentuk yang sudah diberi spasi.
     *
     * ANOMALISE2026
     * ->
     * Anomalise 2026
     * =================================================
     */

    if (
        teksPecah !== teksAsli
    ) {

        tambahSaran(
            teksPecah,
            'Saran penulisan'
        );

    }


    /*
     * =================================================
     * SARAN 2
     *
     * Title Case.
     *
     * ANOMALISE2026
     * ->
     * Anomalise 2026
     * =================================================
     */

    tambahSaran(
        title,
        'Saran penulisan'
    );


    /*
     * =================================================
     * SARAN 3
     *
     * Sistem + nama
     * =================================================
     */

    tambahSaran(
        'Sistem ' + title,
        'Saran nama layanan'
    );


    /*
     * =================================================
     * SARAN 4
     *
     * Aplikasi + nama
     * =================================================
     */

    tambahSaran(
        'Aplikasi ' + title,
        'Saran nama layanan'
    );


    /*
     * =================================================
     * SARAN 5
     *
     * Portal + nama
     * =================================================
     */

    tambahSaran(
        'Portal ' + title,
        'Saran nama layanan'
    );


    /*
     * =================================================
     * SARAN 6
     *
     * Dashboard + nama
     * =================================================
     */

    tambahSaran(
        'Dashboard ' + title,
        'Saran nama layanan'
    );


    /*
     * =================================================
     * SARAN 7
     *
     * Sistem Informasi + nama
     * =================================================
     */

    tambahSaran(
        'Sistem Informasi ' + title,
        'Saran nama layanan'
    );


    /*
     * =================================================
     * SARAN 8
     *
     * nama + Statistik
     * =================================================
     */

    tambahSaran(
        title + ' Statistik',
        'Saran nama layanan'
    );


    /*
     * =================================================
     * SARAN 9
     *
     * nama + BPS
     * =================================================
     */

    tambahSaran(
        title + ' BPS',
        'Saran nama layanan'
    );


    return hasil.slice(0, 8);

}


/* =====================================================
   HIGHLIGHT QUERY
===================================================== */

function highlightText(
    text,
    query
) {

    const safeText =
        escapeHtml(text);


    const safeQuery =
        escapeHtml(query);


    if (!safeQuery) {

        return safeText;

    }


    const regex =
        new RegExp(

            '(' +

            safeQuery.replace(
                /[.*+?^${}()|[\]\\]/g,
                '\\$&'
            )

            + ')',

            'ig'

        );


    return safeText.replace(
        regex,
        '<mark>$1</mark>'
    );

}


/* =====================================================
   TAMPILKAN SARAN
===================================================== */

function tampilkanAutocomplete(
    keyword
) {

    const query =
        normalisasi(keyword);


    indexAktif = -1;

    hasilSaatIni = [];

    autocompleteList.innerHTML = '';

    typoSuggestion.classList.remove(
        'show'
    );

    typoSuggestion.innerHTML = '';


    if (!query) {

        autocompleteList.classList.remove(
            'show'
        );

        return;

    }


    /* =================================================
       1. CARI DARI DATABASE + SARAN TAMBAHAN
    ================================================= */

    const hasilDatabase =
        saranUnik

            .map(function(item) {

                return {

                    ...item,

                    skor:
                        hitungRelevansi(
                            item.nama,
                            query
                        )

                };

            })

            .filter(function(item) {

                return item.skor > 0;

            })

            .sort(function(a, b) {

                if (
                    b.skor !== a.skor
                ) {

                    return (
                        b.skor -
                        a.skor
                    );

                }


                return a.nama.localeCompare(
                    b.nama,
                    'id'
                );

            });


    /* =================================================
       2. BUAT SARAN DINAMIS UNTUK LAYANAN BARU
    ================================================= */

    const hasilBaru =
        buatSaranLayananBaru(
            keyword
        );


    /* =================================================
       3. GABUNGKAN
    ================================================= */

    const gabungan = [];


    hasilDatabase.forEach(
        function(item) {

            gabungan.push({
                nama: item.nama,
                kategori: item.kategori,
                skor: item.skor
            });

        }
    );


    hasilBaru.forEach(
        function(item) {

            gabungan.push({

                nama: item.nama,

                kategori: item.kategori,

                /*
                 * Skor lebih rendah daripada
                 * layanan yang benar-benar ada.
                 */

                skor: 500

            });

        }
    );


    /* =================================================
       4. HAPUS DUPLIKAT
    ================================================= */

    const hasilFinal = [];

    const namaSudahAda =
        new Set();


    gabungan.forEach(function(item) {

        const key =
            normalisasi(item.nama);


        if (
            namaSudahAda.has(key)
        ) {

            return;

        }


        namaSudahAda.add(key);

        hasilFinal.push(item);

    });


    /*
     * Batasi 8 hasil.
     */

    const hasil =
        hasilFinal.slice(0, 8);


    hasilSaatIni =
        hasil;


    /* =================================================
       5. TAMPILKAN
    ================================================= */

    if (hasil.length > 0) {

        hasil.forEach(
            function(item, index) {

                const element =
                    document.createElement(
                        'div'
                    );


                element.className =
                    'autocomplete-item';


                element.dataset.index =
                    index;


                element.innerHTML = `

                    <div class="autocomplete-name">

                        <span class="autocomplete-icon">
                            ${
                                item.skor >= 700
                                    ? '🔎'
                                    : '💡'
                            }
                        </span>

                        ${
                            item.skor >= 700
                                ? highlightText(
                                    item.nama,
                                    keyword
                                )
                                : escapeHtml(
                                    item.nama
                                )
                        }

                    </div>

                    <div class="autocomplete-category">

                        ${escapeHtml(
                            item.kategori
                        )}

                    </div>

                `;


                element.addEventListener(
                    'mousedown',
                    function(event) {

                        event.preventDefault();

                        pilihAutocomplete(
                            index
                        );

                    }
                );


                autocompleteList.appendChild(
                    element
                );

            }
        );


        autocompleteList.classList.add(
            'show'
        );


        /*
         * Kalau hasilnya hanya berasal
         * dari saran dinamis, tampilkan
         * keterangan bahwa ini saran.
         */

        const adaSaranBaru =
            hasil.some(function(item) {

                return item.skor === 500;

            });


        if (
            adaSaranBaru &&
            hasilDatabase.length === 0
        ) {

            const info =
                document.createElement(
                    'div'
                );


            info.className =
                'autocomplete-empty';


            info.innerHTML = `
                💡 <strong>Saran otomatis</strong>
                berdasarkan nama yang kamu ketik.
                <br>
                Nama layanan baru tetap boleh
                digunakan dan disimpan.
            `;


            autocompleteList.appendChild(
                info
            );

        }


        return;

    }


    /* =================================================
       6. FALLBACK
    ================================================= */

    autocompleteList.innerHTML = `

        <div class="autocomplete-empty">

            💡 <strong>Layanan baru</strong>

            <br>

            Nama ini belum ada di database,
            tetapi tetap boleh digunakan.

            <br><br>

            Kamu dapat melanjutkan mengetik
            nama layanan secara manual.

        </div>

    `;


    autocompleteList.classList.add(
        'show'
    );


    /* =================================================
       7. TYPO
    ================================================= */

    const typo =
        cariTypo(keyword);


    if (typo) {

        typoSuggestion.innerHTML = `

            💡 Mungkin maksud Anda:

            <button
                type="button"
                class="typo-button"
                id="gunakanTypo"
            >
                ${escapeHtml(
                    typo.nama
                )}
            </button>

        `;


        typoSuggestion.classList.add(
            'show'
        );


        document
            .getElementById(
                'gunakanTypo'
            )
            .addEventListener(
                'click',
                function() {

                    namaInput.value =
                        typo.nama;


                    autocompleteList
                        .classList
                        .remove(
                            'show'
                        );


                    typoSuggestion
                        .classList
                        .remove(
                            'show'
                        );


                    namaInput.focus();

                }
            );

    }

}


/* =====================================================
   PILIH AUTOCOMPLETE
===================================================== */

function pilihAutocomplete(
    index
) {

    if (
        !hasilSaatIni[index]
    ) {

        return;

    }


    namaInput.value =
        hasilSaatIni[index].nama;


    autocompleteList
        .classList
        .remove(
            'show'
        );


    typoSuggestion
        .classList
        .remove(
            'show'
        );


    indexAktif = -1;


    namaInput.focus();

}


/* =====================================================
   UPDATE ITEM AKTIF
===================================================== */

function updateItemAktif() {

    const items =
        autocompleteList.querySelectorAll(
            '.autocomplete-item'
        );


    items.forEach(
        function(item, index) {

            item.classList.toggle(
                'active',
                index === indexAktif
            );

        }
    );


    if (
        indexAktif >= 0 &&
        items[indexAktif]
    ) {

        items[indexAktif].scrollIntoView({
            block: 'nearest'
        });

    }

}


/* =====================================================
   INPUT EVENT
===================================================== */

namaInput.addEventListener(
    'input',
    function() {

        tampilkanAutocomplete(
            namaInput.value
        );

    }
);


/* =====================================================
   KEYBOARD NAVIGATION
===================================================== */

namaInput.addEventListener(
    'keydown',
    function(event) {

        const items =
            autocompleteList.querySelectorAll(
                '.autocomplete-item'
            );


        /* =========================
           ARROW DOWN
        ========================= */

        if (
            event.key === 'ArrowDown'
        ) {

            if (!items.length) {
                return;
            }

            event.preventDefault();

            indexAktif++;


            if (
                indexAktif >= items.length
            ) {

                indexAktif = 0;

            }


            updateItemAktif();

        }


        /* =========================
           ARROW UP
        ========================= */

        else if (
            event.key === 'ArrowUp'
        ) {

            if (!items.length) {
                return;
            }

            event.preventDefault();

            indexAktif--;


            if (
                indexAktif < 0
            ) {

                indexAktif =
                    items.length - 1;

            }


            updateItemAktif();

        }


        /* =========================
           ENTER
        ========================= */

        else if (
            event.key === 'Enter'
        ) {

            if (
                indexAktif >= 0 &&
                hasilSaatIni[indexAktif]
            ) {

                event.preventDefault();

                pilihAutocomplete(
                    indexAktif
                );

            }

        }


        /* =========================
           ESCAPE
        ========================= */

        else if (
            event.key === 'Escape'
        ) {

            autocompleteList
                .classList
                .remove(
                    'show'
                );


            typoSuggestion
                .classList
                .remove(
                    'show'
                );


            indexAktif = -1;

        }

    }
);


/* =====================================================
   CLICK DI LUAR AUTOCOMPLETE
===================================================== */

document.addEventListener(
    'click',
    function(event) {

        const wrapper =
            document.querySelector(
                '.autocomplete-wrapper'
            );


        if (
            !wrapper.contains(
                event.target
            )
        ) {

            autocompleteList
                .classList
                .remove(
                    'show'
                );

        }

    }
);


/* =====================================================
   SIDEBAR MOBILE
===================================================== */

function toggleMobileSidebar() {

    const sidebar =
        document.getElementById(
            'sidebar'
        );


    const overlay =
        document.getElementById(
            'sidebarOverlay'
        );


    const button =
        document.getElementById(
            'mobileMenuButton'
        );


    sidebar.classList.toggle(
        'mobile-open'
    );


    overlay.classList.toggle(
        'show'
    );


    if (
        sidebar.classList.contains(
            'mobile-open'
        )
    ) {

        button.innerHTML = '✕';


        button.setAttribute(
            'aria-label',
            'Tutup menu'
        );

    } else {

        button.innerHTML = '☰';


        button.setAttribute(
            'aria-label',
            'Buka menu'
        );

    }

}


/* =====================================================
   TUTUP SIDEBAR MOBILE
===================================================== */

function closeMobileSidebar() {

    const sidebar =
        document.getElementById(
            'sidebar'
        );


    const overlay =
        document.getElementById(
            'sidebarOverlay'
        );


    const button =
        document.getElementById(
            'mobileMenuButton'
        );


    sidebar.classList.remove(
        'mobile-open'
    );


    overlay.classList.remove(
        'show'
    );


    button.innerHTML = '☰';


    button.setAttribute(
        'aria-label',
        'Buka menu'
    );

}


/* =====================================================
   TUTUP SIDEBAR SETELAH PILIH MENU
===================================================== */

document
    .querySelectorAll('.menu a')
    .forEach(
        function(link) {

            link.addEventListener(
                'click',
                function() {

                    if (
                        window.innerWidth <= 768
                    ) {

                        closeMobileSidebar();

                    }

                }
            );

        }
    );

</script>


</body>

</html>