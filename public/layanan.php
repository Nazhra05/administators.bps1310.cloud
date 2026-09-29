<?php

require_once '../config/config.php';
require_once '../config/auth.php';

/* =========================
   KONFIGURASI API
========================= */

$apiUrl = API_URL;
$apiKey = API_KEY;


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
    ORDER BY l.id_kategori ASC, l.id_layanan ASC
");

$data_layanan = $stmt_layanan->fetchAll(PDO::FETCH_ASSOC);

usort($data_layanan, function ($a, $b) {

    $kategoriA = (int) ($a['id_kategori'] ?? 0);
    $kategoriB = (int) ($b['id_kategori'] ?? 0);

    if ($kategoriA === $kategoriB) {

        return
            (int) ($a['id_layanan'] ?? 0)
            <=>
            (int) ($b['id_layanan'] ?? 0);

    }

    return $kategoriA <=> $kategoriB;

});


/* =========================
   AMBIL SEMUA KATEGORI
========================= */

$stmt_kategori = $conn->query("
    SELECT id_kategori, nama_kategori
    FROM kategori
    ORDER BY id_kategori ASC
");

$data_kategori = $stmt_kategori->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   FILTER KATEGORI
========================= */

$filter_kategori = isset($_GET['kategori'])
    ? $_GET['kategori']
    : '';


/* =========================
   USERNAME ADMIN
========================= */

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
            align-items: center;
            justify-content: center;
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            gap: 20px;
        }

        .header-left h1 {
            font-size: 28px;
            color: #005a9c;
        }

        .header-left p {
            color: #6b7280;
            margin-top: 6px;
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
           BUTTON TAMBAH
        ========================= */

        .add-container {
            margin-bottom: 20px;
        }

        .btn {
            color: white;
            padding: 11px 18px;
            text-decoration: none;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-block;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .btn-add {
            background: #0066b3;
        }

        .btn-add:hover {
            background: #00589c;
            transform: translateY(-1px);
        }


        /* =========================
           FILTER
        ========================= */

        .filter-container {
            background: white;
            padding: 18px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .filter-container label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .filter-select {
            min-width: 230px;
            padding: 10px 14px;
            border: 1px solid #b9d4e8;
            border-radius: 8px;
            background: white;
            color: #374151;
            font-size: 14px;
            cursor: pointer;
            outline: none;
        }

        .filter-select:focus {
            border-color: #0066b3;
            box-shadow: 0 0 0 3px rgba(0, 102, 179, 0.1);
        }

        .reset-filter {
            padding: 10px 14px;
            background: #eaf4fb;
            color: #0066b3;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .reset-filter:hover {
            background: #dceef9;
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
            border-collapse: collapse;
            min-width: 850px;
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
           LOGO
        ========================= */

        .logo-img {
            width: 45px;
            height: 45px;
            object-fit: contain;
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


        /* =========================
           ACTION
        ========================= */

        .action {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .action-btn span {
            font-size: 14px;
        }

        .edit-btn {
            background: #eaf4fb;
            color: #0066b3;
            border-color: #b9d4e8;
        }

        .edit-btn:hover {
            background: #dceef9;
            border-color: #8fbdd9;
            transform: translateY(-1px);
        }

        .delete-btn {
            background: #fef2f2;
            color: #ff7e42;
            border-color: #fecaca;
        }

        .delete-btn:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            transform: translateY(-1px);
        }


        /* =========================
           EMPTY
        ========================= */

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }


        /* =====================================================
           RESPONSIVE MOBILE
           HANYA UNTUK LAYAR MAKSIMAL 768PX
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
                align-items: stretch;
                gap: 15px;
                margin-bottom: 20px;
            }

            .header-left h1 {
                font-size: 24px;
            }

            .header-left p {
                font-size: 14px;
                line-height: 1.5;
            }


            /* ADMIN */

            .admin-dropdown {
                width: 100%;
            }

            .admin-status {
                width: 100%;
                justify-content: flex-start;
            }


            /* TAMBAH */

            .add-container {
                width: 100%;
            }

            .btn-add {
                width: 100%;
                text-align: center;
                padding: 12px;
            }


            /* FILTER */

            .filter-container {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 16px;
            }

            .filter-container label {
                width: 100%;
            }

            .filter-select {
                width: 100%;
                min-width: 0;
            }

            .reset-filter {
                text-align: center;
            }


            /* TABLE */

            .table-container {
                padding: 15px;
                width: 100%;
                overflow-x: auto;
            }

            table {
                min-width: 850px;
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


        /* =====================================================
           HP SANGAT KECIL
        ===================================================== */

        @media (max-width: 480px) {

            .main {
                padding-left: 12px;
                padding-right: 12px;
            }

            .header-left h1 {
                font-size: 22px;
            }

            .header-left p {
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

            .table-container {
                padding: 12px;
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


    <!-- HEADER -->

    <div class="header">

        <div class="header-left">

            <h1>Manage Link</h1>

            <p>
                Kelola layanan BPS Kabupaten Solok Selatan
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


    <!-- TOMBOL TAMBAH -->

    <div class="add-container">

        <a
            href="tambah_layanan.php"
            class="btn btn-add"
        >
            + Tambah Layanan
        </a>

    </div>


    <!-- FILTER KATEGORI -->

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
                href="layanan.php"
                class="reset-filter"
            >
                Reset Filter
            </a>

        <?php endif; ?>

    </div>


    <!-- TABLE -->

    <div class="table-container">

        <table>

            <thead>

                <tr>
                    <th>No</th>
                    <th>Logo</th>
                    <th>Judul</th>
                    <th>Link</th>
                    <th>Kategori</th>
                    <th>Action</th>
                </tr>

            </thead>

            <tbody>

            <?php if (empty($data_layanan)): ?>

                <tr>

                    <td
                        colspan="6"
                        class="empty"
                    >
                        Belum ada data layanan.
                    </td>

                </tr>

            <?php else: ?>

                <?php $no = 1; ?>

                <?php foreach ($data_layanan as $layanan): ?>

                    <?php

                    if (
                        $filter_kategori != '' &&
                        $layanan['id_kategori'] != $filter_kategori
                    ) {
                        continue;
                    }

                    ?>

                    <tr>

                        <!-- NOMOR -->

                        <td>
                            <?= $no++; ?>
                        </td>


                        <!-- LOGO -->

                        <td>

                            <?php if (!empty($layanan['logo'])): ?>

                                <img
                                    src="uploads/<?= htmlspecialchars($layanan['logo']); ?>"
                                    class="logo-img"
                                    alt="Logo Layanan"
                                >

                            <?php else: ?>

                                -

                            <?php endif; ?>

                        </td>


                        <!-- JUDUL -->

                        <td>

                            <?= htmlspecialchars(
                                $layanan['nama_layanan']
                            ); ?>

                        </td>


                        <!-- LINK -->

                        <td>

                            <?php if (!empty(trim($layanan['url']))): ?>

                                <a
                                    href="<?= htmlspecialchars(
                                        trim($layanan['url']),
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
                                    class="link-icon"
                                    style="opacity: 0.5; cursor: not-allowed;"
                                    title="URL belum tersedia"
                                >
                                    🔗
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- KATEGORI -->

                        <td>

                            <?= htmlspecialchars(
                                $layanan['nama_kategori'] ?? '-'
                            ); ?>

                        </td>


                        <!-- ACTION -->

                        <td class="action">

                            <!-- EDIT -->

                            <a
                                href="edit_layanan.php?id=<?= $layanan['id_layanan']; ?>"
                                class="action-btn edit-btn"
                                title="Edit Layanan"
                            >

                                <span>✏</span>

                                Edit

                            </a>


                            <!-- HAPUS -->

                            <a
                                href="hapus_layanan.php?id=<?= $layanan['id_layanan']; ?>"
                                class="action-btn delete-btn"
                                title="Hapus Layanan"
                                onclick="return confirm('Yakin ingin menghapus layanan ini?');"
                            >

                                <span>🗑</span>

                                Hapus

                            </a>

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
   TUTUP SIDEBAR SETELAH PILIH MENU
========================= */

document.querySelectorAll('.menu a').forEach(function(link) {

    link.addEventListener('click', function() {

        if (window.innerWidth <= 768) {
            closeMobileSidebar();
        }

    });

});


/* =========================
   DROPDOWN ADMIN
========================= */

function toggleAdminMenu() {

    const menu = document.getElementById('adminMenu');

    menu.classList.toggle('show');

}


/* =========================
   TUTUP DROPDOWN JIKA KLIK DI LUAR
========================= */

document.addEventListener('click', function(event) {

    const dropdown = document.querySelector('.admin-dropdown');

    const menu = document.getElementById('adminMenu');

    if (!dropdown.contains(event.target)) {

        menu.classList.remove('show');

    }

});


/* =========================
   FILTER KATEGORI
========================= */

function filterKategori(idKategori) {

    if (idKategori === '') {

        window.location.href = 'layanan.php';

    } else {

        window.location.href =
            'layanan.php?kategori=' + idKategori;

    }

}

</script>

</body>

</html>