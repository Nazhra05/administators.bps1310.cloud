<?php

require_once '../config/config.php';

/* =========================
   CEK LOGIN ADMIN
========================= */

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

/* =========================
   CEK ADMIN IT
========================= */

if (($_SESSION['admin_role'] ?? '') !== 'ADMIN IT') {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

$currentAdminId = (int)$_SESSION['admin_id'];

/* =========================
   PROSES POST
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =========================
       CEK CSRF
    ========================= */

    if (!csrf_verify()) {

        $message = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
        $messageType = 'error';

    } else {

        $id_admin = (int)($_POST['id_admin'] ?? 0);
        $action = $_POST['action'] ?? '';

        /* =========================
           VALIDASI ID
        ========================= */

        if ($id_admin <= 0) {

            $message = 'ID admin tidak valid.';
            $messageType = 'error';

        } elseif (!in_array($action, [
            'approve',
            'reject',
            'make_main',
            'make_regular',
            'delete'
        ], true)) {

            $message = 'Aksi tidak valid.';
            $messageType = 'error';

        } else {

            /* =========================
               APPROVE
               DEFAULT = ADMIN DASHBOARD
            ========================= */

            if ($action === 'approve') {

                $stmt = $conn->prepare("
                    UPDATE admin
                    SET
                        status = 'approved',
                        role = 'ADMIN DASHBOARD'
                    WHERE id_admin = ?
                      AND status = 'pending'
                ");

                $stmt->execute([$id_admin]);

                if ($stmt->rowCount() > 0) {

                    $message = 'Akun berhasil disetujui sebagai ADMIN DASHBOARD.';
                    $messageType = 'success';

                } else {

                    $message = 'Akun tidak ditemukan atau sudah diproses.';
                    $messageType = 'error';
                }
            }

            /* =========================
               REJECT
            ========================= */

            elseif ($action === 'reject') {

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

            /* =========================
               JADIKAN ADMIN IT
            ========================= */

            elseif ($action === 'make_main') {

                if ($id_admin === $currentAdminId) {

                    $message = 'Akun Anda sudah menjadi ADMIN IT.';
                    $messageType = 'error';

                } else {

                    /* =========================
                       CEK ROLE TARGET
                    ========================= */

                    $stmt = $conn->prepare("
                        SELECT role
                        FROM admin
                        WHERE id_admin = ?
                          AND status = 'approved'
                        LIMIT 1
                    ");

                    $stmt->execute([$id_admin]);

                    $targetRole = $stmt->fetchColumn();

                    if (!$targetRole) {

                        $message = 'Admin tidak ditemukan atau belum disetujui.';
                        $messageType = 'error';

                    } elseif ($targetRole !== 'ADMIN DASHBOARD') {

                        $message = 'Hanya ADMIN DASHBOARD yang dapat dijadikan ADMIN IT.';
                        $messageType = 'error';

                    } else {

                        /* =========================
                           HITUNG ADMIN IT
                        ========================= */

                        $stmt = $conn->query("
                            SELECT COUNT(*)
                            FROM admin
                            WHERE role = 'ADMIN IT'
                              AND status = 'approved'
                        ");

                        $jumlahAdminIT = (int)$stmt->fetchColumn();

                        if ($jumlahAdminIT >= 3) {

                            $message = 'Maksimal 3 ADMIN IT. Tidak dapat menambahkan ADMIN IT lagi.';
                            $messageType = 'error';

                        } else {

                            $stmt = $conn->prepare("
                                UPDATE admin
                                SET role = 'ADMIN IT'
                                WHERE id_admin = ?
                                  AND status = 'approved'
                                  AND role = 'ADMIN DASHBOARD'
                            ");

                            $stmt->execute([$id_admin]);

                            if ($stmt->rowCount() > 0) {

                                $message = 'Admin berhasil dijadikan ADMIN IT.';
                                $messageType = 'success';

                            } else {

                                $message = 'Admin tidak ditemukan atau tidak dapat diubah.';
                                $messageType = 'error';
                            }
                        }
                    }
                }
            }

            /* =========================
               JADIKAN ADMIN DASHBOARD
            ========================= */

            elseif ($action === 'make_regular') {

                if ($id_admin === $currentAdminId) {

                    $message = 'Anda tidak dapat menurunkan role akun Anda sendiri.';
                    $messageType = 'error';

                } else {

                    $stmt = $conn->prepare("
                        UPDATE admin
                        SET role = 'ADMIN DASHBOARD'
                        WHERE id_admin = ?
                          AND status = 'approved'
                          AND role = 'ADMIN IT'
                    ");

                    $stmt->execute([$id_admin]);

                    if ($stmt->rowCount() > 0) {

                        $message = 'Admin berhasil dijadikan ADMIN DASHBOARD.';
                        $messageType = 'success';

                    } else {

                        $message = 'Admin tidak ditemukan atau role tidak dapat diubah.';
                        $messageType = 'error';
                    }
                }
            }

            /* =========================
               HAPUS ADMIN
            ========================= */

            elseif ($action === 'delete') {

                if ($id_admin === $currentAdminId) {

                    $message = 'Anda tidak dapat menghapus akun Anda sendiri.';
                    $messageType = 'error';

                } else {

                    $stmt = $conn->prepare("
                        SELECT
                            id_admin,
                            role,
                            status
                        FROM admin
                        WHERE id_admin = ?
                        LIMIT 1
                    ");

                    $stmt->execute([$id_admin]);

                    $adminToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$adminToDelete) {

                        $message = 'Admin tidak ditemukan.';
                        $messageType = 'error';

                    } else {

                        $stmt = $conn->prepare("
                            DELETE FROM admin
                            WHERE id_admin = ?
                        ");

                        $stmt->execute([$id_admin]);

                        if ($stmt->rowCount() > 0) {

                            $message = 'Admin berhasil dihapus.';
                            $messageType = 'success';

                        } else {

                            $message = 'Admin gagal dihapus.';
                            $messageType = 'error';
                        }
                    }
                }
            }
        }
    }
}

/* =========================
   JUMLAH ADMIN IT
========================= */

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM admin
    WHERE role = 'ADMIN IT'
      AND status = 'approved'
");

$jumlahAdminIT = (int)$stmt->fetchColumn();

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
        role,
        email_verified,
        created_at
    FROM admin
    WHERE status = 'pending'
    ORDER BY created_at DESC
");

$stmt->execute();

$pendingAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   AMBIL ADMIN APPROVED
========================= */

$stmt = $conn->prepare("
    SELECT
        id_admin,
        first_name,
        last_name,
        username,
        email,
        status,
        role,
        email_verified,
        created_at
    FROM admin
    WHERE status = 'approved'
    ORDER BY
        CASE
            WHEN role = 'ADMIN IT' THEN 0
            WHEN role = 'ADMIN DASHBOARD' THEN 1
            WHEN role = 'USER APP PORTAL' THEN 2
            ELSE 3
        END,
        created_at ASC
");

$stmt->execute();

$approvedAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            font-family: "Segoe UI", Arial, sans-serif;
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
           TOMBOL MENU
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
           OVERLAY
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
            margin-bottom: 25px;
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
            min-width: 1050px;
            table-layout: fixed;
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
            word-wrap: break-word;
        }

        tr:hover {
            background: #f8fbff;
        }

        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status.pending {
            background: #fff4d6;
            color: #9a6700;
        }

        .status.approved {
            background: #e8f7ee;
            color: #1f7a42;
        }

        /* =========================
           BADGE / ROLE
        ========================= */

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-admin-it {
            background: #e8f3ff;
            color: #005baa;
        }

        .badge-admin-dash {
            background: #f1f1f1;
            color: #555;
        }

        .badge-portal {
            background: #e8f7ee;
            color: #16803c;
        }

        /* =========================
           VERIFIKASI
        ========================= */

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
            align-items: center;
        }

        .actions form {
            margin: 0;
            display: inline-block;
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

        .btn-main {
            background: #005baa;
        }

        .btn-main:hover {
            background: #00498f;
        }

        .btn-delete {
            width: 38px;
            height: 38px;
            padding: 0;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            background: #dc3545;
            color: white;

            border-radius: 6px;
            transition: background 0.2s;
        }

        .btn-delete:hover {
            background: #bb2d3b;
        }

        .btn-delete svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        .btn-disabled {
            background: #adb5bd;
            cursor: not-allowed;
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

            .main {
                margin-left: 0;
                padding: 80px 20px 30px;
            }

            .main.sidebar-open {
                margin-left: 0;
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

            .menu-toggle {
                top: 12px;
                left: 12px;
                width: 42px;
                height: 42px;
            }

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
     TOMBOL MENU
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

        <?php if (($_SESSION['admin_role'] ?? '') === 'ADMIN IT'): ?>

            <li>
                <a href="kelola_admin.php" class="active">
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


<!-- =========================
     MAIN
========================= -->

<main class="main" id="mainContent">

    <div class="page-header">

        <h1>
            Kelola Admin
        </h1>

        <p>
            Kelola persetujuan akun administrator dan pengaturan hak akses administrator.
        </p>

    </div>


    <!-- =========================
         ALERT
    ========================= -->

    <?php if ($message !== ''): ?>

        <div class="alert <?= htmlspecialchars($messageType) ?>">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         PENGAJUAN AKUN
    ========================= -->

    <div class="card">

        <div class="card-title">
            Pengajuan Akun Administrator
        </div>


        <?php if (count($pendingAdmins) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th style="width: 60px;">No</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Verifikasi Email</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                            <th style="width: 180px;">Aksi</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($pendingAdmins as $index => $admin): ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>


                            <td>
                                <?php 
                                    $fullName = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
                                    echo htmlspecialchars($fullName !== '' ? $fullName : '-');
                                ?>
                            </td>


                            <td>
                                <strong>
                                    <?php 
                                        $uname = $admin['username'] ?? '';
                                        echo htmlspecialchars($uname !== '' ? $uname : '-');
                                    ?>
                                </strong>
                            </td>


                            <td>
                                <?= htmlspecialchars($admin['email'] ?? '-') ?>
                            </td>


                            <td>

                                <?php if ((int)($admin['email_verified'] ?? 0) === 1): ?>

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
                                <?= htmlspecialchars($admin['created_at'] ?? '-') ?>
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


    <!-- =========================
         DAFTAR ADMIN
    ========================= -->

    <div class="card">

        <div class="card-title">
            Daftar Administrator
        </div>


        <?php if (count($approvedAdmins) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th style="width: 60px;">No</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th style="width: 200px;">Aksi</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($approvedAdmins as $index => $admin): ?>

                        <tr>

                            <td>
                                <?= $index + 1 ?>
                            </td>


                            <td>
                                <?php 
                                    $fullName = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
                                    echo htmlspecialchars($fullName !== '' ? $fullName : '-');
                                ?>
                            </td>


                            <td>
                                <strong>
                                    <?php 
                                        $uname = $admin['username'] ?? '';
                                        echo htmlspecialchars($uname !== '' ? $uname : '-');
                                    ?>
                                </strong>

                                <?php if ((int)$admin['id_admin'] === $currentAdminId): ?>

                                    <div style="font-size:11px; color:#0066b3; font-weight:600; margin-top:2px;">
                                        Akun Anda
                                    </div>

                                <?php endif; ?>

                            </td>


                            <td>
                                <?= htmlspecialchars($admin['email'] ?? '-') ?>
                            </td>


                            <td>
                                <span class="badge 
                                    <?php 
                                        $r = $admin['role'] ?? '';
                                        if ($r === 'ADMIN IT') echo 'badge-admin-it';
                                        elseif ($r === 'ADMIN DASHBOARD') echo 'badge-admin-dash';
                                        elseif ($r === 'USER APP PORTAL') echo 'badge-portal';
                                        else echo 'badge-admin-dash';
                                    ?>">
                                    <?php 
                                        $displayRole = $r;
                                        if ($displayRole === 'ADMIN DASHBOARD') $displayRole = 'Admin Dash';
                                        elseif ($displayRole === 'USER APP PORTAL') $displayRole = 'User Portal';
                                        echo htmlspecialchars($displayRole !== '' ? $displayRole : '-');
                                    ?>
                                </span>
                            </td>


                            <td>

                                <?php if ((int)$admin['id_admin'] === $currentAdminId): ?>

                                    <span style="color:#888; font-size:13px; font-style:italic;">
                                        Tidak ada aksi
                                    </span>

                                <?php else: ?>

                                    <div class="actions">

                                        <?php if (($admin['role'] ?? '') === 'ADMIN DASHBOARD'): ?>

                                            <?php if ($jumlahAdminIT < 3): ?>

                                                <form
                                                    method="POST"
                                                    onsubmit="return confirm('Yakin ingin menjadikan admin ini sebagai ADMIN IT?');"
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
                                                        value="make_main"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn btn-main"
                                                    >
                                                        Jadikan IT
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                        <?php endif; ?>


                                        <!-- HAPUS ADMIN -->

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus akun ini? Data akan dihapus secara permanen.');"
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
                                                value="delete"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-delete"
                                                title="Hapus admin"
                                                aria-label="Hapus admin"
                                            >
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                                </svg>
                                            </button>

                                        </form>

                                    </div>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty">

                <div class="empty-icon">
                    —
                </div>

                <strong>
                    Belum ada administrator yang disetujui.
                </strong>

            </div>

        <?php endif; ?>

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
   TUTUP SIDEBAR
========================= */

document.getElementById('sidebarOverlay').addEventListener(
    'click',
    function () {

        document.getElementById('sidebar').classList.remove('sidebar-open');
        document.getElementById('mainContent').classList.remove('sidebar-open');
        document.getElementById('sidebarOverlay').classList.remove('show');

    }
);

</script>

</body>

</html>