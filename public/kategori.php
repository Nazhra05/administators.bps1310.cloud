<?php

require_once '../config/config.php';
require_once '../config/auth.php';

/* Ambil semua kategori */
$stmt = $conn->query("
    SELECT 
        k.id_kategori,
        k.nama_kategori,
        COUNT(l.id_layanan) AS jumlah_layanan
    FROM kategori k
    LEFT JOIN layanan l
        ON k.id_kategori = l.id_kategori
    GROUP BY k.id_kategori, k.nama_kategori
    ORDER BY k.id_kategori ASC
");

$kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Username admin */
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

    <title>Kategori - BPS Solok Selatan</title>

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
           MOBILE MENU
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


        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 240px;
            padding: 30px;
            min-width: 0;
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
            flex-shrink: 0;
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
           BUTTON TAMBAH KATEGORI
        ========================= */

        .action-bar {
            margin-bottom: 20px;
        }

        .btn-tambah {
            display: inline-block;
            padding: 11px 18px;
            background: #0066b3;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .btn-tambah:hover {
            background: #00589c;
        }


        /* =========================
           TABLE
        ========================= */

        .table-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }

        th {
            background: #eaf4fb;
            text-align: left;
            padding: 14px;
            color: #005a9c;
            font-size: 18px;
            font-weight: bold;
            white-space: nowrap;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover td {
            background: #f7fbfe;
        }


        /* =========================
           JUMLAH LAYANAN
        ========================= */

        .jumlah-layanan {
            color: #0066b3;
            font-weight: 600;
            white-space: nowrap;
        }


        /* =========================
           ACTION
        ========================= */

        .action {
            display: flex;
            gap: 8px;
            align-items: center;
            white-space: nowrap;
        }

        .btn-action {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .btn-edit {
            background: #eaf4fb;
            color: #0066b3;
        }

        .btn-edit:hover {
            background: #d8ebf8;
        }

        .btn-delete {
            background: #fff1f1;
            color: #ff7e42;
        }

        .btn-delete:hover {
            background: #ffe0e0;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty {
            text-align: center;
            color: #777;
            padding: 30px;
        }


        /* =====================================================
           RESPONSIVE MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            /* SIDEBAR */

            .sidebar {
                width: 250px;
                height: 100vh;
                transform: translateX(-100%);
                padding: 25px 15px;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }


            /* TOMBOL MENU */

            .mobile-menu-button {
                display: flex;
            }


            /* MAIN */

            .main {
                margin-left: 0;
                padding: 75px 15px 25px;
                width: 100%;
            }


            /* HEADER */

            .header {
                flex-direction: column;
                gap: 15px;
                margin-bottom: 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .header p {
                font-size: 14px;
            }


            /* ADMIN */

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


            /* BUTTON TAMBAH */

            .action-bar {
                width: 100%;
                margin-bottom: 15px;
            }

            .btn-tambah {
                display: block;
                width: 100%;
                text-align: center;
                padding: 12px;
            }


            /* TABLE */

            .table-container {
                padding: 15px;
                width: 100%;
                overflow-x: auto;
            }

            table {
                min-width: 650px;
            }

            th {
                padding: 12px;
                font-size: 15px;
            }

            td {
                padding: 12px;
                font-size: 14px;
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

            .table-container {
                padding: 10px;
            }

            table {
                min-width: 620px;
            }

            th {
                font-size: 14px;
                padding: 10px;
            }

            td {
                font-size: 13px;
                padding: 10px;
            }

            .btn-action {
                padding: 7px 10px;
                font-size: 12px;
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
            <a href="layanan.php">
                Kelola Layanan
            </a>
        </li>

        <li>
            <a href="kategori.php" class="active">
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

<main class="main">

    <div class="header">

        <div>

            <h1>Kategori</h1>

            <p>
                Daftar kategori layanan BPS Kabupaten Solok Selatan.
            </p>

        </div>


        <!-- ADMIN YANG SEDANG LOGIN -->

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


    <!-- BUTTON TAMBAH KATEGORI -->

    <div class="action-bar">

        <a
            href="tambah_kategori.php"
            class="btn-tambah"
        >
            + Tambah Kategori
        </a>

    </div>


    <div class="table-container">

        <table>

            <thead>

                <tr>
                    <th>No</th>
                    <th>Nama Kategori</th>
                    <th>Jumlah Layanan</th>
                    <th>Aksi</th>
                </tr>

            </thead>

            <tbody>

            <?php if (empty($kategori)): ?>

                <tr>

                    <td
                        colspan="4"
                        class="empty"
                    >
                        Belum ada kategori.
                    </td>

                </tr>

            <?php else: ?>

                <?php $no = 1; ?>

                <?php foreach ($kategori as $item): ?>

                    <tr>

                        <td>
                            <?= $no++; ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($item['nama_kategori']); ?>
                        </td>

                        <td class="jumlah-layanan">
                            <?= $item['jumlah_layanan']; ?> layanan
                        </td>

                        <td>

                            <div class="action">

                                <a
                                    href="edit_kategori.php?id=<?= $item['id_kategori']; ?>"
                                    class="btn-action btn-edit"
                                >
                                    ✏️ Edit
                                </a>

                                <a
                                    href="hapus_kategori.php?id=<?= $item['id_kategori']; ?>"
                                    class="btn-action btn-delete"
                                    onclick="return confirm('Yakin ingin menghapus kategori ini?');"
                                >
                                    🗑️ Hapus
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>


<script>

/* =========================
   SIDEBAR MOBILE
========================= */

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


/* =========================
   TUTUP SIDEBAR MOBILE
========================= */

function closeMobileSidebar() {

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const button = document.getElementById('mobileMenuButton');

    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('show');

    button.innerHTML = '☰';
    button.setAttribute('aria-label', 'Buka menu');

}


/* =========================
   TUTUP SIDEBAR SETELAH
   PILIH MENU
========================= */

document.querySelectorAll('.menu a').forEach(function(link) {

    link.addEventListener('click', function() {

        if (window.innerWidth <= 768) {
            closeMobileSidebar();
        }

    });

});


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