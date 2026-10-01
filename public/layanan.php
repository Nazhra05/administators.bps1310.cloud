<?php

require_once '../config/config.php';
require_once '../config/auth.php';


/* =====================================================
   CEK FILTER KATEGORI
===================================================== */

$selected_kategori = null;

if (
    isset($_GET['id_kategori']) &&
    is_numeric($_GET['id_kategori']) &&
    (int) $_GET['id_kategori'] > 0
) {

    $selected_kategori = (int) $_GET['id_kategori'];

}


/* =====================================================
   AMBIL DATA LAYANAN + KATEGORI
===================================================== */

$stmt = $conn->query("
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
    ORDER BY l.id_kategori ASC, l.id_layanan ASC
");

$data_layanan = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   AMBIL SEMUA KATEGORI
===================================================== */

$stmt_kategori = $conn->query("
    SELECT
        id_kategori,
        nama_kategori
    FROM kategori
    ORDER BY id_kategori ASC
");

$data_kategori = $stmt_kategori->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   GROUP LAYANAN BERDASARKAN KATEGORI
===================================================== */

$layanan_per_kategori = [];

foreach ($data_kategori as $kategori) {

    $layanan_per_kategori[$kategori['id_kategori']] = [
        'id_kategori'  => $kategori['id_kategori'],
        'nama_kategori' => $kategori['nama_kategori'],
        'layanan'      => []
    ];

}


foreach ($data_layanan as $layanan) {

    $id_kategori = $layanan['id_kategori'];

    if (isset($layanan_per_kategori[$id_kategori])) {

        $layanan_per_kategori[$id_kategori]['layanan'][] = $layanan;

    }

}


/* =====================================================
   CEK APAKAH KATEGORI YANG DIPILIH ADA
===================================================== */

$kategori_ditemukan = false;

if ($selected_kategori !== null) {

    foreach ($layanan_per_kategori as $kategori) {

        if (
            (int) $kategori['id_kategori'] ===
            $selected_kategori
        ) {

            $kategori_ditemukan = true;
            break;

        }

    }

} else {

    $kategori_ditemukan = true;

}


/* =====================================================
   ADMIN
===================================================== */

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Kelola Layanan - BPS Solok Selatan</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f8fc;
            color: #1f2937;
            overflow-x: hidden;
        }


        /* =====================================================
           SIDEBAR
           DEFAULT TERTUTUP
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

            transform: translateX(-100%);

            transition: transform 0.3s ease;
        }


        .sidebar.sidebar-open {
            transform: translateX(0);
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
        }


        .menu a {
            display: block;
            width: 100%;
            color: #e8f3fb;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            transition: 0.2s ease;
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
           SIDEBAR TOGGLE
           HANYA SATU TOMBOL ☰
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

            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);

            transition: 0.2s ease;
        }


        .mobile-menu-button:hover {
            background: #00589c;
            transform: scale(1.03);
        }


        /* =====================================================
           OVERLAY
           HANYA SEBAGAI LAPISAN VISUAL DI HP
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
           DEFAULT TANPA SIDEBAR
        ===================================================== */

        .main {
            margin-left: 0;
            padding: 30px;
            min-width: 0;

            transition: margin-left 0.3s ease;
        }


        .main.sidebar-open {
            margin-left: 240px;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            margin-bottom: 25px;

            gap: 20px;
        }


        .header-left {
            padding-left: 55px;
        }


        .header-left h1 {
            font-size: 28px;
            color: #005a9c;
        }


        .header-left p {
            color: #6b7280;
            margin-top: 6px;
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

            box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);

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


        /* =====================================================
           CONTROL BAR
        ===================================================== */

        .control-bar {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 25px;

            flex-wrap: wrap;
        }


        .control-info h2 {
            font-size: 20px;

            color: #1f2937;

            margin-bottom: 5px;
        }


        .control-info p {
            font-size: 13px;

            color: #6b7280;
        }


        .control-actions {
            display: flex;

            align-items: center;

            gap: 10px;
        }


        .filter-select {
            min-width: 190px;

            padding: 11px 14px;

            border: 1px solid #cbd9e5;

            border-radius: 8px;

            background: white;

            color: #374151;

            font-size: 13px;

            outline: none;

            cursor: pointer;
        }


        .filter-select:focus {
            border-color: #0066b3;
        }


        .add-button {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            padding: 11px 17px;

            border-radius: 8px;

            border: none;

            background: #0066b3;

            color: white;

            text-decoration: none;

            font-size: 13px;

            font-weight: 600;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .add-button:hover {
            background: #00589c;

            transform: translateY(-1px);
        }


        /* =====================================================
           CATEGORY GRID
        ===================================================== */

        .category-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 24px;
        }


        /* =====================================================
           CATEGORY CARD
        ===================================================== */

        .category-card {
            background: white;

            border: 1px solid #dce8f2;

            border-radius: 22px;

            padding: 17px;

            box-shadow:
                0 3px 14px rgba(0, 76, 135, 0.07);

            transition: 0.2s ease;
        }


        .category-card:hover {
            box-shadow:
                0 5px 20px rgba(0, 76, 135, 0.10);
        }


        .category-card.hidden {
            display: none;
        }


        /* =====================================================
           MODE FILTER KATEGORI
        ===================================================== */

        .category-card.full-view {
            grid-column: 1 / -1;
        }


        .category-inner {
            border: 1px solid #dce8f2;

            border-radius: 18px;

            padding: 30px 30px 28px;

            min-height: 430px;

            display: flex;

            flex-direction: column;
        }


        /* =====================================================
           CATEGORY HEADER
        ===================================================== */

        .category-heading {
            display: flex;

            align-items: center;

            gap: 14px;

            margin-bottom: 20px;
        }


        .category-dot {
            width: 28px;
            height: 28px;

            border-radius: 50%;

            background: #243f7d;

            flex-shrink: 0;
        }


        .category-name {
            font-size: 29px;

            font-weight: 700;

            color: #17233e;

            line-height: 1.2;
        }


        .category-count {
            font-size: 14px;

            color: #91a4c0;

            margin-bottom: 23px;
        }


        .category-count strong {
            color: #7085a5;
        }


        /* =====================================================
           SERVICE TITLE
        ===================================================== */

        .service-title {
            font-size: 13px;

            font-weight: 700;

            letter-spacing: 1.2px;

            color: #91a4c0;

            text-transform: uppercase;

            margin-bottom: 10px;
        }


        .service-line {
            height: 1px;

            background: #dfe7ef;
        }


        /* =====================================================
           SERVICE LIST
        ===================================================== */

        .service-list {
            flex: 1;
        }


        /* =====================================================
           SERVICE LIST MODE FILTER
           3 KOLOM
        ===================================================== */

        .service-list-all {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 0 20px;

            align-items: start;
        }


        .service-list-all .service-item {
            min-width: 0;
        }


        .service-item {
            display: flex;

            align-items: center;

            gap: 18px;

            padding: 16px 4px;

            border-bottom: 1px solid #dfe7ef;
        }


        /* =====================================================
           LOGO SERVICE
        ===================================================== */

        .service-logo {
            width: 58px;
            height: 58px;

            flex-shrink: 0;

            border: 1px solid #dce7f0;

            border-radius: 15px;

            background: #f9fbfd;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;
        }


        .service-logo img {
            width: 40px;
            height: 40px;

            object-fit: contain;
        }


        .service-logo.no-image {
            font-size: 24px;

            color: #62b9ec;
        }


        /* =====================================================
           SERVICE NAME
        ===================================================== */

        .service-name {
            flex: 1;

            min-width: 0;
        }


        .service-name a {
            display: block;

            color: #304b72;

            text-decoration: none;

            font-size: 16px;

            line-height: 1.4;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .service-name a:hover {
            color: #0066b3;
        }


        /* =====================================================
           ACTION BUTTON
        ===================================================== */

        .service-actions {
            display: flex;

            gap: 6px;

            flex-shrink: 0;
        }


        .action-btn {
            width: 31px;
            height: 31px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            text-decoration: none;

            font-size: 13px;

            transition: 0.2s ease;
        }


        .edit-btn {
            background: #edf6fc;

            color: #0066b3;

            border: 1px solid #d5e8f5;
        }


        .edit-btn:hover {
            background: #dceefa;
        }


        .delete-btn {
            background: #fff2f1;

            color: #d34b42;

            border: 1px solid #f5d5d2;
        }


        .delete-btn:hover {
            background: #ffe3e0;
        }


        /* =====================================================
           LIHAT SEMUA
        ===================================================== */

        .view-all {
            margin-top: 20px;
        }


        .view-all-button {
            display: flex;

            align-items: center;

            justify-content: center;

            gap: 12px;

            width: 100%;

            min-height: 58px;

            border: 1px solid #b9cde1;

            border-radius: 16px;

            color: #243f7d;

            background: white;

            font-size: 16px;

            font-weight: 700;

            text-decoration: none;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .view-all-button:hover {
            background: #f4f8fc;

            border-color: #243f7d;
        }


        .view-all-button .arrow {
            font-size: 20px;

            font-weight: normal;
        }


        /* =====================================================
           EMPTY SERVICE
        ===================================================== */

        .empty-service {
            padding: 35px 5px;

            text-align: center;

            color: #9caec2;

            font-size: 14px;
        }


        .empty-service-icon {
            font-size: 30px;

            margin-bottom: 8px;
        }


        /* =====================================================
           EMPTY FILTER
        ===================================================== */

        .no-category {
            display: none;

            background: white;

            border: 1px solid #dce8f2;

            border-radius: 18px;

            padding: 50px;

            text-align: center;

            color: #8fa0b5;

            grid-column: 1 / -1;
        }


        .no-category.show {
            display: block;
        }


        /* =====================================================
           RESPONSIVE TABLET
        ===================================================== */

        @media (max-width: 1100px) {

            .category-grid {
                grid-template-columns: 1fr;
            }


            .service-list-all {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        /* =====================================================
           RESPONSIVE MOBILE
        ===================================================== */

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


            .header {
                flex-direction: column;

                align-items: stretch;
            }


            .header-left {
                padding-left: 55px;
            }


            .admin-dropdown {
                width: 100%;
            }


            .admin-status {
                width: 100%;
            }


            .control-bar {
                align-items: stretch;

                flex-direction: column;
            }


            .control-actions {
                width: 100%;

                flex-direction: column;
            }


            .filter-select,
            .add-button {
                width: 100%;
            }


            .category-grid {
                grid-template-columns: 1fr;

                gap: 18px;
            }


            .category-inner {
                padding: 24px 20px;
            }


            .category-name {
                font-size: 25px;
            }


            .service-list-all {
                grid-template-columns: 1fr;
            }

        }


        /* =====================================================
           RESPONSIVE SMALL MOBILE
        ===================================================== */

        @media (max-width: 480px) {

            .main {
                padding-left: 12px;

                padding-right: 12px;
            }


            .category-card {
                padding: 10px;

                border-radius: 17px;
            }


            .category-inner {
                padding: 22px 16px;

                border-radius: 14px;
            }


            .category-name {
                font-size: 23px;
            }


            .category-heading {
                gap: 10px;
            }


            .category-dot {
                width: 23px;

                height: 23px;
            }


            .service-item {
                gap: 12px;
            }


            .service-logo {
                width: 48px;

                height: 48px;

                border-radius: 12px;
            }


            .service-logo img {
                width: 33px;

                height: 33px;
            }


            .service-name a {
                font-size: 14px;
            }


            .service-actions {
                gap: 4px;
            }


            .action-btn {
                width: 28px;

                height: 28px;

                font-size: 12px;
            }


            .view-all-button {
                min-height: 52px;

                font-size: 14px;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR TOGGLE
     HANYA SATU TOMBOL ☰
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
     SIDEBAR OVERLAY
     TIDAK DIGUNAKAN UNTUK MENUTUP SIDEBAR
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

            <a href="index.php">
                Dashboard
            </a>

        </li>


        <li>

            <a
                href="layanan.php"
                class="active"
            >
                Kelola Layanan
            </a>

        </li>


        <li>

            <a href="kategori.php">
                Kategori
            </a>

        </li>


        <li>

            <a href="kelola_admin.php">
                Kelola Admin
            </a>

        </li>


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

<main
    class="main"
    id="mainContent"
>


    <!-- =================================================
         HEADER
    ================================================= -->

    <div class="header">


        <div class="header-left">

            <h1>
                Kelola Layanan
            </h1>

            <p>
                Kelola layanan BPS Kabupaten Solok Selatan
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


    <!-- =================================================
         CONTROL BAR
    ================================================= -->

    <div class="control-bar">


        <div class="control-info">

            <h2>
                Layanan BPS
            </h2>


            <?php if (
                $selected_kategori !== null &&
                $kategori_ditemukan
            ): ?>

                <p>
                    Menampilkan layanan berdasarkan kategori yang dipilih.
                </p>

            <?php else: ?>

                <p>
                    Layanan dikelompokkan berdasarkan kategori.
                </p>

            <?php endif; ?>


        </div>


        <div class="control-actions">


            <!-- =================================================
                 FILTER KATEGORI
            ================================================= -->

            <select
                id="kategoriFilter"
                class="filter-select"
                onchange="filterKategori()"
            >

                <option
                    value=""
                    <?= $selected_kategori === null
                        ? 'selected'
                        : ''; ?>
                >
                    Semua Kategori
                </option>


                <?php foreach (
                    $data_kategori
                    as $kategori
                ): ?>

                    <option
                        value="<?= $kategori['id_kategori']; ?>"
                        <?= (
                            $selected_kategori !== null &&
                            (int) $selected_kategori ===
                            (int) $kategori['id_kategori']
                        )
                            ? 'selected'
                            : ''; ?>
                    >

                        <?= htmlspecialchars(
                            $kategori['nama_kategori']
                        ); ?>

                    </option>

                <?php endforeach; ?>


            </select>


            <!-- TAMBAH -->

            <a
                href="tambah_layanan.php"
                class="add-button"
            >
                + Tambah Layanan
            </a>


        </div>


    </div>


    <!-- =================================================
         CATEGORY GRID
    ================================================= -->

    <div
        class="category-grid"
        id="categoryGrid"
    >


        <?php if (!$kategori_ditemukan): ?>


            <!-- =================================================
                 KATEGORI TIDAK DITEMUKAN
            ================================================= -->

            <div
                class="no-category"
                style="display:block;"
            >

                <div
                    style="
                        font-size:35px;
                        margin-bottom:10px;
                    "
                >
                    📂
                </div>


                Kategori tidak ditemukan.


                <br><br>


                <a
                    href="layanan.php"
                    class="add-button"
                >
                    ← Semua Kategori
                </a>

            </div>


        <?php else: ?>


            <?php foreach (
                $layanan_per_kategori
                as $kategori
            ): ?>


                <?php

                    /*
                     * Jika ada kategori yang dipilih,
                     * hanya kategori tersebut yang ditampilkan.
                     */

                    if (
                        $selected_kategori !== null &&
                        (int) $kategori['id_kategori'] !==
                        $selected_kategori
                    ) {

                        continue;

                    }


                    $jumlah_layanan =
                        count($kategori['layanan']);


                    /*
                     * Jika filter kategori dipilih,
                     * tampilkan semua layanan.
                     *
                     * Jika Semua Kategori,
                     * tampilkan maksimal 5 layanan.
                     */

                    if ($selected_kategori !== null) {

                        $layanan_tampil =
                            $kategori['layanan'];

                    } else {

                        $layanan_tampil =
                            array_slice(
                                $kategori['layanan'],
                                0,
                                5
                            );

                    }

                ?>


                <!-- =================================================
                     CATEGORY CARD
                ================================================= -->

                <div
                    class="category-card
                        <?= $selected_kategori !== null
                            ? 'full-view'
                            : ''; ?>"
                    data-kategori="<?= $kategori['id_kategori']; ?>"
                >


                    <div class="category-inner">


                        <!-- CATEGORY HEADER -->

                        <div class="category-heading">


                            <div class="category-dot"></div>


                            <div class="category-name">

                                <?= htmlspecialchars(
                                    $kategori['nama_kategori']
                                ); ?>

                            </div>


                        </div>


                        <!-- JUMLAH LAYANAN -->

                        <div class="category-count">

                            <strong>
                                <?= $jumlah_layanan; ?>
                            </strong>

                            layanan tersedia

                        </div>


                        <!-- JUDUL DAFTAR -->

                        <div class="service-title">
                            Daftar Layanan
                        </div>


                        <div class="service-line"></div>


                        <!-- SERVICE LIST -->

                        <div
                            class="service-list
                                <?= $selected_kategori !== null
                                    ? 'service-list-all'
                                    : ''; ?>"
                            id="service-list-<?= $kategori['id_kategori']; ?>"
                        >


                            <?php if (
                                $jumlah_layanan === 0
                            ): ?>


                                <div class="empty-service">

                                    <div class="empty-service-icon">
                                        📂
                                    </div>

                                    Belum ada layanan pada kategori ini.

                                </div>


                            <?php else: ?>


                                <?php foreach (
                                    $layanan_tampil
                                    as $layanan
                                ): ?>


                                    <!-- SERVICE ITEM -->

                                    <div class="service-item">


                                        <!-- LOGO -->

                                        <?php if (
                                            !empty(
                                                $layanan['logo']
                                            )
                                        ): ?>


                                            <div class="service-logo">


                                                <img
                                                    src="uploads/<?= htmlspecialchars(
                                                        $layanan['logo']
                                                    ); ?>"
                                                    alt="Logo <?= htmlspecialchars(
                                                        $layanan['nama_layanan']
                                                    ); ?>"
                                                >


                                            </div>


                                        <?php else: ?>


                                            <div
                                                class="
                                                    service-logo
                                                    no-image
                                                "
                                            >
                                                🌐
                                            </div>


                                        <?php endif; ?>


                                        <!-- NAMA LAYANAN -->

                                        <div class="service-name">


                                            <?php if (
                                                !empty(
                                                    trim(
                                                        $layanan['url']
                                                    )
                                                )
                                            ): ?>


                                                <a
                                                    href="<?= htmlspecialchars(
                                                        trim(
                                                            $layanan['url']
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    title="<?= htmlspecialchars(
                                                        $layanan['nama_layanan']
                                                    ); ?>"
                                                >

                                                    <?= htmlspecialchars(
                                                        $layanan['nama_layanan']
                                                    ); ?>

                                                </a>


                                            <?php else: ?>


                                                <span
                                                    style="
                                                        color:#304b72;
                                                        font-size:16px;
                                                    "
                                                >

                                                    <?= htmlspecialchars(
                                                        $layanan['nama_layanan']
                                                    ); ?>

                                                </span>


                                            <?php endif; ?>


                                        </div>


                                        <!-- ACTION -->

                                        <div class="service-actions">


                                            <!-- EDIT -->

                                            <a
                                                href="edit_layanan.php?id=<?= $layanan['id_layanan']; ?>"
                                                class="action-btn edit-btn"
                                                title="Edit"
                                            >
                                                ✏️
                                            </a>


                                            <!-- DELETE -->

                                            <a
                                                href="hapus_layanan.php?id=<?= $layanan['id_layanan']; ?>"
                                                class="action-btn delete-btn"
                                                title="Hapus"
                                                onclick="
                                                    return confirm(
                                                        'Yakin ingin menghapus layanan ini?'
                                                    );
                                                "
                                            >
                                                🗑
                                            </a>


                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            <?php endif; ?>


                        </div>


                        <!-- =================================================
                             LIHAT SEMUA
                             HANYA SAAT SEMUA KATEGORI
                        ================================================= -->

                        <?php if (
                            $jumlah_layanan > 5 &&
                            $selected_kategori === null
                        ): ?>


                            <div class="view-all">


                                <a
                                    href="layanan.php?id_kategori=<?= $kategori['id_kategori']; ?>"
                                    class="view-all-button"
                                >

                                    <span>
                                        Lihat Semua
                                    </span>

                                    <span class="arrow">
                                        →
                                    </span>

                                </a>


                            </div>


                        <?php endif; ?>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


        <!-- EMPTY FILTER -->

        <div
            class="no-category"
            id="noCategory"
        >

            <div
                style="
                    font-size:35px;
                    margin-bottom:10px;
                "
            >
                📂
            </div>


            Tidak ada kategori yang dipilih.


        </div>


    </div>


</main>


<script>


    /* =====================================================
       FILTER KATEGORI
    ===================================================== */

    function filterKategori() {

        const selected =
            document.getElementById(
                'kategoriFilter'
            ).value;


        /*
         * Jika memilih Semua Kategori,
         * kembali ke layanan.php tanpa parameter.
         */

        if (selected === '') {

            window.location.href =
                'layanan.php';

            return;

        }


        /*
         * Jika memilih kategori tertentu,
         * kirim id_kategori melalui URL.
         */

        window.location.href =
            'layanan.php?id_kategori=' +
            encodeURIComponent(selected);

    }


    /* =====================================================
       SIDEBAR
       HANYA ADA FUNGSI MEMBUKA
       TIDAK ADA CLOSE / X
    ===================================================== */

    function openSidebar() {

        const sidebar =
            document.getElementById(
                'sidebar'
            );


        const main =
            document.getElementById(
                'mainContent'
            );


        const overlay =
            document.getElementById(
                'sidebarOverlay'
            );


        sidebar.classList.add(
            'sidebar-open'
        );


        main.classList.add(
            'sidebar-open'
        );


        /*
         * Overlay hanya muncul
         * pada HP/tablet.
         *
         * Overlay TIDAK digunakan
         * untuk menutup sidebar.
         */

        if (window.innerWidth <= 768) {

            overlay.classList.add(
                'show'
            );

        }

    }


    /* =====================================================
       ADMIN DROPDOWN
    ===================================================== */

    function toggleAdminMenu() {

        const menu =
            document.getElementById(
                'adminMenu'
            );


        menu.classList.toggle(
            'show'
        );

    }


    /* =====================================================
       CLOSE ADMIN DROPDOWN
    ===================================================== */

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
       MENU SIDEBAR
       LANGSUNG NAVIGASI
       TIDAK ADA CLOSE SIDEBAR
    ===================================================== */

    document
        .querySelectorAll('.menu a')
        .forEach(
            function(link) {

                link.addEventListener(
                    'click',
                    function() {

                        /*
                         * Tidak melakukan apa-apa.
                         * Browser langsung mengikuti href.
                         */

                    }
                );

            }
        );


    /* =====================================================
       RESPONSIVE SIDEBAR
    ===================================================== */

    window.addEventListener(
        'resize',
        function() {

            const sidebar =
                document.getElementById(
                    'sidebar'
                );


            const overlay =
                document.getElementById(
                    'sidebarOverlay'
                );


            /*
             * Kalau sidebar belum dibuka,
             * overlay jangan ditampilkan.
             */

            if (
                !sidebar.classList.contains(
                    'sidebar-open'
                )
            ) {

                overlay.classList.remove(
                    'show'
                );

                return;

            }


            /*
             * Sidebar sedang terbuka.
             *
             * Overlay hanya untuk HP/tablet.
             */

            if (
                window.innerWidth <= 768
            ) {

                overlay.classList.add(
                    'show'
                );

            } else {

                overlay.classList.remove(
                    'show'
                );

            }

        }
    );


</script>


</body>

</html>