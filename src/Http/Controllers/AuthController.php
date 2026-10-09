<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Mailer;
use App\Support\Csrf;
use App\Support\Session;
use PDO;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function loginForm(): string
    {
        Session::start();
        $adminId = Session::get('admin_id');
        $adminRole = Session::get('admin_role');

        if (!empty($adminId) && in_array($adminRole, ['ADMIN IT', 'ADMIN DASHBOARD'], true)) {
            $this->redirect('/');
        }

        return $this->render('auth/login', [
            'title'          => 'Login Admin',
            'headerTitle'    => 'Login Administrator',
            'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
            'error'          => flash('error'),
        ], 'layouts/auth');
    }

    /**
     * Process login submission.
     */
    public function loginSubmit(): never
    {
        Session::start();

        if (isset($_POST['csrf_token']) && !Csrf::verify()) {
            flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan coba lagi.');
            $this->redirect('/login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            flash('error', 'Username dan password wajib diisi.');
            $this->redirect('/login');
        }

        $stmt = $this->db()->prepare("
            SELECT id_admin, first_name, last_name, username, email, password, status, role, email_verified
            FROM admin
            WHERE username = ? OR email = ?
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || empty($admin['password'])) {
            flash('error', 'Username atau password salah.');
            $this->redirect('/login');
        }

        $storedPassword = (string)$admin['password'];
        $passwordValid = false;

        // 1. Modern Bcrypt / Argon2 check
        if (password_verify($password, $storedPassword)) {
            $passwordValid = true;
        }

        // 2. Legacy SHA-256 check
        if (!$passwordValid && hash_equals($storedPassword, hash('sha256', $password))) {
            $passwordValid = true;
        }

        if (!$passwordValid) {
            flash('error', 'Username atau password salah.');
            $this->redirect('/login');
        }

        if ((int)$admin['email_verified'] !== 1) {
            flash('error', 'Email Anda belum diverifikasi. Silakan periksa kotak masuk email Anda.');
            $this->redirect('/login');
        }

        if ($admin['status'] === 'pending') {
            flash('error', 'Email sudah diverifikasi. Akun Anda masih menunggu persetujuan administrator.');
            $this->redirect('/login');
        }

        if ($admin['status'] === 'rejected') {
            flash('error', 'Pendaftaran akun Anda ditolak oleh administrator.');
            $this->redirect('/login');
        }

        if ($admin['status'] !== 'approved') {
            flash('error', 'Status akun tidak valid. Silakan hubungi administrator.');
            $this->redirect('/login');
        }

        if (!in_array($admin['role'], ['ADMIN IT', 'ADMIN DASHBOARD'], true)) {
            flash('error', 'Akun USER APP PORTAL tidak dapat login ke Dashboard Admin.');
            $this->redirect('/login');
        }

        // Auto-upgrade legacy hash to Bcrypt
        if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT) || !password_verify($password, $storedPassword)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upStmt = $this->db()->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
            $upStmt->execute([$newHash, (int)$admin['id_admin']]);
        }

        Session::regenerate();
        Session::set('admin_id', (int)$admin['id_admin']);
        Session::set('admin_username', $admin['username'] ?? '');
        Session::set('admin_role', $admin['role'] ?? '');
        Session::set('admin_status', $admin['status'] ?? '');
        Session::set('admin_email', $admin['email'] ?? '');

        header('Location: index.php');
        exit;
    }

    /**
     * Process logout.
     */
    public function logout(): never
    {
        Session::destroy();
        header('Location: login.php');
        exit;
    }

    /**
     * Show registration form.
     */
    public function registerForm(): string
    {
        return $this->render('auth/register', [
            'title'          => 'Daftar Akun',
            'headerTitle'    => 'Pendaftaran Akun',
            'headerSubtitle' => 'Buat akun untuk mengakses portal internal',
            'error'          => flash('error'),
            'success'        => flash('success'),
        ], 'layouts/auth');
    }

    /**
     * Process registration submission.
     */
    public function registerSubmit(): never
    {
        Session::start();

        if (!Csrf::verify()) {
            flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang formulir.');
            $this->redirect('/register');
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $agreeToTerms = isset($_POST['agree_to_terms']);

        if ($firstName === '' || $lastName === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '') {
            flash('error', 'Semua data wajib diisi.');
            $this->redirect('/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Format email tidak valid.');
            $this->redirect('/register');
        }

        if ($password !== $confirmPassword) {
            flash('error', 'Konfirmasi password tidak cocok.');
            $this->redirect('/register');
        }

        if (strlen($password) < 6) {
            flash('error', 'Password minimal 6 karakter.');
            $this->redirect('/register');
        }

        if (!$agreeToTerms) {
            flash('error', 'Anda harus menyetujui Syarat & Ketentuan.');
            $this->redirect('/register');
        }

        $stmt = $this->db()->prepare("SELECT id_admin FROM admin WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            flash('error', 'Username atau email sudah terdaftar.');
            $this->redirect('/register');
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (24 * 60 * 60));
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Per GATE 1: Public registration is for portal User
        $insertStmt = $this->db()->prepare("
            INSERT INTO admin (first_name, last_name, username, email, password, status, role, email_verified, verification_token, verification_expires)
            VALUES (?, ?, ?, ?, ?, 'pending', 'USER APP PORTAL', 0, ?, ?)
        ");
        $insertStmt->execute([$firstName, $lastName, $username, $email, $hashedPassword, $token, $expires]);

        Mailer::sendVerificationEmail($email, $firstName, $token, false);
        Mailer::sendAdminNotification([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'username'   => $username,
            'email'      => $email,
        ]);

        flash('success', 'Pendaftaran berhasil! Silakan periksa email Anda untuk melakukan verifikasi akun.');
        $this->redirect('/login');
    }

    /**
     * Verify email via link token.
     */
    public function verifyEmail(): string
    {
        $token = trim($_GET['token'] ?? '');
        if ($token === '') {
            return $this->render('auth/verify_result', [
                'title'   => 'Verifikasi Gagal',
                'success' => false,
                'message' => 'Token verifikasi tidak ditemukan.',
            ], 'layouts/auth');
        }

        $stmt = $this->db()->prepare("
            SELECT id_admin, verification_expires
            FROM admin
            WHERE verification_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return $this->render('auth/verify_result', [
                'title'   => 'Verifikasi Gagal',
                'success' => false,
                'message' => 'Token tidak valid atau sudah pernah digunakan sebelumnya.',
            ], 'layouts/auth');
        }

        if (strtotime($user['verification_expires']) < time()) {
            return $this->render('auth/verify_result', [
                'title'   => 'Token Kedaluwarsa',
                'success' => false,
                'message' => 'Masa berlaku tautan verifikasi telah berakhir. Silakan hubungi admin.',
            ], 'layouts/auth');
        }

        $upStmt = $this->db()->prepare("
            UPDATE admin
            SET email_verified = 1, verification_token = NULL, verification_expires = NULL
            WHERE id_admin = ?
        ");
        $upStmt->execute([(int)$user['id_admin']]);

        return $this->render('auth/verify_result', [
            'title'   => 'Email Terverifikasi',
            'success' => true,
            'message' => 'Email Anda berhasil diverifikasi. Akun Anda saat ini menunggu persetujuan administrator sebelum dapat digunakan.',
        ], 'layouts/auth');
    }

    /**
     * Verify invited portal user link.
     */
    public function verifyUser(): string
    {
        $token = trim($_GET['token'] ?? '');
        if ($token === '') {
            return $this->render('auth/verify_result', [
                'title'   => 'Token Tidak Ditemukan',
                'success' => false,
                'message' => 'Tautan verifikasi tidak lengkap.',
            ], 'layouts/auth');
        }

        $stmt = $this->db()->prepare("
            SELECT id_admin, verification_expires
            FROM admin
            WHERE verification_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || strtotime($user['verification_expires']) < time()) {
            return $this->render('auth/verify_result', [
                'title'   => 'Tautan Tidak Valid',
                'success' => false,
                'message' => 'Tautan aktivasi tidak valid atau telah kedaluwarsa.',
            ], 'layouts/auth');
        }

        // Safe redirection keeping token instead of ID (BUG-003 fixed)
        $this->redirect('/setup-user?token=' . urlencode($token));
    }

    /**
     * Show credential setup form for portal user.
     */
    public function setupUserForm(): string
    {
        $token = trim($_GET['token'] ?? '');
        if ($token === '') {
            http_response_code(400);
            return $this->render('auth/verify_result', [
                'title'   => 'Akses Ditolak',
                'success' => false,
                'message' => 'Token verifikasi wajib disertakan untuk mengakses halaman pengaturan akun ini.',
            ], 'layouts/auth');
        }

        $stmt = $this->db()->prepare("
            SELECT id_admin, email, verification_expires
            FROM admin
            WHERE verification_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || strtotime($user['verification_expires']) < time()) {
            http_response_code(400);
            return $this->render('auth/verify_result', [
                'title'   => 'Token Kedaluwarsa',
                'success' => false,
                'message' => 'Token aktivasi tidak valid atau telah kedaluwarsa.',
            ], 'layouts/auth');
        }

        return $this->render('auth/setup_user', [
            'title'          => 'Setup Akun Pengguna',
            'headerTitle'    => 'Lengkapi Akun Anda',
            'headerSubtitle' => 'Silakan tentukan username dan password untuk portal',
            'token'          => $token,
            'email'          => $user['email'],
            'error'          => flash('error'),
        ], 'layouts/auth');
    }

    /**
     * Process credential setup submission.
     */
    public function setupUserSubmit(): never
    {
        Session::start();

        if (!Csrf::verify()) {
            flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa.');
            $this->redirect('/setup-user?token=' . urlencode($_POST['token'] ?? ''));
        }

        $token = trim($_POST['token'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($token === '') {
            http_response_code(400);
            echo "Token aktivasi wajib disertakan.";
            exit;
        }

        if ($username === '' || $password === '') {
            flash('error', 'Username dan password wajib diisi.');
            $this->redirect('/setup-user?token=' . urlencode($token));
        }

        if (strlen($password) < 6) {
            flash('error', 'Password minimal 6 karakter.');
            $this->redirect('/setup-user?token=' . urlencode($token));
        }

        $stmt = $this->db()->prepare("
            SELECT id_admin, verification_expires
            FROM admin
            WHERE verification_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || strtotime($user['verification_expires']) < time()) {
            http_response_code(400);
            echo "Token aktivasi tidak valid atau telah kedaluwarsa.";
            exit;
        }

        // Check if username taken by someone else
        $uStmt = $this->db()->prepare("SELECT id_admin FROM admin WHERE username = ? AND id_admin != ? LIMIT 1");
        $uStmt->execute([$username, (int)$user['id_admin']]);
        if ($uStmt->fetch()) {
            flash('error', 'Username sudah digunakan, silakan pilih username lain.');
            $this->redirect('/setup-user?token=' . urlencode($token));
        }

        // Hash with Bcrypt (BUG-004 fix)
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $upStmt = $this->db()->prepare("
            UPDATE admin
            SET username = ?, password = ?, email_verified = 1, status = 'approved',
                verification_token = NULL, verification_expires = NULL
            WHERE id_admin = ?
        ");
        $upStmt->execute([$username, $hashedPassword, (int)$user['id_admin']]);

        flash('success', 'Akun Anda berhasil diaktifkan! Silakan gunakan kredensial baru Anda untuk login.');
        $this->redirect('/login');
    }
}
