<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PasswordService;
use PDO;

class PengaturanController extends Controller
{
    /**
     * Halaman Pengaturan Sistem & Akun
     */
    public function index(): string
    {
        $user = $this->user();
        $db = $this->db();

        $stmt = $db->prepare("SELECT * FROM admin WHERE id_admin = ? LIMIT 1");
        $stmt->execute([(int)($user['id'] ?? 0)]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->render('pengaturan/index', [
            'title'       => 'Pengaturan Sistem',
            'admin'       => $admin,
            'error_msg'   => '',
            'success_msg' => '',
        ]);
    }

    /**
     * Handler ubah password admin
     */
    public function changePassword(): string
    {
        $user = $this->user();
        $db = $this->db();
        $adminId = (int)($user['id'] ?? 0);

        $stmt = $db->prepare("SELECT * FROM admin WHERE id_admin = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!csrf_verify()) {
            return $this->render('pengaturan/index', [
                'title'       => 'Pengaturan Sistem',
                'admin'       => $admin,
                'error_msg'   => 'Token keamanan tidak valid atau telah kedaluwarsa.',
                'success_msg' => '',
            ]);
        }

        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            return $this->render('pengaturan/index', [
                'title'       => 'Pengaturan Sistem',
                'admin'       => $admin,
                'error_msg'   => 'Semua kolom password wajib diisi.',
                'success_msg' => '',
            ]);
        }

        $policyError = PasswordService::validatePolicy($newPassword, $admin['username'] ?? null);
        if ($policyError !== null) {
            return $this->render('pengaturan/index', [
                'title'       => 'Pengaturan Sistem',
                'admin'       => $admin,
                'error_msg'   => $policyError,
                'success_msg' => '',
            ]);
        }

        if ($newPassword !== $confirmPassword) {
            return $this->render('pengaturan/index', [
                'title'       => 'Pengaturan Sistem',
                'admin'       => $admin,
                'error_msg'   => 'Konfirmasi password baru tidak cocok.',
                'success_msg' => '',
            ]);
        }

        if (!$admin) {
            return $this->render('pengaturan/index', [
                'title'       => 'Pengaturan Sistem',
                'admin'       => $admin,
                'error_msg'   => 'Pengguna tidak ditemukan.',
                'success_msg' => '',
            ]);
        }

        $valid = PasswordService::verify($currentPassword, (string)$admin['password'], $adminId, $db);

        if (!$valid) {
            return $this->render('pengaturan/index', [
                'title'       => 'Pengaturan Sistem',
                'admin'       => $admin,
                'error_msg'   => 'Password saat ini salah.',
                'success_msg' => '',
            ]);
        }

        $newHash = PasswordService::hash($newPassword);
        $stmtUpdate = $db->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
        $stmtUpdate->execute([$newHash, $adminId]);

        return $this->render('pengaturan/index', [
            'title'       => 'Pengaturan Sistem',
            'admin'       => $admin,
            'error_msg'   => '',
            'success_msg' => 'Password berhasil diperbarui.',
        ]);
    }
}
