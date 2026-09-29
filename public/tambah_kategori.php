<?php

require_once '../config/config.php';
require_once '../config/auth.php';

/* Username admin */
$admin_username = $_SESSION['admin_username'] ?? 'Admin';

$error = '';

/* Proses simpan kategori */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify()) {
        $error = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');

    if ($nama_kategori === '') {

        $error = 'Nama kategori wajib diisi.';

    } else {

        /* Cek apakah kategori sudah ada */
        $cek = $conn->prepare("
            SELECT COUNT(*)
            FROM kategori
            WHERE nama_kategori = ?
        ");

        $cek->execute([$nama_kategori]);

        if ($cek->fetchColumn() > 0) {

            $error = 'Kategori tersebut sudah ada.';

        } else {

            /* Simpan kategori */
            $stmt = $conn->prepare("
                INSERT INTO kategori (nama_kategori)
                VALUES (?)
            ");

            $stmt->execute([$nama_kategori]);

            header('Location: kategori.php');
            exit;
        }
    }
}
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Kategori - BPS Solok Selatan</title>

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
        }

        .menu a {
            display: block;
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

        .menu-toggle {
            display: none;
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
            margin-left: 240px;
            padding: 30px;
            min-height: 100vh;
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
           FORM
        ========================= */

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);
            max-width: 700px;
            width: 100%;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #0066b3;
            box-shadow: 0 0 0 3px rgba(0, 102, 179, 0.1);
        }

        /* =========================
           ERROR
        ========================= */

        .error {
            background: #fff1f1;
            color: #c62828;
            border: 1px solid #f3b5b5;
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        /* =========================
           BUTTON
        ========================= */

        .button-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-simpan {
            border: none;
            padding: 11px 20px;
            background: #0066b3;
            color: white;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-simpan:hover {
            background: #00589c;
        }

        .btn-kembali {
            display: inline-block;
            padding: 11px 20px;
            background: #eef2f6;
            color: #374151;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-kembali:hover {
            background: #e2e8f0;
        }

        /* =========================
           RESPONSIVE TABLET / HP
        ========================= */

        @media (max-width: 768px) {

            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
                padding: 80px 20px 30px;
            }

            .header {
                flex-direction: column;
                align-items: stretch;
                gap: 18px;
            }

            .header h1 {
                font-size: 24px;
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

            .form-container {
                max-width: 100%;
                padding: 22px;
            }

            .button-group {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-simpan,
            .btn-kembali {
                width: 100%;
                text-align: center;
            }

        }

        /* =========================
           RESPONSIVE HP KECIL
        ========================= */

        @media (max-width: 480px) {

            .main {
                padding: 75px 15px 25px;
            }

            .header h1 {
                font-size: 22px;
            }

            .header p {
                font-size: 14px;
                line-height: 1.5;
            }

            .form-container {
                padding: 18px;
                border-radius: 8px;
            }

            .form-group {
                margin-bottom: 18px;
            }

            .form-group input {
                padding: 11px 12px;
                font-size: 14px;
            }

            .admin-status {
                padding: 9px 12px;
            }

            .admin-icon {
                width: 34px;
                height: 34px;
            }

            .admin-name {
                font-size: 13px;
            }

        }

    </style>

</head>

<body>

<!-- MOBILE MENU BUTTON -->

<button
    type="button"
    class="menu-toggle"
    id="menuToggle"
    onclick="toggleSidebar()"
    aria-label="Buka menu"
>
    ☰
</button>


<!-- SIDEBAR OVERLAY -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
    onclick="closeSidebar()"
></div>


<!-- SIDEBAR -->

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


<!-- MAIN -->

<main class="main">

    <div class="header">

        <div>

            <h1>Tambah Kategori</h1>

            <p>
                Tambahkan kategori layanan baru.
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


    <!-- FORM -->

    <div class="form-container">

        <?php if ($error): ?>

            <div class="error">
                <?= htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">
            <?= csrf_field(); ?>

            <div class="form-group">

                <label for="nama_kategori">
                    Nama Kategori
                </label>

                <input
                    type="text"
                    id="nama_kategori"
                    name="nama_kategori"
                    placeholder="Masukkan nama kategori"
                    required
                    value="<?= htmlspecialchars($_POST['nama_kategori'] ?? ''); ?>"
                >

            </div>


            <div class="button-group">

                <button
                    type="submit"
                    class="btn-simpan"
                >
                    Simpan Kategori
                </button>

                <a
                    href="kategori.php"
                    class="btn-kembali"
                >
                    Kembali
                </a>

            </div>

        </form>

    </div>

</main>


<script>

/* =========================
   SIDEBAR MOBILE
========================= */

function toggleSidebar() {

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');

}


function closeSidebar() {

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.remove('show');
    overlay.classList.remove('show');

}


/* =========================
   ADMIN DROPDOWN
========================= */

function toggleAdminMenu() {

    const menu = document.getElementById('adminMenu');

    menu.classList.toggle('show');

}


/* Tutup dropdown jika klik di luar */

document.addEventListener('click', function(event) {

    const dropdown = document.querySelector('.admin-dropdown');

    const menu = document.getElementById('adminMenu');

    if (!dropdown.contains(event.target)) {

        menu.classList.remove('show');

    }

});


/* Tutup sidebar ketika memilih menu di HP */

document.querySelectorAll('.menu a').forEach(function(link) {

    link.addEventListener('click', function() {

        closeSidebar();

    });

});

</script>

</body>

</html>