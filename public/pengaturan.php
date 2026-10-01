<?php

require_once '../config/config.php';

/* Username admin */
$admin_username = $_SESSION['admin_username'] ?? 'Admin';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pengaturan - BPS Solok Selatan</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f8fc;
            color: #333;
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

            /* SIDEBAR TERTUTUP SAAT HALAMAN DIBUKA */
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        /* SIDEBAR TERBUKA */
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
            color: #dceeff;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            transition: 0.2s ease;
        }

        .menu a:hover {
            background: #00589c;
            color: white;
        }

        .menu .active {
            background: white;
            color: #0066b3;
            font-weight: bold;
        }

        /* =========================
           MOBILE MENU BUTTON
           HANYA ☰
        ========================= */

        .menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;

            position: fixed;
            top: 15px;
            left: 15px;

            width: 44px;
            height: 44px;

            border: none;
            border-radius: 8px;

            background: #0066b3;
            color: white;

            font-size: 23px;
            cursor: pointer;

            z-index: 10001;

            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        }

        .menu-toggle:hover {
            background: #00589c;
        }

        /* =========================
           SIDEBAR OVERLAY
        ========================= */

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 9998;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 0;
            padding: 30px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .main.sidebar-open {
            margin-left: 240px;
        }

        /* =========================
           HEADER
        ========================= */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
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
           SETTINGS GRID
        ========================= */

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        /* =========================
           SETTINGS CARD
        ========================= */

        .settings-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);
            border-left: 4px solid #0066b3;
        }

        .settings-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
        }

        .settings-icon {
            width: 38px;
            height: 38px;
            background: #eaf4fb;
            color: #0066b3;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .settings-title h2 {
            font-size: 18px;
            color: #005a9c;
        }

        /* =========================
           INFO ITEM
        ========================= */

        .info-item {
            padding: 13px 0;
            border-bottom: 1px solid #edf2f7;
        }

        .info-item:first-child {
            padding-top: 0;
        }

        .info-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        /* =========================
           SECURITY
        ========================= */

        .security-text {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .logout-main {
            display: inline-block;
            padding: 10px 16px;
            background: #0066b3;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .logout-main:hover {
            background: #00589c;
        }

        /* =========================
           RESPONSIVE TABLET
        ========================= */

        @media (max-width: 900px) {

            .settings-grid {
                grid-template-columns: 1fr;
            }

        }

        /* =========================
           RESPONSIVE MOBILE
        ========================= */

        @media (max-width: 768px) {

            .main {
                margin-left: 0;
                padding: 80px 20px 30px;
            }

            .main.sidebar-open {
                margin-left: 0;
            }

            .header {
                flex-direction: column;
                align-items: stretch;
                gap: 18px;
            }

            .header h1 {
                font-size: 24px;
            }

            .header p {
                font-size: 14px;
            }

            .admin-dropdown {
                width: 100%;
            }

            .admin-status {
                width: 100%;
                justify-content: flex-start;
            }

            .admin-menu {
                left: 0;
                right: auto;
                width: 100%;
            }

            .settings-card {
                padding: 20px;
            }

        }

        /* =========================
           RESPONSIVE HP KECIL
        ========================= */

        @media (max-width: 480px) {

            .menu-toggle {
                top: 12px;
                left: 12px;
                width: 42px;
                height: 42px;
            }

            .main {
                padding: 75px 15px 25px;
            }

            .header h1 {
                font-size: 22px;
            }

            .header p {
                font-size: 13px;
            }

            .settings-card {
                padding: 18px;
                border-radius: 9px;
            }

            .settings-title h2 {
                font-size: 17px;
            }

            .info-value {
                font-size: 13px;
            }

        }

    </style>

</head>

<body>


<!-- =========================
     MOBILE MENU BUTTON
========================= -->

<button
    type="button"
    class="menu-toggle"
    id="menuToggle"
    onclick="openSidebar()"
    aria-label="Buka menu"
>
    ☰
</button>


<!-- =========================
     SIDEBAR OVERLAY
========================= -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- =========================
     SIDEBAR
========================= -->

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
            <a href="layanan.php">
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
            <a href="pengaturan.php" class="active">
                Pengaturan
            </a>
        </li>

    </ul>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main" id="mainContent">

    <div class="header">

        <div>

            <h1>
                Pengaturan
            </h1>

            <p>
                Kelola informasi admin dan keamanan akun.
            </p>

        </div>


        <!-- =========================
             ADMIN DROPDOWN
        ========================= -->

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


            <!-- DROPDOWN LOGOUT -->

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


    <!-- =========================
         SETTINGS
    ========================= -->

    <div class="settings-grid">


        <!-- =========================
             PROFIL ADMIN
        ========================= -->

        <div class="settings-card">

            <div class="settings-title">

                <div class="settings-icon">
                    👤
                </div>

                <h2>
                    Profil Admin
                </h2>

            </div>


            <div class="info-item">

                <span class="info-label">
                    Username
                </span>

                <span class="info-value">
                    <?= htmlspecialchars($admin_username); ?>
                </span>

            </div>


            <div class="info-item">

                <span class="info-label">
                    Sistem
                </span>

                <span class="info-value">
                    Dashboard Admin BPS Solok Selatan
                </span>

            </div>

        </div>


        <!-- =========================
             KEAMANAN
        ========================= -->

        <div class="settings-card">

            <div class="settings-title">

                <div class="settings-icon">
                    🔐
                </div>

                <h2>
                    Keamanan
                </h2>

            </div>


            <p class="security-text">
                Pastikan kamu selalu keluar dari akun setelah selesai menggunakan dashboard, terutama jika menggunakan komputer bersama.
            </p>


            <a
                href="logout.php"
                class="logout-main"
            >
                🚪 Logout dari Akun
            </a>

        </div>

    </div>

</main>


<script>

/* =========================
   BUKA SIDEBAR
========================= */

function openSidebar() {

    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.add('sidebar-open');
    main.classList.add('sidebar-open');

    if (window.innerWidth <= 768) {
        overlay.classList.add('show');
    }

}


/* =========================
   ADMIN DROPDOWN
========================= */

function toggleAdminMenu() {

    const menu = document.getElementById('adminMenu');

    menu.classList.toggle('show');

}


/* =========================
   TUTUP DROPDOWN JIKA
   KLIK DI LUAR
========================= */

document.addEventListener('click', function(event) {

    const dropdown = document.querySelector('.admin-dropdown');

    const menu = document.getElementById('adminMenu');

    if (!dropdown.contains(event.target)) {

        menu.classList.remove('show');

    }

});

</script>


</body>

</html>