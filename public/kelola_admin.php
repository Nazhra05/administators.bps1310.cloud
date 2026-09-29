<?php
require_once '../config/config.php';
require_once '../config/auth.php';

$message = '';
$messageType = '';

/* =========================
   PROSES APPROVE / REJECT
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify()) {
        $message = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
        $messageType = 'error';
    } else {
        $id_admin = (int)($_POST['id_admin'] ?? 0);
        $action = $_POST['action'] ?? '';

    if ($id_admin <= 0) {
        $message = 'ID admin tidak valid.';
        $messageType = 'error';

    } elseif (!in_array($action, ['approve', 'reject'], true)) {
        $message = 'Aksi tidak valid.';
        $messageType = 'error';

    } else {

        if ($action === 'approve') {

            $stmt = $conn->prepare("
                UPDATE admin
                SET status = 'approved'
                WHERE id_admin = ?
                  AND status = 'pending'
            ");

            $stmt->execute([$id_admin]);

            if ($stmt->rowCount() > 0) {
                $message = 'Akun berhasil disetujui.';
                $messageType = 'success';
            } else {
                $message = 'Akun tidak ditemukan atau sudah diproses.';
                $messageType = 'error';
            }

        } elseif ($action === 'reject') {

            $stmt = $conn->prepare("
                UPDATE admin
                SET status = 'rejected'
                WHERE id_admin = ?
                  AND status = 'pending'
            ");

            $stmt->execute([$id_admin]);

            if ($stmt->rowCount() > 0) {
                $message = 'Akun berhasil ditolak.';
                $messageType = 'success';
            } else {
                $message = 'Akun tidak ditemukan atau sudah diproses.';
                $messageType = 'error';
            }
        }
    }
}

/* =========================
   AMBIL AKUN PENDING
========================= */

$stmt = $conn->prepare("
    SELECT
        id_admin,
        first_name,
        last_name,
        username,
        email,
        status,
        email_verified,
        created_at
    FROM admin
    WHERE status = 'pending'
    ORDER BY created_at DESC
");

$stmt->execute();
$pendingAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola Admin - Dashboard Admin BPS Solok Selatan</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
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

        .page-header {
            background: #005baa;
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 26px;
            margin-bottom: 8px;
        }

        .page-header p {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.5;
        }

        /* =========================
           ALERT
        ========================= */

        .alert {
            padding: 15px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert.success {
            background: #e8f7ee;
            color: #1f7a42;
            border: 1px solid #b9e5c8;
        }

        .alert.error {
            background: #fdecec;
            color: #b42318;
            border: 1px solid #f3b7b2;
        }

        /* =========================
           CARD
        ========================= */

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .card-title {
            font-size: 20px;
            font-weight: bold;
            color: #005baa;
            margin-bottom: 20px;
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
            min-width: 900px;
        }

        th {
            background: #005baa;
            color: white;
            padding: 13px;
            text-align: left;
            font-size: 14px;
            white-space: nowrap;
        }

        td {
            padding: 13px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            vertical-align: middle;
        }

        tr:hover {
            background: #f8fbff;
        }

        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status.pending {
            background: #fff4d6;
            color: #9a6700;
        }

        .verified {
            color: #16803c;
            font-weight: bold;
            white-space: nowrap;
        }

        .not-verified {
            color: #b42318;
            font-weight: bold;
            white-space: nowrap;
        }

        /* =========================
           ACTION
        ========================= */

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .actions form {
            margin: 0;
        }

        .btn {
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            color: white;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-approve {
            background: #198754;
        }

        .btn-approve:hover {
            background: #157347;
        }

        .btn-reject {
            background: #dc3545;
        }

        .btn-reject:hover {
            background: #bb2d3b;
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            text-align: center;
            padding: 45px 20px;
            color: #777;
        }

        .empty-icon {
            font-size: 40px;
            margin-bottom: 10px;
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

            .page-header {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .page-header p {
                font-size: 13px;
            }

            .card {
                padding: 18px;
            }

            .card-title {
                font-size: 18px;
            }

            .table-wrapper {
                margin-left: -2px;
                margin-right: -2px;
            }

        }

        /* =========================
           RESPONSIVE HP KECIL
        ========================= */

        @media (max-width: 480px) {

            .main {
                padding: 75px 15px 25px;
            }

            .page-header {
                padding: 18px;
                border-radius: 9px;
            }

            .page-header h1 {
                font-size: 21px;
            }

            .page-header p {
                font-size: 13px;
                line-height: 1.5;
            }

            .card {
                padding: 15px;
                border-radius: 9px;
            }

            .card-title {
                font-size: 17px;
                margin-bottom: 16px;
            }

            .alert {
                font-size: 13px;
                padding: 12px 14px;
            }

            .empty {
                padding: 35px 15px;
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
    onclick="toggleSidebar()"
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
    onclick="closeSidebar()"
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
            <a href="kelola_admin.php" class="active">
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


<!-- =========================
     MAIN
========================= -->

<main class="main">

    <div class="page-header">

        <h1>
            Kelola Admin
        </h1>

        <p>
            Kelola persetujuan akun administrator yang telah melakukan pendaftaran.
        </p>

    </div>


    <!-- ALERT -->

    <?php if ($message !== ''): ?>

        <div class="alert <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- CARD -->

    <div class="card">

        <div class="card-title">
            Pengajuan Akun Administrator
        </div>


        <?php if (count($pendingAdmins) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Nama</th>

                            <th>Username</th>

                            <th>Email</th>

                            <th>Verifikasi Email</th>

                            <th>Status</th>

                            <th>Tanggal Daftar</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($pendingAdmins as $index => $admin): ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    trim(
                                        ($admin['first_name'] ?? '') .
                                        ' ' .
                                        ($admin['last_name'] ?? '')
                                    )
                                ) ?>
                            </td>


                            <td>

                                <strong>
                                    <?= htmlspecialchars($admin['username']) ?>
                                </strong>

                            </td>


                            <td>
                                <?= htmlspecialchars($admin['email']) ?>
                            </td>


                            <td>

                                <?php if ((int)$admin['email_verified'] === 1): ?>

                                    <span class="verified">
                                        ✓ Terverifikasi
                                    </span>

                                <?php else: ?>

                                    <span class="not-verified">
                                        ✕ Belum
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span class="status pending">
                                    Pending
                                </span>

                            </td>


                            <td>
                                <?= htmlspecialchars($admin['created_at']) ?>
                            </td>


                            <td>

                                <div class="actions">

                                    <!-- APPROVE -->

                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menyetujui akun ini?');"
                                    >
                                        <?= csrf_field(); ?>

                                        <input
                                            type="hidden"
                                            name="id_admin"
                                            value="<?= (int)$admin['id_admin'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-approve"
                                        >
                                            ✓ Setujui
                                        </button>

                                    </form>


                                    <!-- REJECT -->

                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menolak akun ini?');"
                                    >
                                        <?= csrf_field(); ?>

                                        <input
                                            type="hidden"
                                            name="id_admin"
                                            value="<?= (int)$admin['id_admin'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-reject"
                                        >
                                            ✕ Tolak
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty">

                <div class="empty-icon">
                    ✓
                </div>

                <strong>
                    Tidak ada akun yang menunggu persetujuan.
                </strong>

                <p style="margin-top: 8px;">
                    Semua pengajuan akun sudah diproses.
                </p>

            </div>

        <?php endif; ?>

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


/* Tutup sidebar ketika memilih menu */

document.querySelectorAll('.menu a').forEach(function(link) {

    link.addEventListener('click', function() {

        closeSidebar();

    });

});

</script>

</body>

</html>