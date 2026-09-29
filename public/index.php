<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

/* Ambil username admin yang sedang login */
$admin_username = $_SESSION['admin_username'] ?? 'Admin';


/* =========================
   AMBIL DATA LAYANAN
   LANGSUNG DARI DATABASE
========================= */

$stmt_layanan = $conn->query("
    SELECT
        l.id_layanan,
        l.id_kategori,
        l.nama_layanan,
        l.url,
        l.logo,
        k.nama_kategori
    FROM layanan l
    LEFT JOIN kategori k
        ON l.id_kategori = k.id_kategori
    ORDER BY l.id_kategori ASC, l.id_layanan DESC
");

$layanan_terbaru = $stmt_layanan->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   HITUNG TOTAL LAYANAN
========================= */

$total_layanan = count($layanan_terbaru);


/* =========================
   AMBIL DATA KATEGORI
   DARI DATA API
========================= */

$data_kategori = [];

foreach ($layanan_terbaru as $item) {

    if (
        isset($item['id_kategori']) &&
        isset($item['nama_kategori'])
    ) {

        $id_kategori = $item['id_kategori'];

        if (!isset($data_kategori[$id_kategori])) {

            $data_kategori[$id_kategori] = [
                'id_kategori' => $item['id_kategori'],
                'nama_kategori' => $item['nama_kategori']
            ];

        }

    }

}


/* Ubah menjadi array biasa */

$data_kategori = array_values($data_kategori);


/* Urutkan berdasarkan ID kategori */

usort($data_kategori, function ($a, $b) {

    return $a['id_kategori'] <=> $b['id_kategori'];

});


/* Total kategori */

$total_kategori = count($data_kategori);


/* =========================
   AMBIL FILTER KATEGORI
========================= */

$filter_kategori = isset($_GET['kategori'])
    ? $_GET['kategori']
    : '';

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Dashboard Admin BPS Solok Selatan</title>

<style>

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background: #f4f8fc;
        color: #1f2937;
        overflow-x: hidden;
    }


    /* =========================
       SIDEBAR
    ========================= */

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
        color: #e8f3fb;
        text-decoration: none;
        padding: 14px 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .menu a:hover {
        background: #00589c;
        color: white;
    }

    .menu .active {
        background: #ffffff;
        color: #0066b3;
        font-weight: bold;
    }


    /* =========================
       TOMBOL MENU MOBILE
    ========================= */

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
    }


    /* =========================
       OVERLAY MOBILE
    ========================= */

    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        z-index: 9998;
    }


    /* =========================
       MAIN
    ========================= */

    .main {
        margin-left: 240px;
        padding: 30px;
        position: relative;
        z-index: 1;
        min-width: 0;
    }

    .header {
        margin-bottom: 30px;
    }

    .header-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 8px;
    }

    .header h1 {
        font-size: 28px;
        color: #005a9c;
    }

    .header p {
        color: #6b7280;
    }


    /* =========================
       ADMIN DROPDOWN
    ========================= */

    .admin-dropdown {
        position: relative;
    }

    .admin-status {
        display: flex;
        align-items: center;
        gap: 10px;
        background: white;
        padding: 10px 15px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);
        border: 1px solid #e1edf7;
        white-space: nowrap;
        cursor: pointer;
        font-family: inherit;
    }

    .admin-status:hover {
        background: #f8fbfe;
    }

    .admin-icon {
        width: 36px;
        height: 36px;
        background: #eaf4fb;
        color: #0066b3;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .admin-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
        text-align: left;
    }

    .admin-label {
        font-size: 12px;
        color: #6b7280;
    }

    .admin-name {
        font-size: 14px;
        font-weight: bold;
        color: #005a9c;
    }

    .dropdown-arrow {
        font-size: 18px;
        color: #005a9c;
        margin-left: 5px;
    }


    /* =========================
       MENU LOGOUT
    ========================= */

    .admin-menu {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        width: 160px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 18px rgba(0, 0, 0, 0.15);
        border: 1px solid #e1edf7;
        padding: 8px;
        z-index: 10000;
    }

    .admin-menu.show {
        display: block;
    }

    .logout-button {
        display: block;
        width: 100%;
        padding: 11px 12px;
        color: #c62828;
        text-decoration: none;
        border-radius: 7px;
        font-size: 14px;
        font-weight: 600;
    }

    .logout-button:hover {
        background: #fff1f1;
    }


    /* =========================
       CARDS
    ========================= */

    .cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        border-left: 5px solid #0066b3;
        box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);
    }

    .card h3 {
        color: #6b7280;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .number {
        font-size: 30px;
        font-weight: bold;
        color: #0066b3;
    }


    /* =========================
       TABLE CARD
    ========================= */

    .table-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);
        overflow: hidden;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 20px;
    }

    .table-card h2 {
        margin: 0;
        color: #1f2937;
    }


    /* =========================
       FILTER
    ========================= */

    .filter-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-container label {
        font-size: 14px;
        color: #6b7280;
        font-weight: 600;
    }

    .filter-select {
        padding: 9px 12px;
        border: 1px solid #b9d4e8;
        border-radius: 8px;
        background: white;
        font-size: 14px;
        color: #374151;
        cursor: pointer;
        min-width: 180px;
        outline: none;
    }

    .filter-select:focus {
        border-color: #0066b3;
        box-shadow: 0 0 0 2px rgba(0, 102, 179, 0.1);
    }

    .reset-filter {
        text-decoration: none;
        font-size: 13px;
        color: #0066b3;
        padding: 8px 10px;
        border-radius: 7px;
        background: #eaf4fb;
    }

    .reset-filter:hover {
        background: #dceef9;
    }


    /* =========================
       TABLE
    ========================= */

    .table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 650px;
    }

    th {
        text-align: left;
        padding: 16px 15px;
        background: #eaf4fb;
        color: #005a9c;
        font-size: 18px;
        font-weight: bold;
        white-space: nowrap;
    }

    td {
        padding: 12px 15px;
        border-bottom: 1px solid #e5e7eb;
    }

    tr:hover td {
        background: #f7fbfe;
    }


    /* =========================
       LINK ICON
    ========================= */

    .link-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        background: #eaf4fb;
        color: #0066b3;
        border: 1px solid #b9d4e8;
        border-radius: 8px;
        text-decoration: none;
        font-size: 20px;
        transition: 0.2s ease;
    }

    .link-icon:hover {
        background: #0066b3;
        color: white;
        border-color: #0066b3;
        transform: translateY(-1px);
    }

    .link-disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .empty {
        text-align: center;
        color: #777;
        padding: 25px;
    }


    /* =====================================================
       RESPONSIVE MOBILE
       HANYA BERLAKU UNTUK LAYAR MAKSIMAL 768PX
    ===================================================== */

    @media (max-width: 768px) {

        body {
            overflow-x: hidden;
        }


        /* SIDEBAR DISSEMBUNYIKAN */

        .sidebar {
            width: 250px;
            height: 100vh;
            transform: translateX(-100%);
            padding: 25px 15px;
        }

        .sidebar.mobile-open {
            transform: translateX(0);
        }


        /* TOMBOL MENU MUNCUL */

        .mobile-menu-button {
            display: flex;
            align-items: center;
            justify-content: center;
        }


        /* OVERLAY */

        .sidebar-overlay.show {
            display: block;
        }


        /* MAIN FULL WIDTH */

        .main {
            margin-left: 0;
            padding: 75px 15px 25px;
            width: 100%;
        }


        /* HEADER */

        .header {
            margin-bottom: 20px;
        }

        .header-top {
            flex-direction: column;
            gap: 15px;
        }

        .header h1 {
            font-size: 24px;
        }

        .header p {
            font-size: 14px;
            line-height: 1.5;
            margin-top: 5px;
        }


        /* ADMIN */

        .admin-dropdown {
            width: 100%;
        }

        .admin-status {
            width: 100%;
            justify-content: flex-start;
        }


        /* CARDS */

        .cards {
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .card {
            padding: 20px;
        }

        .number {
            font-size: 28px;
        }


        /* TABLE CARD */

        .table-card {
            padding: 18px 15px;
        }


        /* HEADER TABLE */

        .table-header {
            flex-direction: column;
            align-items: stretch;
            gap: 15px;
        }

        .table-card h2 {
            font-size: 20px;
        }


        /* FILTER */

        .filter-container {
            width: 100%;
            flex-wrap: wrap;
            align-items: stretch;
        }

        .filter-container label {
            width: 100%;
        }

        .filter-select {
            width: 100%;
            min-width: 0;
            padding: 11px 12px;
        }

        .reset-filter {
            text-align: center;
            padding: 10px;
        }


        /* TABLE BISA DIGESER */

        .table-wrapper {
            overflow-x: auto;
            width: 100%;
        }

        table {
            min-width: 650px;
        }

        th {
            font-size: 15px;
            padding: 12px 10px;
        }

        td {
            font-size: 14px;
            padding: 11px 10px;
        }

    }


    /* =========================
       HP SANGAT KECIL
    ========================= */

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

        .admin-status {
            padding: 9px 12px;
        }

        .admin-icon {
            width: 34px;
            height: 34px;
            font-size: 16px;
        }

        .admin-name {
            font-size: 13px;
        }

        .card {
            padding: 18px;
        }

        .table-card {
            padding: 16px 12px;
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
            <a href="./index.php" class="active">
                Dashboard
            </a>
        </li>

        <li>
            <a href="./layanan.php">
                Kelola Layanan
            </a>
        </li>

        <li>
            <a href="./kategori.php">
                Kategori
            </a>
        </li>

        <li>
            <a href="./kelola_admin.php">
                Kelola Admin
            </a>
        </li>

        <li>
            <a href="./pengaturan.php">
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

        <div class="header-top">

            <div>

                <h1>Dashboard Admin</h1>

                <p>
                    Selamat datang di Dashboard Admin BPS Kabupaten Solok Selatan.
                </p>

            </div>


            <div class="admin-dropdown">

                <button
                    type="button"
                    class="admin-status"
                    onclick="toggleAdminMenu()"
                >

                    <div class="admin-icon">
                        👤
                    </div>

                    <div class="admin-info">

                        <span class="admin-label">
                            Login sebagai
                        </span>

                        <span class="admin-name">
                            <?= htmlspecialchars($admin_username); ?>
                        </span>

                    </div>

                    <span class="dropdown-arrow">
                        ▾
                    </span>

                </button>


                <div
                    class="admin-menu"
                    id="adminMenu"
                >

                    <a
                        href="logout.php"
                        class="logout-button"
                    >
                        🚪 Logout
                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         CARDS
    ===================================================== -->

    <div class="cards">

        <div class="card">

            <h3>Total Layanan</h3>

            <div class="number">
                <?= $total_layanan; ?>
            </div>

        </div>


        <div class="card">

            <h3>Total Kategori</h3>

            <div class="number">
                <?= $total_kategori; ?>
            </div>

        </div>

    </div>


    <!-- =====================================================
         TABLE
    ===================================================== -->

    <div class="table-card">

        <div class="table-header">

            <h2>Recently Added</h2>

            <div class="filter-container">

                <label for="kategori">
                    Filter Kategori:
                </label>

                <select
                    id="kategori"
                    class="filter-select"
                    onchange="filterKategori(this.value)"
                >

                    <option value="">
                        Semua Kategori
                    </option>

                    <?php foreach ($data_kategori as $kategori): ?>

                        <option
                            value="<?= $kategori['id_kategori']; ?>"
                            <?= ($filter_kategori == $kategori['id_kategori']) ? 'selected' : ''; ?>
                        >

                            <?= htmlspecialchars($kategori['nama_kategori']); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <?php if ($filter_kategori != ''): ?>

                    <a
                        href="index.php"
                        class="reset-filter"
                    >
                        Reset
                    </a>

                <?php endif; ?>

            </div>

        </div>


        <?php

        $jumlah_tampil = 0;

        foreach ($layanan_terbaru as $item):

            if (
                $filter_kategori != '' &&
                $item['id_kategori'] != $filter_kategori
            ) {
                continue;
            }

            $jumlah_tampil++;

        endforeach;

        ?>


        <?php if ($jumlah_tampil == 0): ?>

            <div class="empty">
                Tidak ada layanan pada kategori yang dipilih.
            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>
                            <th>No</th>
                            <th>Nama Layanan</th>
                            <th>Kategori</th>
                            <th>Link</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php $no = 1; ?>

                    <?php foreach ($layanan_terbaru as $item): ?>

                        <?php

                        if (
                            $filter_kategori != '' &&
                            $item['id_kategori'] != $filter_kategori
                        ) {
                            continue;
                        }

                        ?>

                        <tr>

                            <td>
                                <?= $no++; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($item['nama_layanan']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($item['nama_kategori'] ?? '-'); ?>
                            </td>

                            <td>

                                <?php if (!empty(trim($item['url']))): ?>

                                    <a
                                        href="<?= htmlspecialchars(
                                            trim($item['url']),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="link-icon"
                                        title="Buka Website"
                                    >
                                        🔗
                                    </a>

                                <?php else: ?>

                                    <span
                                        class="link-icon link-disabled"
                                        title="URL belum tersedia"
                                    >
                                        🔗
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</main>


<script>

/* =====================================================
   SIDEBAR MOBILE
===================================================== */

function toggleMobileSidebar() {

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const button = document.getElementById('mobileMenuButton');

    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('show');

    if (sidebar.classList.contains('mobile-open')) {

        button.innerHTML = '✕';
        button.setAttribute('aria-label', 'Tutup menu');

    } else {

        button.innerHTML = '☰';
        button.setAttribute('aria-label', 'Buka menu');

    }

}


/* =====================================================
   TUTUP SIDEBAR MOBILE
===================================================== */

function closeMobileSidebar() {

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const button = document.getElementById('mobileMenuButton');

    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('show');

    button.innerHTML = '☰';
    button.setAttribute('aria-label', 'Buka menu');

}


/* =====================================================
   TUTUP SIDEBAR SETELAH PILIH MENU DI HP
===================================================== */

document.querySelectorAll('.menu a').forEach(function(link) {

    link.addEventListener('click', function() {

        if (window.innerWidth <= 768) {
            closeMobileSidebar();
        }

    });

});


/* =====================================================
   ADMIN DROPDOWN
===================================================== */

function toggleAdminMenu() {

    const menu = document.getElementById('adminMenu');

    menu.classList.toggle('show');

}


/* =====================================================
   TUTUP DROPDOWN JIKA KLIK DI LUAR
===================================================== */

document.addEventListener('click', function(event) {

    const dropdown = document.querySelector('.admin-dropdown');

    const menu = document.getElementById('adminMenu');

    if (!dropdown.contains(event.target)) {

        menu.classList.remove('show');

    }

});


/* =====================================================
   FILTER KATEGORI
===================================================== */

function filterKategori(idKategori) {

    if (idKategori === '') {

        window.location.href = 'index.php';

    } else {

        window.location.href =
            'index.php?kategori=' + idKategori;

    }

}

</script>

</body>

</html>