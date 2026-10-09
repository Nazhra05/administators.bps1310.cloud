<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LoginThrottleService;
use App\Services\Mailer;
use App\Services\PasswordService;
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
    public function loginSubmit(): mixed
    {
        Session::start();

        if (isset($_POST['csrf_token']) && !Csrf::verify()) {
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan coba lagi.',
            ], 'layouts/auth');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (LoginThrottleService::isIpThrottled($this->db(), $ip)) {
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Terlalu banyak percobaan login gagal dari alamat IP Anda. Silakan coba lagi nanti.',
            ], 'layouts/auth');
        }

        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Username dan password wajib diisi.',
            ], 'layouts/auth');
        }

        $stmt = $this->db()->prepare("
            SELECT id_admin, first_name, last_name, username, email, password, status, role, email_verified, failed_attempts, locked_until, unit_id
            FROM admin
            WHERE username = ? OR email = ?
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || empty($admin['password'])) {
            LoginThrottleService::handleFailedLogin($this->db(), null, $ip, $username);
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Username atau password salah.',
            ], 'layouts/auth');
        }

        if (LoginThrottleService::isAccountLocked($admin)) {
            PasswordService::dummyVerify();
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Akun Anda terkunci sementara karena terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam 10 menit.',
            ], 'layouts/auth');
        }

        $passwordValid = PasswordService::verify(
            $password,
            (string)$admin['password'],
            (int)$admin['id_admin'],
            $this->db()
        );

        if (!$passwordValid) {
            LoginThrottleService::handleFailedLogin($this->db(), $admin, $ip, $username);
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Username atau password salah.',
            ], 'layouts/auth');
        }

        if ((int)$admin['email_verified'] !== 1) {
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Email Anda belum diverifikasi. Silakan periksa kotak masuk email Anda.',
            ], 'layouts/auth');
        }

        if ($admin['status'] === 'pending') {
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Email sudah diverifikasi. Akun Anda masih menunggu persetujuan administrator.',
            ], 'layouts/auth');
        }

        if ($admin['status'] === 'rejected') {
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Pendaftaran akun Anda ditolak oleh administrator.',
            ], 'layouts/auth');
        }

        if ($admin['status'] !== 'approved') {
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Status akun tidak valid. Silakan hubungi administrator.',
            ], 'layouts/auth');
        }

        if (!in_array($admin['role'], ['ADMIN IT', 'ADMIN DASHBOARD'], true)) {
            return $this->render('auth/login', [
                'title'          => 'Login Admin',
                'headerTitle'    => 'Login Administrator',
                'headerSubtitle' => 'Silakan masuk ke akun admin Anda',
                'error'          => 'Akun USER APP PORTAL tidak dapat login ke Dashboard Admin.',
            ], 'layouts/auth');
        }

        LoginThrottleService::handleSuccessfulLogin($this->db(), (int)$admin['id_admin'], $ip, $username);

        Session::regenerate();
        Session::set('admin_id', (int)$admin['id_admin']);
        Session::set('admin_username', $admin['username'] ?? '');
        Session::set('admin_role', $admin['role'] ?? '');
        Session::set('admin_status', $admin['status'] ?? '');
        Session::set('admin_email', $admin['email'] ?? '');
        Session::set('admin_unit_id', isset($admin['unit_id']) ? (int)$admin['unit_id'] : null);

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
    public function registerSubmit(): mixed
    {
        Session::start();

        if (!Csrf::verify()) {
            return $this->render('auth/register', [
                'title'          => 'Daftar Akun',
                'headerTitle'    => 'Pendaftaran Akun',
                'headerSubtitle' => 'Buat akun untuk mengakses portal internal',
                'error'          => 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang formulir.',
            ], 'layouts/auth');
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $agreeToTerms = isset($_POST['agree_to_terms']);

        if ($firstName === '' || $lastName === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '') {
            return $this->render('auth/register', [
                'title'          => 'Daftar Akun',
                'headerTitle'    => 'Pendaftaran Akun',
                'headerSubtitle' => 'Buat akun untuk mengakses portal internal',
                'error'          => 'Semua data wajib diisi.',
            ], 'layouts/auth');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('auth/register', [
                'title'          => 'Daftar Akun',
                'headerTitle'    => 'Pendaftaran Akun',
                'headerSubtitle' => 'Buat akun untuk mengakses portal internal',
                'error'          => 'Format email tidak valid.',
            ], 'layouts/auth');
        }

        if ($password !== $confirmPassword) {
            return $this->render('auth/register', [
                'title'          => 'Daftar Akun',
                'headerTitle'    => 'Pendaftaran Akun',
                'headerSubtitle' => 'Buat akun untuk mengakses portal internal',
                'error'          => 'Konfirmasi password tidak cocok.',
            ], 'layouts/auth');
        }

        if (strlen($password) < 6) {
            return $this->render('auth/register', [
                'title'          => 'Daftar Akun',
                'headerTitle'    => 'Pendaftaran Akun',
                'headerSubtitle' => 'Buat akun untuk mengakses portal internal',
                'error'          => 'Password minimal 6 karakter.',
            ], 'layouts/auth');
        }

        if (!$agreeToTerms) {
            return $this->render('auth/register', [
                'title'          => 'Daftar Akun',
                'headerTitle'    => 'Pendaftaran Akun',
                'headerSubtitle' => 'Buat akun untuk mengakses portal internal',
                'error'          => 'Anda harus menyetujui Syarat & Ketentuan.',
            ], 'layouts/auth');
        }

        $stmt = $this->db()->prepare("SELECT id_admin FROM admin WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return $this->render('auth/register', [
                'title'          => 'Daftar Akun',
                'headerTitle'    => 'Pendaftaran Akun',
                'headerSubtitle' => 'Buat akun untuk mengakses portal internal',
                'error'          => 'Username atau email sudah terdaftar.',
            ], 'layouts/auth');
        }

        $policyError = \App\Services\PasswordService::validatePolicy($password, $username);
        if ($policyError !== null) {
            return $this->render('auth/register', [
                'title'          => 'Daftar Akun',
                'headerTitle'    => 'Pendaftaran Akun',
                'headerSubtitle' => 'Buat akun untuk mengakses portal internal',
                'error'          => $policyError,
            ], 'layouts/auth');
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (24 * 60 * 60));
        $hashedPassword = \App\Services\PasswordService::hash($password);

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

        return $this->render('auth/register', [
            'title'          => 'Daftar Akun',
            'headerTitle'    => 'Pendaftaran Akun',
            'headerSubtitle' => 'Buat akun untuk mengakses portal internal',
            'success'        => 'Pendaftaran berhasil! Silakan periksa email Anda untuk melakukan verifikasi akun.',
        ], 'layouts/auth');
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
                'heading' => 'Verifikasi Gagal',
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
                'heading' => 'Verifikasi Gagal',
                'success' => false,
                'message' => 'Token tidak valid atau sudah pernah digunakan sebelumnya.',
            ], 'layouts/auth');
        }

        if (strtotime($user['verification_expires']) < time()) {
            return $this->render('auth/verify_result', [
                'title'   => 'Token Kedaluwarsa',
                'heading' => 'Verifikasi Gagal',
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
            'heading' => 'Email Berhasil Diverifikasi',
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
                'title'   => 'Verifikasi Gagal',
                'heading' => 'Verifikasi Gagal',
                'success' => false,
                'message' => 'Link verifikasi tidak valid.',
            ], 'layouts/auth');
        }

        $stmt = $this->db()->prepare("
            SELECT id_admin, email, role, email_verified, verification_token, verification_expires
            FROM admin
            WHERE verification_token = ?
              AND role = 'USER APP PORTAL'
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return $this->render('auth/verify_result', [
                'title'   => 'Verifikasi Gagal',
                'heading' => 'Verifikasi Gagal',
                'success' => false,
                'message' => 'Link verifikasi tidak valid atau sudah tidak berlaku.',
            ], 'layouts/auth');
        }

        if (empty($user['verification_expires']) || strtotime($user['verification_expires']) < time()) {
            return $this->render('auth/verify_result', [
                'title'   => 'Verifikasi Gagal',
                'heading' => 'Verifikasi Gagal',
                'success' => false,
                'message' => 'Link verifikasi sudah kedaluwarsa. Silakan minta administrator mengirimkan verifikasi ulang.',
            ], 'layouts/auth');
        }

        // Tandai email sudah diverifikasi, pertahankan token untuk setup_user.php
        $upStmt = $this->db()->prepare("
            UPDATE admin
            SET email_verified = 1
            WHERE id_admin = ?
              AND role = 'USER APP PORTAL'
        ");
        $upStmt->execute([(int)$user['id_admin']]);

        return $this->render('auth/verify_result', [
            'title'     => 'Email Berhasil Diverifikasi',
            'heading'   => 'Email Berhasil Diverifikasi',
            'success'   => true,
            'message'   => 'Email berhasil diverifikasi.',
            'showSetup' => true,
            'setupUrl'  => 'setup_user.php?token=' . urlencode($token),
        ], 'layouts/auth');
    }

    /**
     * Show credential setup form for portal user.
     */
    public function setupUserForm(): string
    {
        $token = trim($_GET['token'] ?? '');
        if ($token === '') {
            return $this->render('auth/verify_result', [
                'title'   => 'Pendaftaran Tidak Dapat Dilanjutkan',
                'heading' => 'Pendaftaran Tidak Dapat Dilanjutkan',
                'success' => false,
                'message' => 'Link pendaftaran tidak valid.',
            ], 'layouts/auth');
        }

        $stmt = $this->db()->prepare("
            SELECT id_admin, email, verification_expires
            FROM admin
            WHERE verification_token = ?
              AND role = 'USER APP PORTAL'
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['verification_expires']) || strtotime($user['verification_expires']) < time()) {
            return $this->render('auth/verify_result', [
                'title'   => 'Pendaftaran Tidak Dapat Dilanjutkan',
                'heading' => 'Pendaftaran Tidak Dapat Dilanjutkan',
                'success' => false,
                'message' => 'Link pendaftaran tidak valid atau sudah tidak berlaku.',
            ], 'layouts/auth');
        }

        return $this->render('auth/setup_user', [
            'title'          => 'Buat Akun User',
            'headerTitle'    => 'Buat Akun User',
            'headerSubtitle' => 'Silakan tentukan username dan password untuk portal',
            'token'          => $token,
            'email'          => $user['email'],
            'error'          => flash('error'),
        ], 'layouts/auth');
    }

    /**
     * Process credential setup submission.
     */
    public function setupUserSubmit(): mixed
    {
        Session::start();

        $token = trim($_POST['token'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirmation'] ?? $_POST['confirm_password'] ?? '');

        if ($token === '') {
            return $this->render('auth/verify_result', [
                'title'   => 'Pendaftaran Tidak Dapat Dilanjutkan',
                'heading' => 'Pendaftaran Tidak Dapat Dilanjutkan',
                'success' => false,
                'message' => 'Link pendaftaran tidak valid.',
            ], 'layouts/auth');
        }

        $stmt = $this->db()->prepare("
            SELECT id_admin, email, verification_expires
            FROM admin
            WHERE verification_token = ?
              AND role = 'USER APP PORTAL'
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['verification_expires']) || strtotime($user['verification_expires']) < time()) {
            return $this->render('auth/verify_result', [
                'title'   => 'Pendaftaran Tidak Dapat Dilanjutkan',
                'heading' => 'Pendaftaran Tidak Dapat Dilanjutkan',
                'success' => false,
                'message' => 'Link pendaftaran tidak valid atau sudah tidak berlaku.',
            ], 'layouts/auth');
        }

        if (!Csrf::verify()) {
            return $this->render('auth/setup_user', [
                'title'          => 'Buat Akun User',
                'headerTitle'    => 'Buat Akun User',
                'headerSubtitle' => 'Silakan tentukan username dan password untuk portal',
                'token'          => $token,
                'email'          => $user['email'],
                'error'          => 'Sesi keamanan tidak valid atau telah kedaluwarsa.',
            ], 'layouts/auth');
        }

        if ($username === '' || $password === '') {
            return $this->render('auth/setup_user', [
                'title'          => 'Buat Akun User',
                'headerTitle'    => 'Buat Akun User',
                'headerSubtitle' => 'Silakan tentukan username dan password untuk portal',
                'token'          => $token,
                'email'          => $user['email'],
                'error'          => 'Username dan password wajib diisi.',
            ], 'layouts/auth');
        }

        if ($passwordConfirm !== '' && $password !== $passwordConfirm) {
            return $this->render('auth/setup_user', [
                'title'          => 'Buat Akun User',
                'headerTitle'    => 'Buat Akun User',
                'headerSubtitle' => 'Silakan tentukan username dan password untuk portal',
                'token'          => $token,
                'email'          => $user['email'],
                'error'          => 'Konfirmasi password tidak cocok.',
            ], 'layouts/auth');
        }

        $policyError = \App\Services\PasswordService::validatePolicy($password, $username);
        if ($policyError !== null) {
            return $this->render('auth/setup_user', [
                'title'          => 'Buat Akun User',
                'headerTitle'    => 'Buat Akun User',
                'headerSubtitle' => 'Silakan tentukan username dan password untuk portal',
                'token'          => $token,
                'email'          => $user['email'],
                'error'          => $policyError,
            ], 'layouts/auth');
        }

        // Check if username taken by someone else
        $uStmt = $this->db()->prepare("SELECT id_admin FROM admin WHERE username = ? AND id_admin != ? LIMIT 1");
        $uStmt->execute([$username, (int)$user['id_admin']]);
        if ($uStmt->fetch()) {
            return $this->render('auth/setup_user', [
                'title'          => 'Buat Akun User',
                'headerTitle'    => 'Buat Akun User',
                'headerSubtitle' => 'Silakan tentukan username dan password untuk portal',
                'token'          => $token,
                'email'          => $user['email'],
                'error'          => 'Username sudah digunakan, silakan pilih username lain.',
            ], 'layouts/auth');
        }

        // Hash with Bcrypt (BUG-004 fix)
        $hashedPassword = \App\Services\PasswordService::hash($password);

        $upStmt = $this->db()->prepare("
            UPDATE admin
            SET username = ?, password = ?, email_verified = 1, status = 'approved',
                verification_token = NULL, verification_expires = NULL
            WHERE id_admin = ?
              AND role = 'USER APP PORTAL'
        ");
        $upStmt->execute([$username, $hashedPassword, (int)$user['id_admin']]);

        return $this->render('auth/verify_result', [
            'title'   => 'Pendaftaran Berhasil',
            'heading' => 'Pendaftaran Berhasil',
            'success' => true,
            'message' => 'Akun Anda berhasil dibuat dan telah terdaftar sebagai User App Portal.',
        ], 'layouts/auth');
    }
}
