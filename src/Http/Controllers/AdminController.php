<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LoginThrottleService;
use App\Services\Mailer;
use App\Services\PasswordService;
use PDO;

class AdminController extends Controller
{
    /**
     * Pastikan hanya ADMIN IT yang bisa mengakses modul admin/user
     */
    private function authorizeAdminIt(): void
    {
        $user = $this->user();
        if (!$user || ($user['role'] ?? '') !== 'ADMIN IT') {
            $this->redirect('index.php');
        }
    }

    /**
     * Halaman Kelola Administrator & Pengguna
     */
    public function manageAdmin(): string
    {
        $this->authorizeAdminIt();
        $db = $this->db();

        $stmt = $db->query("
            SELECT a.id_admin, a.first_name, a.last_name, a.username, a.email, a.role, a.unit_id,
                   a.status, a.is_active, a.must_change_password, a.created_at, a.last_login_at,
                   u.nama AS unit_name
            FROM admin a
            LEFT JOIN units u ON a.unit_id = u.id
            ORDER BY a.id_admin ASC
        ");
        $admins = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmtUnits = $db->query("SELECT id, nama FROM units ORDER BY id ASC");
        $units = $stmtUnits ? $stmtUnits->fetchAll(PDO::FETCH_ASSOC) : [];

        return $this->render('admin/manage_admin', [
            'title'  => 'Kelola Admin',
            'admins' => $admins,
            'units'  => $units,
            'user'   => $this->user(),
        ]);
    }

    /**
     * Tambah Pengguna / Admin Baru secara langsung oleh Admin IT
     */
    public function storeUser(): void
    {
        $this->authorizeAdminIt();

        if (!csrf_verify()) {
            flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa.');
            $this->redirect('kelola_admin.php');
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $role = (string)($_POST['role'] ?? 'ADMIN DASHBOARD');
        $unitId = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;
        $passwordRaw = (string)($_POST['password'] ?? '');

        $allowedRoles = ['ADMIN IT', 'ADMIN DASHBOARD', 'USER APP PORTAL'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'ADMIN DASHBOARD';
        }

        if ($username === '' || !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            flash('error', 'Username wajib diisi 3-50 karakter alfanumerik/underscore.');
            $this->redirect('kelola_admin.php');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Format email tidak valid.');
            $this->redirect('kelola_admin.php');
        }

        $db = $this->db();

        $stmtCheck = $db->prepare("SELECT id_admin FROM admin WHERE username = ? OR email = ? LIMIT 1");
        $stmtCheck->execute([$username, $email]);
        if ($stmtCheck->fetch()) {
            flash('error', 'Username atau email sudah digunakan oleh akun lain.');
            $this->redirect('kelola_admin.php');
        }

        $isTempPassword = false;
        if ($passwordRaw === '') {
            $tempPassword = 'Bps!' . bin2hex(random_bytes(4));
            $passwordToHash = $tempPassword;
            $mustChangePassword = 1;
            $isTempPassword = true;
        } else {
            $policyErr = PasswordService::validatePolicy($passwordRaw, $username);
            if ($policyErr !== null) {
                flash('error', $policyErr);
                $this->redirect('kelola_admin.php');
            }
            $passwordToHash = $passwordRaw;
            $mustChangePassword = 0;
            $tempPassword = '';
        }

        $hash = PasswordService::hash($passwordToHash);

        $stmtInsert = $db->prepare("
            INSERT INTO admin (
                first_name, last_name, username, email, password,
                status, role, unit_id, is_active, must_change_password, email_verified
            ) VALUES (?, ?, ?, ?, ?, 'approved', ?, ?, 1, ?, 1)
        ");
        $stmtInsert->execute([
            $firstName ?: null,
            $lastName ?: null,
            $username,
            $email,
            $hash,
            $role,
            $unitId,
            $mustChangePassword,
        ]);
        $newId = (int)$db->lastInsertId();

        audit_log('create', 'admin', $newId, [
            'username' => $username,
            'email'    => $email,
            'role'     => $role,
            'unit_id'  => $unitId,
        ]);

        if ($isTempPassword) {
            flash('success', "Akun '{$username}' berhasil dibuat. Password sementara: <strong>{$tempPassword}</strong> (Wajib diganti saat login pertama).");
        } else {
            flash('success', "Akun '{$username}' berhasil dibuat dengan role '{$role}'.");
        }

        $this->redirect('kelola_admin.php');
    }

    /**
     * Hitung jumlah ADMIN IT yang sedang aktif
     */
    private function countActiveAdminIt(PDO $db): int
    {
        $stmt = $db->query("
            SELECT COUNT(*) 
            FROM admin 
            WHERE role = 'ADMIN IT' 
              AND status = 'approved' 
              AND (is_active = 1 OR is_active IS NULL)
        ");
        return (int)($stmt ? $stmt->fetchColumn() : 0);
    }

    /**
     * Proses aksi status, role, reset password, dan aktivasi admin
     */
    public function actionAdmin(): void
    {
        $this->authorizeAdminIt();

        if (!csrf_verify()) {
            flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa.');
            $this->redirect('kelola_admin.php');
        }

        $idAdmin = (int)($_POST['id_admin'] ?? 0);
        $action = (string)($_POST['action'] ?? '');
        $currentAdminId = (int)($this->user()['id'] ?? 0);

        if ($idAdmin <= 0) {
            flash('error', 'ID admin tidak valid.');
            $this->redirect('kelola_admin.php');
        }

        $db = $this->db();

        switch ($action) {
            case 'approve':
                $stmt = $db->prepare("
                    UPDATE admin
                    SET status = 'approved', role = 'ADMIN DASHBOARD', is_active = 1
                    WHERE id_admin = ? AND status = 'pending'
                ");
                $stmt->execute([$idAdmin]);
                if ($stmt->rowCount() > 0) {
                    audit_log('approve', 'admin', $idAdmin);
                    flash('success', 'Akun berhasil disetujui sebagai ADMIN DASHBOARD.');
                } else {
                    flash('error', 'Akun tidak ditemukan atau sudah diproses.');
                }
                break;

            case 'reject':
                $stmt = $db->prepare("
                    UPDATE admin
                    SET status = 'rejected', is_active = 0
                    WHERE id_admin = ? AND status = 'pending'
                ");
                $stmt->execute([$idAdmin]);
                if ($stmt->rowCount() > 0) {
                    audit_log('reject', 'admin', $idAdmin);
                    flash('success', 'Akun berhasil ditolak.');
                } else {
                    flash('error', 'Akun tidak ditemukan atau sudah diproses.');
                }
                break;

            case 'update_role_unit':
                $newRole = (string)($_POST['new_role'] ?? '');
                $newUnitId = !empty($_POST['new_unit_id']) ? (int)$_POST['new_unit_id'] : null;

                if (!in_array($newRole, ['ADMIN IT', 'ADMIN DASHBOARD', 'USER APP PORTAL'], true)) {
                    flash('error', 'Role yang dipilih tidak valid.');
                    break;
                }

                if ($idAdmin === $currentAdminId && $newRole !== 'ADMIN IT') {
                    if ($this->countActiveAdminIt($db) <= 1) {
                        flash('error', 'Tidak dapat menurunkan role akun Anda sendiri karena Anda adalah ADMIN IT terakhir yang aktif.');
                        break;
                    }
                }

                $stmtUpdate = $db->prepare("UPDATE admin SET role = ?, unit_id = ? WHERE id_admin = ?");
                $stmtUpdate->execute([$newRole, $newUnitId, $idAdmin]);
                audit_log('update_role_unit', 'admin', $idAdmin, ['role' => $newRole, 'unit_id' => $newUnitId]);
                flash('success', 'Role dan Unit kerja admin berhasil diperbarui.');
                break;

            case 'toggle_active':
                $targetActive = (int)($_POST['is_active'] ?? 0);

                if ($idAdmin === $currentAdminId && $targetActive === 0) {
                    if ($this->countActiveAdminIt($db) <= 1) {
                        flash('error', 'Tidak dapat menonaktifkan akun Anda sendiri karena Anda adalah ADMIN IT terakhir yang aktif.');
                        break;
                    }
                }

                $stmt = $db->prepare("UPDATE admin SET is_active = ? WHERE id_admin = ?");
                $stmt->execute([$targetActive, $idAdmin]);

                if ($targetActive === 0) {
                    try {
                        $db->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$idAdmin]);
                    } catch (\Throwable) {}
                    audit_log('deactivate', 'admin', $idAdmin);
                    flash('success', 'Akun berhasil dinonaktifkan.');
                } else {
                    audit_log('activate', 'admin', $idAdmin);
                    flash('success', 'Akun berhasil diaktifkan kembali.');
                }
                break;

            case 'reset_password':
                $tempPassword = 'Bps!' . bin2hex(random_bytes(4));
                $hash = PasswordService::hash($tempPassword);

                $stmt = $db->prepare("
                    UPDATE admin
                    SET password = ?, must_change_password = 1, failed_attempts = 0, locked_until = NULL
                    WHERE id_admin = ?
                ");
                $stmt->execute([$hash, $idAdmin]);

                try {
                    $db->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$idAdmin]);
                } catch (\Throwable) {}

                $stmtUser = $db->prepare("SELECT username FROM admin WHERE id_admin = ?");
                $stmtUser->execute([$idAdmin]);
                $uName = $stmtUser->fetchColumn() ?: "ID #{$idAdmin}";

                audit_log('reset_password', 'admin', $idAdmin);
                flash('success', "Password sementara untuk pengguna <strong>" . e((string)$uName) . "</strong> berhasil dibuat: <strong>{$tempPassword}</strong>. Pengguna wajib mengganti password saat login.");
                break;

            case 'make_main':
                if ($idAdmin === $currentAdminId) {
                    flash('error', 'Akun Anda sudah menjadi ADMIN IT.');
                    break;
                }
                $stmt = $db->prepare("SELECT role FROM admin WHERE id_admin = ? AND status = 'approved' LIMIT 1");
                $stmt->execute([$idAdmin]);
                $targetRole = $stmt->fetchColumn();

                if (!$targetRole || $targetRole !== 'ADMIN DASHBOARD') {
                    flash('error', 'Hanya ADMIN DASHBOARD yang dapat dijadikan ADMIN IT.');
                    break;
                }

                if ($this->countActiveAdminIt($db) >= 3) {
                    flash('error', 'Maksimal 3 ADMIN IT. Tidak dapat menambahkan ADMIN IT lagi.');
                    break;
                }

                $stmtUpdate = $db->prepare("
                    UPDATE admin SET role = 'ADMIN IT'
                    WHERE id_admin = ? AND status = 'approved' AND role = 'ADMIN DASHBOARD'
                ");
                $stmtUpdate->execute([$idAdmin]);
                audit_log('make_main', 'admin', $idAdmin);
                flash('success', 'Admin berhasil dijadikan ADMIN IT.');
                break;

            case 'make_regular':
                if ($idAdmin === $currentAdminId) {
                    if ($this->countActiveAdminIt($db) <= 1) {
                        flash('error', 'Tidak dapat menurunkan role akun Anda sendiri karena Anda adalah ADMIN IT terakhir yang aktif.');
                        break;
                    }
                }
                $stmt = $db->prepare("
                    UPDATE admin SET role = 'ADMIN DASHBOARD'
                    WHERE id_admin = ? AND status = 'approved' AND role = 'ADMIN IT'
                ");
                $stmt->execute([$idAdmin]);
                if ($stmt->rowCount() > 0) {
                    audit_log('make_regular', 'admin', $idAdmin);
                    flash('success', 'Admin berhasil dijadikan ADMIN DASHBOARD.');
                } else {
                    flash('error', 'Admin tidak ditemukan atau role tidak dapat diubah.');
                }
                break;

            case 'delete':
                if ($idAdmin === $currentAdminId) {
                    flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
                    break;
                }

                $stmtRole = $db->prepare("SELECT role, is_active FROM admin WHERE id_admin = ?");
                $stmtRole->execute([$idAdmin]);
                $targetAdmin = $stmtRole->fetch(PDO::FETCH_ASSOC);

                if ($targetAdmin && $targetAdmin['role'] === 'ADMIN IT' && (int)($targetAdmin['is_active'] ?? 1) === 1) {
                    if ($this->countActiveAdminIt($db) <= 1) {
                        flash('error', 'Tidak dapat menghapus ADMIN IT terakhir yang aktif.');
                        break;
                    }
                }

                try {
                    $db->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$idAdmin]);
                } catch (\Throwable) {}

                $stmtDelete = $db->prepare("DELETE FROM admin WHERE id_admin = ?");
                $stmtDelete->execute([$idAdmin]);
                if ($stmtDelete->rowCount() > 0) {
                    audit_log('delete', 'admin', $idAdmin);
                    flash('success', 'Admin berhasil dihapus.');
                } else {
                    flash('error', 'Admin gagal dihapus.');
                }
                break;

            default:
                flash('error', 'Aksi tidak valid.');
                break;
        }

        $this->redirect('kelola_admin.php');
    }

    /**
     * Halaman Kelola Pengguna Portal
     */
    public function manageUser(): string
    {
        $this->authorizeAdminIt();
        $db = $this->db();

        $stmt = $db->query("
            SELECT id_admin, username, email, status, role, email_verified, created_at
            FROM admin
            WHERE role = 'USER APP PORTAL'
            ORDER BY id_admin DESC
        ");
        $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return $this->render('admin/manage_user', [
            'title'       => 'Kelola User Portal',
            'users'       => $users,
            'error'       => '',
            'email_value' => '',
        ]);
    }

    /**
     * Proses undang user baru
     */
    public function inviteUser(): string
    {
        $this->authorizeAdminIt();

        $email = trim($_POST['email'] ?? '');
        $db = $this->db();

        $stmt = $db->query("
            SELECT id_admin, username, email, status, role, email_verified, created_at
            FROM admin
            WHERE role = 'USER APP PORTAL'
            ORDER BY id_admin DESC
        ");
        $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        if (!csrf_verify()) {
            return $this->render('admin/manage_user', [
                'title'       => 'Kelola User Portal',
                'users'       => $users,
                'error'       => 'Sesi keamanan tidak valid atau telah kedaluwarsa.',
                'email_value' => $email,
            ]);
        }

        if ($email === '') {
            return $this->render('admin/manage_user', [
                'title'       => 'Kelola User Portal',
                'users'       => $users,
                'error'       => 'Email wajib diisi.',
                'email_value' => '',
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('admin/manage_user', [
                'title'       => 'Kelola User Portal',
                'users'       => $users,
                'error'       => 'Format email tidak valid.',
                'email_value' => $email,
            ]);
        }

        $check = $db->prepare("SELECT id_admin FROM admin WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch(PDO::FETCH_ASSOC)) {
            return $this->render('admin/manage_user', [
                'title'       => 'Kelola User Portal',
                'users'       => $users,
                'error'       => 'Email tersebut sudah terdaftar di sistem.',
                'email_value' => $email,
            ]);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (LoginThrottleService::isEmailRateLimited($db, $email, $ip)) {
            return $this->render('admin/manage_user', [
                'title'       => 'Kelola User Portal',
                'users'       => $users,
                'error'       => 'Terlalu banyak permintaan pengiriman email ke alamat tersebut. Silakan coba lagi nanti.',
                'email_value' => $email,
            ]);
        }

        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 jam per Task 6.7

        $stmtInsert = $db->prepare("
            INSERT INTO admin (
                first_name, last_name, username, email, password,
                status, role, email_verified, verification_token, verification_expires
            ) VALUES (
                NULL, NULL, NULL, ?, NULL,
                'pending', 'USER APP PORTAL', 0, ?, ?
            )
        ");
        $stmtInsert->execute([$email, $hashedToken, $expires]);
        $newUserId = (int)$db->lastInsertId();

        LoginThrottleService::recordEmailVerificationAttempt($db, $email, $ip);
        audit_log('invite', 'user', $newUserId, ['email' => $email]);

        $sent = Mailer::sendVerificationEmail($email, 'Pengguna Portal', $rawToken, true);

        if ($sent) {
            flash('success', 'Akun user berhasil dibuat. Link verifikasi telah dikirim ke email tersebut.');
        } else {
            flash('warning', 'Akun berhasil dibuat, namun email verifikasi gagal dikirim.');
        }

        $this->redirect('kelola_user.php');
    }
}
