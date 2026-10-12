<?php
/**
 * Halaman Kelola Pengguna (User Management)
 * Secara fungsi, halaman ini mengelola persetujuan pendaftaran, aktivasi, dan akun role USER.
 */

require_once '../config/config.php';
require_once '../config/auth.php';

/* =========================
   CEK LOGIN ADMIN
========================= */

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$is_real_admin = is_admin_user();
$active_role = auth_active_role();

/* =========================
   CEK AKSES (HANYA ROLE ADMIN)
========================= */

if ($active_role !== 'ADMIN') {
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
            'delete',
            'deactivate',
            'activate'
        ], true)) {

            $message = 'Aksi tidak valid.';
            $messageType = 'error';

        } else {

            /* =========================
               APPROVE
               DEFAULT = USER
            ========================= */

            if ($action === 'approve') {

                $stmtUser = $conn->prepare("
                    SELECT id_admin, username, email, first_name, last_name
                    FROM admin
                    WHERE id_admin = ?
                      AND status = 'pending'
                    LIMIT 1
                ");
                $stmtUser->execute([$id_admin]);
                $pendingUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

                $stmt = $conn->prepare("
                    UPDATE admin
                    SET
                        status = 'approved',
                        role = 'USER'
                    WHERE id_admin = ?
                      AND status = 'pending'
                ");

                $stmt->execute([$id_admin]);

                if ($stmt->rowCount() > 0) {

                    $message = 'Akun berhasil disetujui sebagai USER.';
                    $messageType = 'success';

                    log_audit('APPROVE_USER', 'admin', $id_admin, 'Administrator menyetujui akun ' . ($pendingUser['username'] ?? '') . ' sebagai USER.');

                    if (!empty($pendingUser['email'])) {
                        try {
                            require_once __DIR__ . '/../app/services/mail_service.php';
                            $fullName = trim(($pendingUser['first_name'] ?? '') . ' ' . ($pendingUser['last_name'] ?? ''));
                            if (empty($fullName)) {
                                $fullName = $pendingUser['username'] ?? 'Pegawai';
                            }
                            sendAccountApprovedNotification($pendingUser['email'], $fullName, $pendingUser['username'] ?? '');
                        } catch (Throwable $e) {
                            error_log('Gagal mengirim email notifikasi persetujuan: ' . $e->getMessage());
                        }
                    }

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
                    log_audit('REJECT_USER', 'admin', $id_admin, 'Administrator menolak pendaftaran akun.');

                } else {

                    $message = 'Akun tidak ditemukan atau sudah diproses.';
                    $messageType = 'error';
                }
            }

            /* =========================
               JADIKAN ADMIN
            ========================= */

            elseif ($action === 'make_main') {

                if ($id_admin === $currentAdminId) {

                    $message = 'Akun Anda sudah menjadi ADMIN.';
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

                        $message = 'Akun tidak ditemukan atau belum disetujui.';
                        $messageType = 'error';

                    } elseif ($targetRole !== 'USER') {

                        $message = 'Hanya akun USER yang dapat dijadikan ADMIN.';
                        $messageType = 'error';

                    } else {

                        /* =========================
                           HITUNG ADMIN
                        ========================= */

                        $stmt = $conn->query("
                            SELECT COUNT(*)
                            FROM admin
                            WHERE role = 'ADMIN'
                              AND status = 'approved'
                        ");

                        $jumlahAdmin = (int)$stmt->fetchColumn();

                        if ($jumlahAdmin >= 3) {

                            $message = 'Maksimal 3 ADMIN. Tidak dapat menambahkan ADMIN lagi.';
                            $messageType = 'error';

                        } else {

                            $stmt = $conn->prepare("
                                UPDATE admin
                                SET role = 'ADMIN'
                                WHERE id_admin = ?
                                  AND status = 'approved'
                                  AND role = 'USER'
                            ");

                            $stmt->execute([$id_admin]);

                            if ($stmt->rowCount() > 0) {

                                $message = 'Akun berhasil dijadikan ADMIN.';
                                $messageType = 'success';
                                log_audit('PROMOTE_ADMIN', 'admin', $id_admin, 'Administrator mempromosikan user menjadi role ADMIN.');

                            } else {

                                $message = 'Akun tidak ditemukan atau tidak dapat diubah.';
                                $messageType = 'error';
                            }
                        }
                    }
                }
            }

            /* =========================
               JADIKAN USER
            ========================= */

            elseif ($action === 'make_regular') {

                if ($id_admin === $currentAdminId) {

                    $message = 'Anda tidak dapat menurunkan role akun Anda sendiri.';
                    $messageType = 'error';

                } else {

                    $stmt = $conn->prepare("
                        UPDATE admin
                        SET role = 'USER'
                        WHERE id_admin = ?
                          AND status = 'approved'
                          AND role = 'ADMIN'
                    ");

                    $stmt->execute([$id_admin]);

                    if ($stmt->rowCount() > 0) {

                        $message = 'Akun berhasil dijadikan USER.';
                        $messageType = 'success';
                        log_audit('DEMOTE_USER', 'admin', $id_admin, 'Administrator mengubah role akun menjadi USER.');

                    } else {

                        $message = 'Akun tidak ditemukan atau role tidak dapat diubah.';
                        $messageType = 'error';
                    }
                }
            }

            /* =========================
               NONAKTIFKAN AKUN
            ========================= */
            elseif ($action === 'deactivate') {
                if ($id_admin === $currentAdminId) {
                    $message = 'Anda tidak dapat menonaktifkan akun Anda sendiri.';
                    $messageType = 'error';
                } elseif ($id_admin === 1) {
                    $message = 'Akun Administrator Utama dilindungi dan tidak dapat dinonaktifkan.';
                    $messageType = 'error';
                } else {
                    $stmt = $conn->prepare("UPDATE admin SET status = 'inactive' WHERE id_admin = ?");
                    $stmt->execute([$id_admin]);
                    $message = 'Akun berhasil dinonaktifkan.';
                    $messageType = 'success';
                    log_audit('DEACTIVATE_USER', 'admin', $id_admin, 'Administrator menonaktifkan akun pengguna.');
                }
            }

            /* =========================
               AKTIFKAN KEMBALI AKUN
            ========================= */
            elseif ($action === 'activate') {
                $stmt = $conn->prepare("UPDATE admin SET status = 'approved' WHERE id_admin = ?");
                $stmt->execute([$id_admin]);
                $message = 'Akun berhasil diaktifkan kembali.';
                $messageType = 'success';
                log_audit('ACTIVATE_USER', 'admin', $id_admin, 'Administrator mengaktifkan kembali akun pengguna.');
            }

            /* =========================
               HAPUS ADMIN
            ========================= */
            elseif ($action === 'delete') {

                if ($id_admin === $currentAdminId) {

                    $message = 'Anda tidak dapat menghapus akun Anda sendiri.';
                    $messageType = 'error';

                } elseif ($id_admin === 1) {

                    $message = 'Akun Administrator Utama dilindungi dan tidak dapat dihapus.';
                    $messageType = 'error';

                } else {

                    $stmt = $conn->prepare("
                        SELECT
                            id_admin,
                            username,
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

                    } elseif (strtolower(trim($adminToDelete['username'] ?? '')) === 'admin') {

                        $message = 'Akun Administrator Utama dilindungi dan tidak dapat dihapus.';
                        $messageType = 'error';

                    } else {

                        // Lindungi integritas layanan
                        $conn->prepare("UPDATE layanan SET created_by = 1 WHERE created_by = ?")->execute([$id_admin]);
                        try {
                            $conn->prepare("DELETE FROM user_favorites WHERE id_user = ?")->execute([$id_admin]);
                        } catch (Exception $e) {}

                        $stmt = $conn->prepare("
                            DELETE FROM admin
                            WHERE id_admin = ?
                        ");

                        $stmt->execute([$id_admin]);

                        if ($stmt->rowCount() > 0) {

                            $message = 'Admin berhasil dihapus.';
                            $messageType = 'success';
                            log_audit('DELETE_USER', 'admin', $id_admin, 'Administrator menghapus akun pengguna (' . ($adminToDelete['username'] ?? '') . ').');

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
   JUMLAH ADMIN
========================= */

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM admin
    WHERE role = 'ADMIN'
      AND status = 'approved'
");

$jumlahAdmin = (int)$stmt->fetchColumn();

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
   AMBIL USER APPROVED (HANYA ROLE USER)
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
      AND role = 'USER'
    ORDER BY created_at DESC
");

$stmt->execute();

$approvedAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kelola User - Dashboard Admin BPS Solok Selatan</title>
    <link rel="stylesheet" href="css/style.css">

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

        .badge-admin {
            background: #e8f3ff;
            color: #005baa;
        }

        .badge-user {
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
     SIDEBAR OVERLAY
========================= -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- =========================
     SIDEBAR
========================= -->

<?php
$active_nav = 'kelola_user';
include __DIR__ . '/partials/navbar.php';
?>


<!-- =========================
     MAIN
========================= -->

<main class="main" id="mainContent">

    <?php
    $page_title = 'Kelola User';
    $page_subtitle = 'Kelola persetujuan akun pengguna dan daftar user terdaftar.';
    include __DIR__ . '/partials/topbar.php';
    ?>


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
            Pengajuan Akun User
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
         DAFTAR USER
    ========================= -->

    <div class="card">

        <div class="card-title">
            Daftar Pengguna (User)
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
                                <span class="badge <?= (($admin['role'] ?? '') === 'ADMIN') ? 'badge-admin' : 'badge-user'; ?>">
                                    <?= htmlspecialchars((($admin['role'] ?? '') === 'ADMIN') ? 'ADMIN' : 'USER') ?>
                                </span>
                            </td>


                            <td>

                                <?php if ((int)$admin['id_admin'] === $currentAdminId): ?>

                                    <span style="color:#888; font-size:13px; font-style:italic;">
                                        Akun Anda
                                    </span>

                                <?php else: ?>

                                    <div class="actions">

                                        <?php if (($admin['role'] ?? '') === 'USER'): ?>

                                            <?php if ($jumlahAdmin < 3): ?>

                                                <form
                                                    method="POST"
                                                    onsubmit="return confirm('Yakin ingin menjadikan akun ini sebagai ADMIN?');"
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
                                                        Jadikan ADMIN
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                        <?php elseif (($admin['role'] ?? '') === 'ADMIN'): ?>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menjadikan admin ini sebagai USER?');"
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
                                                    value="make_regular"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-regular"
                                                    style="background: #e6f4ea; color: #137333; border: 1px solid #ceead6; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 6px; cursor: pointer;"
                                                >
                                                    Jadikan USER
                                                </button>

                                            </form>

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
                    Belum ada user yang disetujui.
                </strong>

            </div>

        <?php endif; ?>

    </div>

</main>




</body>

</html>