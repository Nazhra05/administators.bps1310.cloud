<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Mailer;
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
     * Halaman Kelola Administrator
     */
    public function manageAdmin(): string
    {
        $this->authorizeAdminIt();
        $db = $this->db();

        $stmt = $db->query("
            SELECT id_admin, first_name, last_name, username, email, role, status, created_at
            FROM admin
            ORDER BY id_admin ASC
        ");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('admin/manage_admin', [
            'title'  => 'Kelola Admin',
            'admins' => $admins,
            'user'   => $this->user(),
        ]);
    }

    /**
     * Proses aksi status dan role admin (approve, reject, make_main, make_regular, delete)
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
                    SET status = 'approved', role = 'ADMIN DASHBOARD'
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
                    SET status = 'rejected'
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

                $stmtCount = $db->query("SELECT COUNT(*) FROM admin WHERE role = 'ADMIN IT' AND status = 'approved'");
                $countAdminIt = (int)$stmtCount->fetchColumn();
                if ($countAdminIt >= 3) {
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
                    flash('error', 'Anda tidak dapat menurunkan role akun Anda sendiri.');
                    break;
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
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (24 * 60 * 60));

        $stmtInsert = $db->prepare("
            INSERT INTO admin (
                first_name, last_name, username, email, password,
                status, role, email_verified, verification_token, verification_expires
            ) VALUES (
                NULL, NULL, NULL, ?, NULL,
                'pending', 'USER APP PORTAL', 0, ?, ?
            )
        ");
        $stmtInsert->execute([$email, $token, $expires]);
        $newUserId = (int)$db->lastInsertId();

        audit_log('invite', 'user', $newUserId, ['email' => $email]);

        $sent = Mailer::sendVerificationEmail($email, 'Pengguna Portal', $token, true);

        if ($sent) {
            flash('success', 'Akun user berhasil dibuat. Link verifikasi telah dikirim ke email tersebut.');
        } else {
            flash('warning', 'Akun berhasil dibuat, namun email verifikasi gagal dikirim.');
        }

        $this->redirect('kelola_user.php');
    }
}
