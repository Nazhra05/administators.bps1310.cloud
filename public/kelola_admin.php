<?php
/**
 * Halaman Kelola Administrator (Admin Management)
 * Secara fungsi, halaman ini mengelola undangan, aktivasi, dan akun role ADMIN.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../app/services/mail_service.php';
require_once __DIR__ . '/partials/icons.php';

/* =====================================================
   CEK LOGIN
===================================================== */
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$is_real_admin = is_admin_user();
$active_role = auth_active_role();

if ($active_role === 'USER') {
    header('Location: index.php');
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
$active_role = $_SESSION['active_role'] ?? $admin_role;
$active_role = (strtoupper(trim($active_role)) === 'USER') ? 'USER' : 'ADMIN';
$_SESSION['active_role'] = $active_role;

if ($active_role !== 'ADMIN' || $admin_role !== 'ADMIN') {
    header('Location: index.php');
    exit;
}

$admin_username = $currentAdmin['username'] ?? 'Admin';
$error = '';
$success = '';
$email_value = '';

/* =====================================================
   PROSES POST (TAMBAH / NONAKTIFKAN / AKTIFKAN / HAPUS ADMIN)
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $action = trim($_POST['action'] ?? '');

        /* =====================================================
           AKSI: NONAKTIFKAN ADMIN
        ===================================================== */
        if ($action === 'deactivate') {
            $targetId = (int) ($_POST['id_admin'] ?? 0);
            if ($targetId <= 0) {
                $error = 'ID admin tidak valid.';
            } elseif ($targetId === $currentAdminId) {
                $error = 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif digunakan.';
            } elseif ($targetId === 1) {
                $error = 'Akun Administrator Utama sistem dilindungi dan tidak dapat dinonaktifkan.';
            } else {
                $targetStmt = $conn->prepare("SELECT id_admin, username, email, role, status FROM admin WHERE id_admin = ? AND role = 'ADMIN' LIMIT 1");
                $targetStmt->execute([$targetId]);
                $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

                if (!$target) {
                    $error = 'Admin tidak ditemukan.';
                } elseif (strtolower(trim($target['username'] ?? '')) === 'admin') {
                    $error = 'Akun Administrator Utama sistem dilindungi dan tidak dapat dinonaktifkan.';
                } else {
                    $countStmt = $conn->query("SELECT COUNT(*) FROM admin WHERE role = 'ADMIN' AND status = 'approved'");
                    $activeCount = (int) $countStmt->fetchColumn();

                    if (($target['status'] ?? '') === 'approved' && $activeCount <= 1) {
                        $error = 'Tidak dapat menonaktifkan admin ini. Minimal harus ada 1 Administrator aktif di sistem.';
                    } else {
                        $stmtUpdate = $conn->prepare("UPDATE admin SET status = 'inactive' WHERE id_admin = ?");
                        $stmtUpdate->execute([$targetId]);
                        $displayName = $target['username'] ?: $target['email'];
                        $success = "Akun admin \"$displayName\" berhasil dinonaktifkan. Admin ini tidak akan dapat login ke dashboard sampai diaktifkan kembali.";
                        log_audit('DEACTIVATE_ADMIN', 'admin', $targetId, "Administrator menonaktifkan admin \"$displayName\".");
                    }
                }
            }
        }

        /* =====================================================
           AKSI: AKTIFKAN KEMBALI ADMIN
        ===================================================== */
        elseif ($action === 'activate') {
            $targetId = (int) ($_POST['id_admin'] ?? 0);
            if ($targetId <= 0) {
                $error = 'ID admin tidak valid.';
            } else {
                $targetStmt = $conn->prepare("SELECT id_admin, username, email, role, status FROM admin WHERE id_admin = ? AND role = 'ADMIN' LIMIT 1");
                $targetStmt->execute([$targetId]);
                $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

                if (!$target) {
                    $error = 'Admin tidak ditemukan.';
                } else {
                    $stmtUpdate = $conn->prepare("UPDATE admin SET status = 'approved' WHERE id_admin = ?");
                    $stmtUpdate->execute([$targetId]);
                    $displayName = $target['username'] ?: $target['email'];
                    $success = "Akun admin \"$displayName\" berhasil diaktifkan kembali. Admin dapat login ke dashboard kembali.";
                    log_audit('ACTIVATE_ADMIN', 'admin', $targetId, "Administrator mengaktifkan kembali admin \"$displayName\".");
                }
            }
        }

        /* =====================================================
           AKSI: HAPUS ADMIN (DENGAN PROTEKSI RELASI DATABASE)
        ===================================================== */
        elseif ($action === 'delete_admin' || $action === 'delete') {
            $targetId = (int) ($_POST['id_admin'] ?? 0);
            if ($targetId <= 0) {
                $error = 'ID admin tidak valid.';
            } elseif ($targetId === $currentAdminId) {
                $error = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.';
            } elseif ($targetId === 1) {
                $error = 'Akun Administrator Utama sistem dilindungi dan tidak dapat dihapus.';
            } else {
                $targetStmt = $conn->prepare("SELECT id_admin, username, email, role, status FROM admin WHERE id_admin = ? AND role = 'ADMIN' LIMIT 1");
                $targetStmt->execute([$targetId]);
                $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

                if (!$target) {
                    $error = 'Admin tidak ditemukan.';
                } elseif (strtolower(trim($target['username'] ?? '')) === 'admin') {
                    $error = 'Akun Administrator Utama sistem dilindungi dan tidak dapat dihapus.';
                } else {
                    $countStmt = $conn->query("SELECT COUNT(*) FROM admin WHERE role = 'ADMIN' AND status = 'approved'");
                    $activeCount = (int) $countStmt->fetchColumn();

                    if (($target['status'] ?? '') === 'approved' && $activeCount <= 1) {
                        $error = 'Tidak dapat menghapus admin ini. Minimal harus ada 1 Administrator aktif di sistem.';
                    } else {
                        // Periksa relasi database ke data layanan
                        $stmtLayanan = $conn->prepare("SELECT COUNT(*) FROM layanan WHERE created_by = ?");
                        $stmtLayanan->execute([$targetId]);
                        $layananCount = (int) $stmtLayanan->fetchColumn();

                        if ($layananCount > 0) {
                            // Alihkan data layanan ke Administrator Utama (ID 1) agar tidak hilang / error
                            $stmtReassign = $conn->prepare("UPDATE layanan SET created_by = 1 WHERE created_by = ?");
                            $stmtReassign->execute([$targetId]);
                        }

                        // Hapus riwayat favorit bila ada
                        try {
                            $stmtFav = $conn->prepare("DELETE FROM user_favorites WHERE id_user = ?");
                            $stmtFav->execute([$targetId]);
                        } catch (Exception $e) {}

                        // Hapus record admin
                        $stmtDelete = $conn->prepare("DELETE FROM admin WHERE id_admin = ?");
                        $stmtDelete->execute([$targetId]);

                        $displayName = $target['username'] ?: $target['email'];
                        $noteLayanan = ($layananCount > 0) ? " ($layananCount data layanan terkait telah dialihkan ke Admin Utama)" : "";
                        $success = "Akun admin \"$displayName\" berhasil dihapus dari sistem$noteLayanan.";
                        log_audit('DELETE_ADMIN', 'admin', $targetId, "Administrator menghapus akun admin \"$displayName\"$noteLayanan.");
                    }
                }
            }
        }

        /* =====================================================
           AKSI: DAFTARKAN ADMIN BARU
        ===================================================== */
        else {
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
                            'pending', 'ADMIN', 0, ?, ?
                        )
                    ");
                    $stmt->execute([$email_value, $verificationToken, $verificationExpires]);

                    $emailSent = sendAdminVerificationEmail($email_value, $verificationToken);

                    if ($emailSent) {
                        $success = 'Akun admin berhasil dibuat. Link verifikasi telah dikirim ke email tersebut.';
                        $email_value = '';
                    } else {
                        $error = 'Akun berhasil dibuat, tetapi email verifikasi gagal dikirim. Silakan periksa konfigurasi SMTP.';
                    }
                }
            }
        }
    }
}

/* =====================================================
   AMBIL DAFTAR ADMIN (SAFE QUERY)
===================================================== */
$users = [];
try {
    $stmtUsers = $conn->prepare("
        SELECT id_admin, username, email, status, role, email_verified, created_at
        FROM admin
        WHERE role = 'ADMIN'
        ORDER BY id_admin DESC
    ");
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    // Fallback jika kolom created_at belum ada di tabel database
    $stmtUsers = $conn->prepare("
        SELECT id_admin, username, email, status, role, email_verified
        FROM admin
        WHERE role = 'ADMIN'
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
    <title>Kelola Admin - BPS Solok Selatan</title>
    <link rel="stylesheet" href="css/style.css">
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
        .badge-inactive { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-rejected { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
        .action-self-badge { font-size: 11px; font-weight: 600; color: #0284c7; background: #e0f2fe; padding: 4px 8px; border-radius: 6px; border: 1px solid #bae6fd; white-space: nowrap; display: inline-block; }
        .action-protected-badge { font-size: 11px; font-weight: 600; color: #4338ca; background: #e0e7ff; padding: 4px 8px; border-radius: 6px; border: 1px solid #c7d2fe; white-space: nowrap; display: inline-block; }
        
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 11px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .btn-deactivate {
            background: #fff7ed;
            color: #c2410c;
            border-color: #ffedd5;
        }
        .btn-deactivate:hover {
            background: #ffedd5;
            color: #9a3412;
            border-color: #fed7aa;
        }

        .btn-activate {
            background: #ecfdf5;
            color: #047857;
            border-color: #d1fae5;
        }
        .btn-activate:hover {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }
        .btn-delete:hover {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fca5a5;
        }

        .empty-state { text-align: center; padding: 35px 20px; color: #777; font-size: 14px; }
        @media (max-width: 768px) {
            .main { padding: 80px 20px 30px; }
            .topbar { align-items: flex-start; flex-direction: column; gap: 8px; }
            .card, .user-list-card { padding: 22px; }
        }
    </style>
</head>
<body>
    <?php
$active_nav = 'kelola_admin';
include __DIR__ . '/partials/navbar.php';
?>

    <main class="main" id="mainContent">
        <?php
        $page_title = 'Kelola Admin';
        $page_subtitle = 'Kelola akun dan pendaftaran administrator sistem.';
        include __DIR__ . '/partials/topbar.php';
        ?>

        <div class="card">
            <h2>Daftarkan Admin</h2>
            <p class="card-description">
                Masukkan email administrator yang akan didaftarkan sebagai ADMIN.
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
                    <input type="email" id="email" name="email" value="<?= e($email_value); ?>" placeholder="Masukkan email admin" autocomplete="email" required>
                </div>
                <button type="submit" class="btn">Daftarkan</button>
            </form>
        </div>

        <div class="user-list-card">
            <h2>Daftar Admin</h2>
            <div class="table-wrapper">
                <?php if (!empty($users)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Verifikasi Email</th>
                                <th>Tanggal Daftar</th>
                                <th style="min-width: 190px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $index => $user): 
                                $userStatus = strtolower(trim($user['status'] ?? 'pending'));
                                $userId = (int) $user['id_admin'];
                                $isSelf = ($userId === $currentAdminId);
                                $isMainAdmin = ($userId === 1 || strtolower(trim($user['username'] ?? '')) === 'admin');
                                $adminDisplayName = $user['username'] ?: ($user['email'] ?? 'Admin');
                            ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td>
                                        <?php if (!empty($user['username'])): ?>
                                            <strong><?= e($user['username']); ?></strong>
                                            <?php if ($isMainAdmin): ?>
                                                <span style="font-size: 11px; background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">Utama</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:#999; font-style: italic;">Belum dibuat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($user['email'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-role">
                                            <?= htmlspecialchars((($user['role'] ?? '') === 'ADMIN') ? 'ADMIN' : 'USER') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($userStatus === 'approved'): ?>
                                            <span class="badge badge-approved">Aktif</span>
                                        <?php elseif ($userStatus === 'inactive' || $userStatus === 'nonaktif'): ?>
                                            <span class="badge badge-inactive">Nonaktif</span>
                                        <?php elseif ($userStatus === 'rejected'): ?>
                                            <span class="badge badge-rejected">Ditolak</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending"><?= e(ucfirst($userStatus)); ?></span>
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
                                    <td style="text-align: center;">
                                        <?php if ($isMainAdmin || $isSelf): ?>
                                            <div style="display: inline-flex; gap: 4px; align-items: center; justify-content: center; flex-wrap: wrap;">
                                                <?php if ($isMainAdmin): ?>
                                                    <span class="action-protected-badge" title="Administrator utama dilindungi dari penonaktifan dan penghapusan">Dilindungi</span>
                                                <?php endif; ?>
                                                <?php if ($isSelf): ?>
                                                    <span class="action-self-badge" title="Akun yang sedang Anda gunakan saat ini">Sesi Aktif</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                                                <!-- Tombol Nonaktifkan / Aktifkan -->
                                                <form method="POST" action="" style="margin: 0; display: inline;" onsubmit="return confirmAction(event, '<?= $userStatus === 'approved' ? 'deactivate' : 'activate'; ?>', '<?= e($adminDisplayName); ?>')">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="id_admin" value="<?= $userId; ?>">
                                                    <?php if ($userStatus === 'approved'): ?>
                                                        <input type="hidden" name="action" value="deactivate">
                                                        <button type="submit" class="btn-action btn-deactivate" title="Nonaktifkan Admin">
                                                            <?= render_icon('pause-circle', 'btn-icon', 14); ?> Nonaktifkan
                                                        </button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="action" value="activate">
                                                        <button type="submit" class="btn-action btn-activate" title="Aktifkan Kembali Admin">
                                                            <?= render_icon('play-circle', 'btn-icon', 14); ?> Aktifkan
                                                        </button>
                                                    <?php endif; ?>
                                                </form>

                                                <!-- Tombol Hapus -->
                                                <form method="POST" action="" style="margin: 0; display: inline;" onsubmit="return confirmAction(event, 'delete', '<?= e($adminDisplayName); ?>')">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="id_admin" value="<?= $userId; ?>">
                                                    <input type="hidden" name="action" value="delete_admin">
                                                    <button type="submit" class="btn-action btn-delete" title="Hapus Admin">
                                                        <?= render_icon('trash-2', 'btn-icon', 14); ?> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
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
        function confirmAction(e, actionType, adminName) {
            let msg = '';
            if (actionType === 'deactivate') {
                msg = 'Apakah Anda yakin ingin menonaktifkan akun admin "' + adminName + '"?\n\nAdmin yang dinonaktifkan tidak akan dapat login ke Dashboard hingga diaktifkan kembali.';
            } else if (actionType === 'activate') {
                msg = 'Apakah Anda yakin ingin mengaktifkan kembali akun admin "' + adminName + '"?\n\nAdmin ini akan dapat login kembali.';
            } else if (actionType === 'delete') {
                msg = 'PERINGATAN: Apakah Anda yakin ingin menghapus akun admin "' + adminName + '" secara permanen?\n\nSeluruh data layanan terkait akan dialihkan ke Admin Utama agar integritas data tetap terjaga. Tindakan ini tidak dapat dibatalkan!';
            }
            return confirm(msg);
        }
    </script>
</body>
</html>