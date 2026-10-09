<?php

require_once '../config/config.php';
require_once 'send_user_verification_email.php';

/* =====================================================
   CEK LOGIN
===================================================== */
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$currentAdminId = (int) $_SESSION['admin_id'];

$stmtAdmin = $conn->prepare("
    SELECT id_admin, username, role, status
    FROM admin
    WHERE id_admin = ?
    LIMIT 1
");
$stmtAdmin->execute([$currentAdminId]);
$currentAdmin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

if (!$currentAdmin) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$admin_role = $currentAdmin['role'] ?? '';
if ($admin_role !== 'ADMIN IT') {
    header('Location: index.php');
    exit;
}

$admin_username = $currentAdmin['username'] ?? 'Admin';
$error = '';
$success = '';
$email_value = '';

/* =====================================================
   PROSES DAFTARKAN USER
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $email_value = trim($_POST['email'] ?? '');

        if ($email_value === '') {
            $error = 'Email wajib diisi.';
        } elseif (!filter_var($email_value, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            $check = $conn->prepare("SELECT id_admin FROM admin WHERE email = ? LIMIT 1");
            $check->execute([$email_value]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $error = 'Email tersebut sudah terdaftar di sistem.';
            } else {
                $verificationToken   = bin2hex(random_bytes(32));
                $verificationExpires = date('Y-m-d H:i:s', time() + (24 * 60 * 60));

                $stmt = $conn->prepare("
                    INSERT INTO admin (
                        first_name, last_name, username, email, password,
                        status, role, email_verified, verification_token, verification_expires
                    ) VALUES (
                        NULL, NULL, NULL, ?, NULL,
                        'pending', 'USER APP PORTAL', 0, ?, ?
                    )
                ");
                $stmt->execute([$email_value, $verificationToken, $verificationExpires]);

                $emailSent = sendUserVerificationEmail($email_value, $verificationToken);

                if ($emailSent) {
                    $success = 'Akun user berhasil dibuat. Link verifikasi telah dikirim ke email tersebut.';
                    $email_value = '';
                } else {
                    $error = 'Akun berhasil dibuat, tetapi email verifikasi gagal dikirim. Silakan periksa konfigurasi SMTP.';
                }
            }
        }
    }
}

/* =====================================================
   AMBIL DAFTAR USER APP PORTAL (SAFE QUERY)
===================================================== */
$users = [];
try {
    $stmtUsers = $conn->prepare("
        SELECT id_admin, username, email, status, role, email_verified, created_at
        FROM admin
        WHERE role = 'USER APP PORTAL'
        ORDER BY id_admin DESC
    ");
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    // Fallback jika kolom created_at belum ada di tabel database
    $stmtUsers = $conn->prepare("
        SELECT id_admin, username, email, status, role, email_verified
        FROM admin
        WHERE role = 'USER APP PORTAL'
        ORDER BY id_admin DESC
    ");
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - BPS Solok Selatan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; background: #f5f7fb; color: #333; min-height: 100vh; }
        .sidebar { position: fixed; left: 0; top: 0; width: 240px; height: 100vh; background: #0066b3; color: #fff; padding-top: 25px; z-index: 9999; box-shadow: 2px 0 10px rgba(0,0,0,0.08); transform: translateX(-100%); transition: transform 0.3s ease; }
        .sidebar.sidebar-open { transform: translateX(0); }
        .sidebar .logo { font-size: 20px; font-weight: bold; margin-bottom: 40px; padding: 0 15px; }
        .sidebar ul { list-style: none; padding: 0 12px; }
        .sidebar ul li { margin-bottom: 8px; }
        .sidebar ul li a { display: block; color: #e8f3fb; text-decoration: none; padding: 12px 15px; border-radius: 8px; font-size: 16px; transition: 0.2s ease; }
        .sidebar ul li a:hover { background: rgba(255,255,255,0.15); color: white; }
        .sidebar ul li a.active { background: white; color: #0066b3; font-weight: bold; }
        .menu-toggle { display: flex; align-items: center; justify-content: center; position: fixed; top: 15px; left: 15px; width: 44px; height: 44px; border: none; border-radius: 8px; background: #0066b3; color: white; font-size: 23px; cursor: pointer; z-index: 10001; box-shadow: 0 3px 10px rgba(0,0,0,0.15); }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 9998; }
        .sidebar-overlay.show { display: block; }
        .main { margin-left: 0; min-height: 100vh; padding: 30px; transition: margin-left 0.3s ease; }
        .main.sidebar-open { margin-left: 240px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .topbar h1 { font-size: 26px; color: #183b7a; }
        .admin-info { font-size: 14px; color: #666; }
        .card { background: #ffffff; border-radius: 12px; padding: 30px; max-width: 700px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .card h2 { color: #183b7a; font-size: 20px; margin-bottom: 8px; }
        .card-description { color: #777; font-size: 14px; line-height: 1.6; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; color: #444; }
        .form-group input { width: 100%; padding: 12px 14px; border: 1px solid #d8dee8; border-radius: 7px; font-size: 14px; outline: none; transition: 0.2s; }
        .form-group input:focus { border-color: #0066b3; box-shadow: 0 0 0 3px rgba(0,102,179,0.08); }
        .btn { border: none; background: #0066b3; color: #ffffff; padding: 12px 22px; border-radius: 7px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn:hover { background: #005596; }
        .alert { padding: 13px 15px; border-radius: 7px; margin-bottom: 20px; font-size: 14px; line-height: 1.5; }
        .alert-error { background: #ffe8e8; color: #c62828; border: 1px solid #f2b8b8; }
        .alert-success { background: #e8f7ee; color: #18794e; border: 1px solid #b8e6cc; }
        .user-list-card { background: #ffffff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        .user-list-card h2 { color: #183b7a; font-size: 20px; margin-bottom: 20px; }
        .table-wrapper { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 850px; }
        table th { background: #f4f7fb; color: #444; font-size: 13px; font-weight: 600; text-align: left; padding: 13px 12px; border-bottom: 1px solid #e1e5eb; white-space: nowrap; }
        table td { padding: 13px 12px; font-size: 13px; color: #555; border-bottom: 1px solid #edf0f4; vertical-align: middle; }
        table tbody tr:hover { background: #fafbfd; }
        .badge { display: inline-block; padding: 5px 9px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .badge-approved { background: #e8f7ee; color: #18794e; }
        .badge-pending { background: #fff4df; color: #a66300; }
        .badge-verified { background: #e8f7ee; color: #18794e; }
        .badge-unverified { background: #ffe8e8; color: #c62828; }
        .badge-role { background: #e6f0fa; color: #0066b3; }
        .empty-state { text-align: center; padding: 35px 20px; color: #777; font-size: 14px; }
        @media (max-width: 768px) {
            .main { padding: 80px 20px 30px; }
            .topbar { align-items: flex-start; flex-direction: column; gap: 8px; }
            .card, .user-list-card { padding: 22px; }
        }
    </style>
</head>
<body>
    <button type="button" class="menu-toggle" id="menuToggle" onclick="openSidebar()" aria-label="Buka menu">☰</button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="logo">Dashboard Admin</div>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="layanan.php">Kelola Layanan</a></li>
            <li><a href="kategori.php">Kategori</a></li>
            <?php if ($admin_role === 'ADMIN IT'): ?>
                <li><a href="kelola_admin.php">Kelola Admin</a></li>
                <li><a href="kelola_user.php" class="active">Kelola User</a></li>
            <?php endif; ?>
            <li><a href="pengaturan.php">Pengaturan</a></li>
        </ul>
    </aside>

    <main class="main" id="mainContent">
        <div class="topbar">
            <h1>Kelola User</h1>
            <div class="admin-info">
                Login sebagai: <strong><?= e($admin_username); ?></strong>
            </div>
        </div>

        <div class="card">
            <h2>Daftarkan User</h2>
            <p class="card-description">
                Masukkan email pengguna yang akan didaftarkan sebagai USER APP PORTAL.
                Link verifikasi akan dikirim ke email tersebut.
            </p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= e($error); ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?= e($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrf_field(); ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($email_value); ?>" placeholder="Masukkan email user" autocomplete="email" required>
                </div>
                <button type="submit" class="btn">Daftarkan</button>
            </form>
        </div>

        <div class="user-list-card">
            <h2>Daftar User</h2>
            <div class="table-wrapper">
                <?php if (!empty($users)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Verifikasi Email</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $index => $user): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td>
                                        <?php if (!empty($user['username'])): ?>
                                            <?= e($user['username']); ?>
                                        <?php else: ?>
                                            <span style="color:#999;">Belum dibuat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($user['email'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-role">
                                            <?php 
                                                $role_display = $user['role'] ?? '-';
                                                if ($role_display === 'USER APP PORTAL') $role_display = 'User Portal';
                                                elseif ($role_display === 'ADMIN DASHBOARD') $role_display = 'Admin Dash';
                                                echo e($role_display); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php $status = $user['status'] ?? 'pending'; ?>
                                        <?php if ($status === 'approved'): ?>
                                            <span class="badge badge-approved">Approved</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending"><?= e(ucfirst($status)); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) ($user['email_verified'] ?? 0) === 1): ?>
                                            <span class="badge badge-verified">Terverifikasi</span>
                                        <?php else: ?>
                                            <span class="badge badge-unverified">Belum Verifikasi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if (!empty($user['created_at'])) {
                                            echo e(date('d-m-Y H:i', strtotime($user['created_at'])));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">Belum ada user yang terdaftar.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function openSidebar() {
            document.getElementById('sidebar').classList.add('sidebar-open');
            document.getElementById('mainContent').classList.add('sidebar-open');
            if (window.innerWidth <= 768) {
                document.getElementById('sidebarOverlay').classList.add('show');
            }
        }

        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('sidebar-open');
            document.getElementById('mainContent').classList.remove('sidebar-open');
            this.classList.remove('show');
        });
    </script>
</body>
</html>