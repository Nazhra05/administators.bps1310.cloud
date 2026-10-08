<?php

require_once '../config/config.php';

/* =========================
   CEK LOGIN ADMIN
========================= */

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? '';


/* =========================
   CEK ROLE DASHBOARD
========================= */

if (
    $admin_role !== 'ADMIN IT' &&
    $admin_role !== 'ADMIN DASHBOARD'
) {
    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}


/* =========================
   AMBIL DATA LAYANAN
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

$total_layanan = count($layanan_terbaru);

$filter_kategori = $_GET['kategori'] ?? '';


/* =========================
   AMBIL DATA KATEGORI
========================= */

$stmt_kategori = $conn->query("
    SELECT
        k.id_kategori,
        k.nama_kategori,
        COUNT(l.id_layanan) AS jumlah_layanan
    FROM kategori k
    LEFT JOIN layanan l
        ON k.id_kategori = l.id_kategori
    GROUP BY
        k.id_kategori,
        k.nama_kategori
    ORDER BY k.id_kategori ASC
");

$data_kategori = $stmt_kategori->fetchAll(PDO::FETCH_ASSOC);

$total_kategori = count($data_kategori);


/* =========================
   DESKRIPSI KATEGORI
========================= */

$deskripsi_kategori = [

    'Distribusi' =>
        'Perdagangan, harga, transportasi, pariwisata, distribusi barang/jasa.',

    'Produksi' =>
        'Pertanian, industri, pertambangan, energi, konstruksi, peternakan, kehutanan, perikanan.',

    'Neraca' =>
        'PDRB, neraca wilayah, neraca produksi/pengeluaran, analisis ekonomi.',

    'Sosial' =>
        'Penduduk, ketenagakerjaan, pendidikan, kesehatan, kemiskinan, dan sosial.',

    'TI' =>
        'Teknologi informasi, sistem, aplikasi, dan layanan digital statistik.',

    'Umum' =>
        'Informasi umum, administrasi, layanan internal, dan kebutuhan pendukung.',

    'Diseminasi' =>
        'Layanan dan media untuk menyebarkan data statistik kepada pengguna.'
];


/* =========================
   WARNA KATEGORI
========================= */

$kelas_warna = [
    'blue',
    'orange',
    'green',
    'purple',
    'teal',
    'gray',
    'pink'
];


/* =========================
   KELOMPOKKAN LAYANAN
========================= */

$layanan_per_kategori = [];

foreach ($data_kategori as $kategori) {

    $layanan_per_kategori[
        (int) $kategori['id_kategori']
    ] = [];

}

foreach ($layanan_terbaru as $item) {

    $id_kategori = (int) $item['id_kategori'];

    if (!isset($layanan_per_kategori[$id_kategori])) {

        $layanan_per_kategori[$id_kategori] = [];

    }

    $layanan_per_kategori[$id_kategori][] = $item;

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

<title>Dashboard Admin BPS Solok Selatan</title>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


html {
    scroll-behavior: smooth;
}


body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f4f8fc;
    color: #1f2937;
    overflow-x: hidden;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {
    width: 240px;
    height: 100vh;

    position: fixed;
    left: 0;
    top: 0;

    background: #0066b3;
    color: white;

    padding: 25px 15px;

    z-index: 9999;

    box-shadow:
        2px 0 10px rgba(0, 0, 0, 0.08);

    transform: translateX(-100%);

    transition:
        transform 0.3s ease;
}


.sidebar.sidebar-open {
    transform: translateX(0);
}


/* =====================================================
   LOGO SIDEBAR
===================================================== */

.logo {
    font-size: 20px;
    font-weight: bold;

    margin-bottom: 40px;

    padding: 0 15px;
}


/* =====================================================
   MENU
===================================================== */

.menu {
    list-style: none;
}


.menu li {
    margin-bottom: 8px;
}


.menu a {
    display: block;

    width: 100%;

    color: #e8f3fb;

    text-decoration: none;

    padding: 12px 15px;

    border-radius: 8px;

    transition: 0.2s ease;

    font-family: inherit;

    font-size: 16px;

    font-weight: 400;
}


.menu a:hover {
    background: #00589c;

    color: white;
}


.menu a.active {
    background: white;

    color: #0066b3;

    font-weight: bold;
}


/* =====================================================
   TOMBOL ☰
===================================================== */

.mobile-menu-button {
    display: flex;

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

    align-items: center;
    justify-content: center;

    box-shadow:
        0 3px 10px rgba(0, 0, 0, 0.15);

    transition: 0.2s ease;
}


.mobile-menu-button:hover {
    background: #00589c;

    transform: scale(1.03);
}


/* =====================================================
   OVERLAY
===================================================== */

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
    margin-left: 0;

    padding: 30px;

    min-width: 0;

    transition:
        margin-left 0.3s ease;
}


.main.sidebar-open {
    margin-left: 240px;
}


/* =====================================================
   HEADER
===================================================== */

.header {
    margin-bottom: 25px;
}


.header-top {
    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;
}


.header-left {
    padding-left: 55px;
}


.header h1 {
    font-size: 28px;

    line-height: 1.25;

    font-weight: 700;

    color: #005a9c;

    margin-bottom: 6px;
}


.header p {
    color: #6b7280;

    margin-top: 0;

    font-size: 15px;

    line-height: 1.5;
}


/* =====================================================
   ADMIN DROPDOWN
===================================================== */

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

    border: 1px solid #e1edf7;

    box-shadow:
        0 2px 10px rgba(0, 102, 179, 0.08);

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
    color: #005a9c;

    font-size: 18px;

    margin-left: 3px;
}


.admin-menu {
    display: none;

    position: absolute;

    right: 0;

    top: calc(100% + 8px);

    width: 160px;

    background: white;

    border-radius: 10px;

    box-shadow:
        0 5px 18px rgba(0, 0, 0, 0.15);

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


/* =====================================================
   TOTAL CARDS
===================================================== */

.cards {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 20px;

    margin-bottom: 30px;
}


.card {
    background: white;

    padding: 22px 23px;

    border-radius: 12px;

    border-left: 5px solid #0066b3;

    box-shadow:
        0 2px 10px rgba(0, 102, 179, 0.08);

    transition: 0.2s ease;
}


.card:hover {
    transform: translateY(-2px);

    box-shadow:
        0 6px 18px rgba(0, 102, 179, 0.10);
}


.card h3 {
    color: #6b7280;

    font-size: 14px;

    font-weight: 600;

    margin-bottom: 7px;
}


.number {
    font-size: 30px;

    font-weight: 700;

    color: #0066b3;
}


/* =====================================================
   SECTION
===================================================== */

.section-heading {
    display: flex;

    justify-content: space-between;

    align-items: end;

    gap: 20px;

    margin: 5px 0 20px;
}


.section-heading h2 {
    font-size: 20px;

    line-height: 1.35;

    font-weight: 700;

    color: #1f2937;
}


.section-heading p {
    font-size: 13px;

    color: #6b7280;

    margin-top: 4px;
}


/* =====================================================
   CATEGORY GRID
===================================================== */

.category-grid {
    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 24px;

    margin-bottom: 30px;
}


.category-card {
    background: white;

    border: 1px solid #dce8f2;

    border-radius: 22px;

    padding: 17px;

    box-shadow:
        0 3px 14px rgba(0, 76, 135, 0.07);

    transition: 0.2s ease;

    min-width: 0;
}


.category-card:hover {
    box-shadow:
        0 5px 20px rgba(0, 76, 135, 0.10);
}


.category-inner {
    border: 1px solid #dce8f2;

    border-radius: 18px;

    padding: 30px 30px 28px;

    min-height: 430px;

    background: #f7fbff;
}


.category-card.orange .category-inner {
    background: #fff8f0;

    border-color: #f8dfc8;
}


.category-card.green .category-inner {
    background: #f7fce9;

    border-color: #dff0b9;
}


.category-card.purple .category-inner {
    background: #f8f5ff;

    border-color: #e5dbff;
}


.category-card.teal .category-inner {
    background: #f1fbfa;

    border-color: #d3efeb;
}


.category-card.gray .category-inner {
    background: #f7f8fa;

    border-color: #e5e7eb;
}


.category-card.pink .category-inner {
    background: #fff6fa;

    border-color: #f6dce8;
}


/* =====================================================
   CATEGORY TOP
===================================================== */

.category-top {
    display: flex;

    align-items: center;

    gap: 14px;

    margin-bottom: 20px;
}


.category-dot {
    width: 28px;
    height: 28px;

    border-radius: 50%;

    background: #183b7a;

    flex-shrink: 0;
}


.orange .category-dot {
    background: #f58a4c;
}


.green .category-dot {
    background: #9be84c;
}


.purple .category-dot {
    background: #8f70e8;
}


.teal .category-dot {
    background: #29aaa1;
}


.gray .category-dot {
    background: #8290a5;
}


.pink .category-dot {
    background: #e875a4;
}


.category-name {
    font-size: 29px;

    line-height: 1.2;

    font-weight: 700;

    color: #17233e;

    margin-bottom: 10px;
}


.category-name.blue {
    color: #0066b3;
}


.category-focus {
    font-size: 14px;

    line-height: 1.6;

    color: #657894;

    min-height: 48px;
}


.category-count {
    font-size: 14px;

    color: #91a4c0;

    margin-top: 15px;
}


.popular-label {
    font-size: 13px;

    line-height: 1.3;

    letter-spacing: 1.2px;

    font-weight: 700;

    color: #91a4c0;

    margin: 20px 2px 10px;

    text-transform: uppercase;
}


.popular-list {
    border-top:
        1px solid #dfe7ef;
}


.popular-item {
    display: flex;

    align-items: center;

    gap: 18px;

    padding: 16px 4px;

    border-bottom:
        1px solid #dfe7ef;

    min-width: 0;
}


.service-icon {
    width: 58px;
    height: 58px;

    border-radius: 15px;

    background: #f9fbfd;

    border: 1px solid #dce7f0;

    display: flex;

    align-items: center;
    justify-content: center;

    color: #8da0ba;

    font-size: 24px;

    flex: 0 0 auto;
}


.service-name {
    flex: 1;

    min-width: 0;

    font-size: 16px;

    line-height: 1.4;

    color: #304b72;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


/* =====================================================
   BUTTON LIHAT SEMUA
===================================================== */

.view-all {
    display: flex;

    align-items: center;

    justify-content: center;

    gap: 12px;

    width: 100%;

    min-height: 58px;

    margin-top: 20px;

    border: 1px solid #b9cde1;

    border-radius: 16px;

    color: #243f7d;

    background: white;

    font-size: 16px;

    font-weight: 700;

    text-decoration: none;

    cursor: pointer;

    transition: 0.2s ease;

    font-family: inherit;
}


.view-all:hover {
    background: #f4f8fc;

    border-color: #243f7d;
}


/* =====================================================
   MODAL
===================================================== */

.modal-overlay {
    display: none;

    position: fixed;

    inset: 0;

    background:
        rgba(24, 35, 56, 0.62);

    backdrop-filter: blur(2px);

    z-index: 20000;

    padding: 34px;
}


.modal-overlay.show {
    display: flex;

    align-items: center;

    justify-content: center;
}


.modal {
    width: min(100%, 1180px);

    max-height:
        calc(100vh - 68px);

    background: white;

    border-radius: 28px;

    overflow: hidden;

    box-shadow:
        0 25px 70px rgba(0, 0, 0, 0.25);

    display: flex;

    flex-direction: column;
}


.modal-header {
    padding: 32px 40px 21px;

    border-bottom:
        1px solid #e7edf4;

    position: relative;
}


.modal-title-row {
    display: flex;

    align-items: center;

    gap: 11px;

    margin-bottom: 16px;
}


.modal-title-dot {
    width: 14px;
    height: 14px;

    border-radius: 50%;

    background: #183b7a;
}


.modal-title {
    font-size: 21px;

    line-height: 1.3;

    letter-spacing: 0.8px;

    color: #91a2ba;

    font-weight: 700;

    text-transform: uppercase;
}


.modal-focus {
    font-size: 18px;

    line-height: 1.6;

    color: #657894;

    padding-right: 50px;
}


.modal-close {
    position: absolute;

    right: 28px;

    top: 26px;

    border: none;

    background: transparent;

    color: #91a2ba;

    font-size: 31px;

    line-height: 1;

    cursor: pointer;

    font-family: inherit;
}


.modal-list {
    overflow-y: auto;

    padding: 0 40px;
}


.modal-item {
    display: flex;

    align-items: center;

    gap: 18px;

    min-height: 108px;

    border-bottom:
        1px solid #e7edf4;
}


.modal-item .service-icon {
    width: 54px;
    height: 54px;

    font-size: 23px;
}


.modal-item-main {
    min-width: 0;

    flex: 1;
}


.modal-item-name {
    font-size: 20px;

    line-height: 1.4;

    font-weight: 700;

    color: #17233c;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.modal-item-category {
    font-size: 15px;

    color: #93a5bd;

    margin-left: 7px;
}


.modal-open {
    display: flex;

    align-items: center;

    gap: 25px;

    color: #657894;

    text-decoration: none;

    font-size: 14px;

    font-weight: 600;

    white-space: nowrap;
}


.modal-open:hover {
    color: #183b7a;
}


.modal-arrow {
    font-size: 23px;

    color: #c2cfdd;
}


.modal-empty {
    text-align: center;

    padding: 50px;

    color: #7b8da8;

    font-size: 14px;
}


.modal-footer {
    padding: 15px 40px;

    color: #93a5bd;

    font-size: 12px;

    background: #fbfcfe;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

    .category-grid {
        grid-template-columns: 1fr;
    }

}


@media (max-width: 768px) {

    .sidebar {
        width: 250px;
    }


    .main {
        margin-left: 0;

        padding:
            75px 15px 25px;
    }


    .main.sidebar-open {
        margin-left: 0;
    }


    .header-top {
        flex-direction: column;

        gap: 15px;
    }


    .header-left {
        padding-left: 55px;
    }


    .header h1 {
        font-size: 23px;
    }


    .header p {
        font-size: 13px;
    }


    .admin-dropdown,
    .admin-status {
        width: 100%;
    }


    .admin-status {
        justify-content: flex-start;
    }


    .cards {
        grid-template-columns: 1fr;

        gap: 14px;
    }


    .category-grid {
        grid-template-columns: 1fr;

        gap: 18px;
    }


    .category-inner {
        min-height: auto;

        padding: 24px 20px;
    }


    .category-name {
        font-size: 25px;
    }


    .popular-item {
        gap: 12px;
    }


    .service-icon {
        width: 48px;
        height: 48px;

        border-radius: 12px;

        font-size: 20px;
    }


    .service-name {
        font-size: 14px;
    }


    .view-all {
        min-height: 52px;

        font-size: 14px;
    }


    .modal-overlay {
        padding: 12px;
    }


    .modal {
        max-height:
            calc(100vh - 24px);

        border-radius: 20px;
    }


    .modal-header {
        padding:
            24px 21px 17px;
    }


    .modal-focus {
        font-size: 15px;

        padding-right: 25px;
    }


    .modal-title {
        font-size: 17px;
    }


    .modal-list {
        padding: 0 21px;
    }


    .modal-item {
        min-height: 86px;

        gap: 11px;
    }


    .modal-item .service-icon {
        width: 43px;
        height: 43px;

        font-size: 18px;
    }


    .modal-item-name {
        font-size: 15px;
    }


    .modal-item-category {
        display: block;

        margin: 3px 0 0;

        font-size: 12px;
    }


    .modal-open {
        gap: 7px;

        font-size: 13px;
    }


    .modal-footer {
        padding:
            11px 21px;
    }

}


@media (max-width: 480px) {

    .main {
        padding-left: 12px;

        padding-right: 12px;
    }


    .header h1 {
        font-size: 21px;
    }


    .cards {
        margin-bottom: 22px;
    }


    .category-card {
        padding: 10px;

        border-radius: 17px;
    }


    .category-inner {
        padding:
            22px 16px;

        border-radius: 14px;
    }


    .category-name {
        font-size: 23px;
    }


    .category-focus {
        font-size: 13px;
    }


    .category-dot {
        width: 23px;
        height: 23px;
    }


    .popular-item {
        gap: 12px;
    }


    .service-icon {
        width: 48px;
        height: 48px;
    }


    .service-name {
        font-size: 14px;
    }


    .modal-close {
        right: 17px;

        top: 18px;

        font-size: 28px;
    }

}

.table-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);
    overflow: hidden;
    margin-top: 30px;
}
.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 20px;
}
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
}
.filter-select:focus {
    border-color: #0066b3;
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 102, 179, 0.1);
}
.reset-filter {
    padding: 9px 14px;
    background: #eef2f7;
    color: #4b5563;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}
.reset-filter:hover {
    background: #e2e8f0;
}
.table-wrapper {
    width: 100%;
    overflow-x: auto;
}
.table-card table {
    width: 100%;
    border-collapse: collapse;
}
.table-card thead th {
    background: #f8fafc;
    padding: 12px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
}
.table-card tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
    color: #334155;
}
.table-card tbody tr:hover {
    background: #f8fafc;
}
.link-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 6px;
    background: #f0f7ff;
    color: #0066b3;
    text-decoration: none;
    font-size: 15px;
    border: 1px solid #d0e3f7;
}
.link-icon:hover {
    background: #0066b3;
    color: white;
}
.link-disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.empty {
    text-align: center;
    color: #64748b;
    padding: 30px;
    font-size: 14px;
}

</style>

</head>


<body>


<!-- =====================================================
     TOMBOL ☰
===================================================== -->

<button
    type="button"
    class="mobile-menu-button"
    id="mobileMenuButton"
    onclick="openSidebar()"
    aria-label="Buka menu"
>
    ☰
</button>


<!-- =====================================================
     OVERLAY HP
===================================================== -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside
    class="sidebar"
    id="sidebar"
>

    <div class="logo">
        Dashboard Admin
    </div>


    <ul class="menu">

        <li>
            <a
                href="./index.php"
                class="active"
            >
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


        <!--
            KELOLA ADMIN DAN KELOLA USER
            HANYA UNTUK ADMIN IT
        -->

        <?php if ($admin_role === 'ADMIN IT'): ?>

            <li>
                <a href="./kelola_admin.php">
                    Kelola Admin
                </a>
            </li>


            <li>
                <a href="./kelola_user.php">
                    Kelola User
                </a>
            </li>

        <?php endif; ?>


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

<main
    class="main"
    id="mainContent"
>


    <div class="header">


        <div class="header-top">


            <div class="header-left">

                <h1>
                    Dashboard Admin
                </h1>


                <p>
                    Selamat datang di Dashboard Admin BPS Kabupaten Solok Selatan.
                </p>

            </div>


            <!-- ADMIN -->

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


    <!-- =================================================
         TOTAL CARD
    ================================================== -->

    <div class="cards">


        <div class="card">

            <h3>
                Total Layanan
            </h3>

            <div class="number">
                <?= $total_layanan; ?>
            </div>

        </div>


        <div class="card">

            <h3>
                Total Kategori
            </h3>

            <div class="number">
                <?= $total_kategori; ?>
            </div>

        </div>


    </div>


    <!-- =================================================
         JUDUL KATEGORI
    ================================================== -->

    <div class="section-heading">


        <div>

            <h2>
                Layanan Berdasarkan Kategori
            </h2>

            <p>
                Jelajahi layanan BPS Kabupaten Solok Selatan berdasarkan bidangnya.
            </p>

        </div>


    </div>


    <!-- =================================================
         CATEGORY GRID
    ================================================== -->

    <div class="category-grid">


        <?php foreach ($data_kategori as $index => $kategori): ?>


            <?php

            $id =
                (int) $kategori['id_kategori'];

            $nama =
                $kategori['nama_kategori'];

            $items =
                $layanan_per_kategori[$id]
                ?? [];

            $kelas =
                $kelas_warna[
                    $index %
                    count($kelas_warna)
                ];

            $deskripsi =
                $deskripsi_kategori[$nama]
                ??
                'Kumpulan layanan statistik dan informasi BPS berdasarkan kategori ini.';

            ?>


            <article
                class="
                    category-card
                    <?= htmlspecialchars($kelas); ?>
                "
            >


                <div class="category-inner">


                    <div class="category-top">

                        <span
                            class="category-dot"
                        ></span>

                    </div>


                    <div
                        class="
                            category-name
                            <?= htmlspecialchars($kelas); ?>
                        "
                    >

                        <?= htmlspecialchars($nama); ?>

                    </div>


                    <div class="category-focus">

                        Fokus:
                        <?= htmlspecialchars($deskripsi); ?>

                    </div>


                    <div class="category-count">

                        <?= count($items); ?>

                        tautan tersedia

                    </div>


                    <div class="popular-label">

                        Tautan Populer

                    </div>


                    <div class="popular-list">


                        <?php if (!$items): ?>


                            <div class="popular-item">

                                <span class="service-name">
                                    Belum ada layanan.
                                </span>

                            </div>


                        <?php else: ?>


                            <?php
                            foreach (
                                array_slice(
                                    $items,
                                    0,
                                    3
                                )
                                as $item
                            ):
                            ?>


                                <div class="popular-item">


                                    <span class="service-icon">
                                        🌐
                                    </span>


                                    <span class="service-name">

                                        <?= htmlspecialchars(
                                            $item['nama_layanan']
                                        ); ?>

                                    </span>


                                </div>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </div>


                    <button
                        type="button"
                        class="view-all"
                        onclick="
                            openCategoryModal(
                                <?= $id; ?>
                            )
                        "
                    >

                        Lihat Semua

                        <span>
                            →
                        </span>

                    </button>


                </div>


            </article>


        <?php endforeach; ?>


    </div>


    <!-- =====================================================
         TABLE CARD
    ===================================================== -->

    <div class="table-card">

        <div class="table-header">

            <h2 style="margin: 0; color: #1f2937; font-size: 20px;">Recently Added</h2>

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


<!-- =====================================================
     MODAL SEMUA LAYANAN
===================================================== -->

<?php foreach ($data_kategori as $kategori): ?>


    <?php

    $id =
        (int) $kategori['id_kategori'];

    $nama =
        $kategori['nama_kategori'];

    $items =
        $layanan_per_kategori[$id]
        ?? [];

    $deskripsi =
        $deskripsi_kategori[$nama]
        ??
        'Kumpulan layanan statistik dan informasi BPS berdasarkan kategori ini.';

    ?>


    <div
        class="modal-overlay"
        id="categoryModal<?= $id; ?>"
        onclick="
            closeCategoryModal(
                event,
                <?= $id; ?>
            )
        "
    >


        <div
            class="modal"
            role="dialog"
            aria-modal="true"
            onclick="event.stopPropagation()"
        >


            <div class="modal-header">


                <div class="modal-title-row">


                    <span class="modal-title-dot">
                    </span>


                    <div class="modal-title">

                        <?= htmlspecialchars($nama); ?>

                    </div>


                </div>


                <div class="modal-focus">

                    Fokus:
                    <?= htmlspecialchars($deskripsi); ?>

                </div>


                <button
                    type="button"
                    class="modal-close"
                    onclick="
                        closeCategoryModal(
                            null,
                            <?= $id; ?>
                        )
                    "
                    aria-label="Tutup"
                >
                    ×
                </button>


            </div>


            <div class="modal-list">


                <?php if (!$items): ?>


                    <div class="modal-empty">

                        Belum ada layanan pada kategori ini.

                    </div>


                <?php else: ?>


                    <?php foreach ($items as $item): ?>


                        <div class="modal-item">


                            <span class="service-icon">
                                🌐
                            </span>


                            <div class="modal-item-main">


                                <div class="modal-item-name">

                                    <?= htmlspecialchars(
                                        $item['nama_layanan']
                                    ); ?>


                                    <span
                                        class="modal-item-category"
                                    >

                                        <?= htmlspecialchars(
                                            $nama
                                        ); ?>

                                    </span>


                                </div>


                            </div>


                            <?php if (
                                !empty(
                                    trim(
                                        $item['url']
                                    )
                                )
                            ): ?>


                                <a
                                    class="modal-open"
                                    href="<?= htmlspecialchars(
                                        trim($item['url']),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >

                                    Buka

                                    <span class="modal-arrow">
                                        →
                                    </span>

                                </a>


                            <?php else: ?>


                                <span class="modal-open">
                                    URL belum tersedia
                                </span>


                            <?php endif; ?>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


            <div class="modal-footer">

                <?= count($items); ?>

                layanan tersedia pada kategori

                <?= htmlspecialchars($nama); ?>.

            </div>


        </div>


    </div>


<?php endforeach; ?>


<script>

/* =====================================================
   BUKA SIDEBAR
===================================================== */

function openSidebar() {

    const sidebar =
        document.getElementById('sidebar');

    const main =
        document.getElementById('mainContent');

    const overlay =
        document.getElementById('sidebarOverlay');


    if (sidebar) {

        sidebar.classList.add('sidebar-open');

    }


    if (main) {

        main.classList.add('sidebar-open');

    }


    if (
        overlay &&
        window.innerWidth <= 768
    ) {

        overlay.classList.add('show');

    }

}


/* =====================================================
   TUTUP SIDEBAR
===================================================== */

function closeSidebar() {

    const sidebar =
        document.getElementById('sidebar');

    const main =
        document.getElementById('mainContent');

    const overlay =
        document.getElementById('sidebarOverlay');


    if (sidebar) {

        sidebar.classList.remove('sidebar-open');

    }


    if (main) {

        main.classList.remove('sidebar-open');

    }


    if (overlay) {

        overlay.classList.remove('show');

    }

}


/* =====================================================
   KLIK MENU
===================================================== */

document
    .querySelectorAll('.menu a')
    .forEach(function(link) {

        link.addEventListener(
            'click',
            function() {

                if (window.innerWidth <= 768) {

                    closeSidebar();

                }

            }
        );

    });


/* =====================================================
   OVERLAY HP
===================================================== */

const sidebarOverlay =
    document.getElementById(
        'sidebarOverlay'
    );


if (sidebarOverlay) {

    sidebarOverlay.addEventListener(
        'click',
        function() {

            closeSidebar();

        }
    );

}


/* =====================================================
   ADMIN DROPDOWN
===================================================== */

function toggleAdminMenu() {

    const menu =
        document.getElementById(
            'adminMenu'
        );


    if (!menu) {

        return;

    }


    menu.classList.toggle(
        'show'
    );

}


document.addEventListener(
    'click',
    function(event) {

        const dropdown =
            document.querySelector(
                '.admin-dropdown'
            );

        const menu =
            document.getElementById(
                'adminMenu'
            );


        if (
            dropdown &&
            menu &&
            !dropdown.contains(
                event.target
            )
        ) {

            menu.classList.remove(
                'show'
            );

        }

    }
);


/* =====================================================
   MODAL KATEGORI
===================================================== */

function openCategoryModal(id) {

    const modal =
        document.getElementById(
            'categoryModal' + id
        );


    if (modal) {

        modal.classList.add(
            'show'
        );


        document.body.style.overflow =
            'hidden';

    }

}


/* =====================================================
   TUTUP MODAL
===================================================== */

function closeCategoryModal(
    event,
    id
) {

    if (
        event &&
        event.target !==
        event.currentTarget
    ) {

        return;

    }


    const modal =
        document.getElementById(
            'categoryModal' + id
        );


    if (modal) {

        modal.classList.remove(
            'show'
        );


        document.body.style.overflow =
            '';

    }

}


/* =====================================================
   ESC UNTUK MODAL DAN SIDEBAR
===================================================== */

document.addEventListener(
    'keydown',
    function(event) {

        if (
            event.key === 'Escape'
        ) {

            document
                .querySelectorAll(
                    '.modal-overlay.show'
                )
                .forEach(
                    function(modal) {

                        modal.classList.remove(
                            'show'
                        );

                    }
                );


            closeSidebar();


            document.body.style.overflow =
                '';

        }

    }
);

function filterKategori(idKategori) {
    if (idKategori === '') {
        window.location.href = 'index.php';
    } else {
        window.location.href = 'index.php?kategori=' + idKategori;
    }
}

</script>


</body>

</html>