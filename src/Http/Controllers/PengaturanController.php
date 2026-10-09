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

        $stmtSettings = $db->query("SELECT skey, svalue FROM settings");
        $settings = $stmtSettings ? $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR) : [];

        return $this->render('pengaturan/index', [
            'title'       => 'Pengaturan Sistem',
            'admin'       => $admin,
            'user'        => $user,
            'settings'    => $settings,
            'error_msg'   => '',
            'success_msg' => '',
        ]);
    }

    /**
     * Dispatcher untuk form submit di halaman pengaturan
     */
    public function postHandler(): string
    {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'update_settings') {
            return $this->saveSettings();
        }
        return $this->changePassword();
    }

    /**
     * Simpan pengaturan kontak bantuan dan portal (ADMIN IT saja)
     */
    public function saveSettings(): string
    {
        $user = $this->user();
        $db = $this->db();
        $adminId = (int)($user['id'] ?? 0);

        $stmt = $db->prepare("SELECT * FROM admin WHERE id_admin = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmtSettings = $db->query("SELECT skey, svalue FROM settings");
        $settings = $stmtSettings ? $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR) : [];

        if (($user['role'] ?? '') !== 'ADMIN IT') {
            http_response_code(403);
            return $this->render('errors/403', [
                'title'   => '403 - Akses Ditolak',
                'message' => 'Hanya Administrator IT yang dapat mengubah pengaturan sistem.',
            ]);
        }

        if (!csrf_verify()) {
            return $this->render('pengaturan/index', [
                'title'       => 'Pengaturan Sistem',
                'admin'       => $admin,
                'user'        => $user,
                'settings'    => $settings,
                'error_msg'   => 'Token keamanan CSRF tidak valid atau telah kedaluwarsa.',
                'success_msg' => '',
            ]);
        }

        $allowedKeys = [
            'app_name',
            'contact_email',
            'contact_whatsapp',
            'service_hours',
            'helpdesk_unit',
        ];

        $updatedKeys = [];
        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key])) {
                $val = trim((string)$_POST[$key]);
                $stmtCheck = $db->prepare("SELECT 1 FROM settings WHERE skey = ?");
                $stmtCheck->execute([$key]);
                if ($stmtCheck->fetchColumn()) {
                    $stmtUp = $db->prepare("UPDATE settings SET svalue = ? WHERE skey = ?");
                    $stmtUp->execute([$val, $key]);
                } else {
                    $stmtIn = $db->prepare("INSERT INTO settings (skey, svalue) VALUES (?, ?)");
                    $stmtIn->execute([$key, $val]);
                }
                $settings[$key] = $val;
                $updatedKeys[$key] = $val;
            }
        }

        audit_log('update', 'settings', null, [
            'updated_keys' => array_keys($updatedKeys),
        ]);

        return $this->render('pengaturan/index', [
            'title'       => 'Pengaturan Sistem',
            'admin'       => $admin,
            'user'        => $user,
            'settings'    => $settings,
            'error_msg'   => '',
            'success_msg' => 'Pengaturan kontak bantuan dan portal berhasil disimpan.',
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

        audit_log('change_password', 'admin', $adminId);

        return $this->render('pengaturan/index', [
            'title'       => 'Pengaturan Sistem',
            'admin'       => $admin,
            'error_msg'   => '',
            'success_msg' => 'Password berhasil diperbarui.',
        ]);
    }
}
