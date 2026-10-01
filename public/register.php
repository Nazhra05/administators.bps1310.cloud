<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/config.php';
require_once 'send_verification_email.php';
require_once 'send_admin_notification.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $agreeToTerms = isset($_POST['agree_to_terms']);


    /* =========================
       VALIDASI
    ========================= */

    if (
        $firstName === '' ||
        $lastName === '' ||
        $username === '' ||
        $email === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {

        $error = 'Semua data wajib diisi.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Format email tidak valid.';

    } elseif ($password !== $confirmPassword) {

        $error = 'Konfirmasi password tidak cocok.';

    } elseif (strlen($password) < 6) {

        $error = 'Password minimal 6 karakter.';

    } elseif (!$agreeToTerms) {

        $error = 'Anda harus menyetujui Terms & Conditions.';

    } else {

        /* =========================
           CEK USERNAME / EMAIL
        ========================= */

        $stmt = $conn->prepare("
            SELECT id_admin
            FROM admin
            WHERE username = ? OR email = ?
            LIMIT 1
        ");

        $stmt->execute([
            $username,
            $email
        ]);

        $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($existingAdmin) {

            $error = 'Username atau email sudah terdaftar.';

        } else {

            /* =========================
               TOKEN VERIFIKASI
            ========================= */

            $verificationToken = bin2hex(
                random_bytes(32)
            );

            $verificationExpires = date(
                'Y-m-d H:i:s',
                time() + (24 * 60 * 60)
            );


            /*
             * Sementara menggunakan SHA-256
             * agar kompatibel dengan login.php
             */

            $hashedPassword = hash(
                'sha256',
                $password
            );


            try {

                /* =========================
                   MULAI TRANSAKSI
                ========================= */

                $conn->beginTransaction();


                /* =========================
                   SIMPAN DATA
                ========================= */

                $stmt = $conn->prepare("
                    INSERT INTO admin (
                        first_name,
                        last_name,
                        username,
                        email,
                        password,
                        status,
                        email_verified,
                        verification_token,
                        verification_expires
                    )
                    VALUES (?, ?, ?, ?, ?, 'pending', 0, ?, ?)
                ");

                $stmt->execute([
                    $firstName,
                    $lastName,
                    $username,
                    $email,
                    $hashedPassword,
                    $verificationToken,
                    $verificationExpires
                ]);


                /* =========================
                   KIRIM EMAIL VERIFIKASI
                ========================= */

                $emailSent = sendVerificationEmail(
                    $email,
                    $firstName,
                    $verificationToken
                );


                /* =========================
                   CEK HASIL EMAIL VERIFIKASI
                ========================= */

                if (!$emailSent) {

                    $conn->rollBack();

                    $error = '
                        Pendaftaran gagal karena email verifikasi
                        tidak dapat dikirim. Silakan coba lagi.
                    ';

                } else {

                    /*
                     * Email verifikasi berhasil dikirim.
                     *
                     * Data pendaftaran disimpan permanen
                     * terlebih dahulu.
                     */

                    $conn->commit();


                    /* =========================
                       KIRIM NOTIFIKASI
                       KE ADMIN UTAMA
                    ========================= */

                    $adminNotificationSent =
                        sendAdminRegistrationNotification(
                            $firstName,
                            $lastName,
                            $username,
                            $email
                        );


                    /*
                     * Jika notifikasi admin gagal,
                     * pendaftaran TETAP berhasil.
                     *
                     * Kegagalan dicatat ke log
                     * untuk pengecekan lebih lanjut.
                     */

                    if (!$adminNotificationSent) {

                        error_log(
                            'Notifikasi admin gagal dikirim untuk pendaftaran: '
                            . $username
                        );
                    }


                    /* =========================
                       PESAN SUKSES
                    ========================= */

                    $success = '
    <strong>Pendaftaran berhasil!</strong><br><br>
    Email verifikasi sudah dikirim.<br>
    <strong>Silakan cek Inbox atau folder Spam/Junk.</strong>
';
                }

            } catch (Exception $e) {

                if ($conn->inTransaction()) {

                    $conn->rollBack();

                }

                $error = '
                    Terjadi kesalahan saat proses pendaftaran.
                    Silakan coba lagi.
                ';

                error_log(
                    'Register error: '
                    . $e->getMessage()
                );
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - BPS Solok Selatan</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            background:
                linear-gradient(
                    135deg,
                    #0f172a,
                    #1e3a8a,
                    #1e293b
                );
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .signup-container {
            width: 100%;
            max-width: 1050px;
            min-height: 650px;
            background: white;
            border-radius: 28px;
            overflow: hidden;
            display: flex;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
        }

        /* =========================
           LEFT PANEL
        ========================= */

        .left-panel {
            width: 50%;
            position: relative;
            overflow: hidden;
            background: #005baa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .left-panel::before {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            top: -120px;
            left: -120px;
        }

        .left-panel::after {
            content: "";
            position: absolute;
            width: 350px;
            height: 350px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            bottom: -130px;
            right: -100px;
        }

        .logo-wrapper {
            position: relative;
            z-index: 2;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.18);
        }

        .logo-wrapper img {
            width: 190px;
            height: 190px;
            object-fit: contain;
        }

        .back-button {
            position: absolute;
            top: 24px;
            left: 24px;
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.20);
            color: white;
            font-size: 22px;
            cursor: pointer;
            z-index: 5;
            backdrop-filter: blur(5px);
        }

        .back-button:hover {
            background: rgba(0, 0, 0, 0.35);
        }

        /* =========================
           RIGHT PANEL
        ========================= */

        .right-panel {
            width: 50%;
            padding: 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .heading {
            margin-bottom: 30px;
        }

        .heading h1 {
            font-size: 32px;
            color: #111827;
            margin-bottom: 10px;
        }

        .heading p {
            color: #6b7280;
            font-size: 15px;
        }

        .heading a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .heading a:hover {
            text-decoration: underline;
        }

        /* =========================
           FORM
        ========================= */

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            transition: 0.2s;
        }

        .form-group input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 48px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 19px;
            color: #6b7280;
        }

        /* =========================
           TERMS
        ========================= */

        .terms {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 24px;
        }

        .terms input {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
            cursor: pointer;
        }

        .terms label {
            font-size: 13px;
            color: #6b7280;
        }

        .terms a {
            color: #111827;
            font-weight: 600;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        /* =========================
           BUTTON
        ========================= */

        .btn-create {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: #111111;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-create:hover {
            background: #292929;
        }

        /* =========================
           MESSAGE
        ========================= */

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 800px) {

            .signup-container {
                max-width: 550px;
            }

            .left-panel {
                display: none;
            }

            .right-panel {
                width: 100%;
                padding: 35px;
            }

        }

        @media (max-width: 500px) {

            body {
                padding: 10px;
            }

            .right-panel {
                padding: 25px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .heading h1 {
                font-size: 27px;
            }

        }

    </style>

</head>

<body>

<div class="signup-container">

    <!-- =========================
         LEFT PANEL
    ========================== -->

    <div class="left-panel">

        <button
            type="button"
            class="back-button"
            onclick="window.location.href='login.php'"
        >
            ←
        </button>

        <div class="logo-wrapper">

            <img
                src="uploads/logo_bps.jpg"
                alt="Logo BPS"
            >

        </div>

    </div>


    <!-- =========================
         RIGHT PANEL
    ========================= -->

    <div class="right-panel">

        <div class="heading">

            <h1>Create an Account</h1>

            <p>
                Already have an account?
                <a href="login.php">Log in</a>
            </p>

        </div>


        <?php if ($error !== ''): ?>

            <div class="error">
                <?= htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <?php if ($success !== ''): ?>

            <div class="success">
                <?= $success; ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <!-- NAME -->

            <div class="form-row">

                <div class="form-group">

                    <label for="first_name">
                        First Name
                    </label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        placeholder="First Name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="last_name">
                        Last Name
                    </label>

                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        placeholder="Last Name"
                        required
                    >

                </div>

            </div>


            <!-- USERNAME -->

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Username"
                    required
                >

            </div>


            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Email Address"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('password', this)"
                    >
                        👁️
                    </button>

                </div>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm Password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword('confirm_password', this)"
                    >
                        👁️
                    </button>

                </div>

            </div>


            <!-- TERMS -->

            <div class="terms">

                <input
                    type="checkbox"
                    id="agree_to_terms"
                    name="agree_to_terms"
                    required
                >

                <label for="agree_to_terms">
                    I agree to the
                    <a href="#" onclick="return false;">
                        Terms & Conditions
                    </a>
                </label>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="btn-create"
            >
                Create Account
            </button>

        </form>

    </div>

</div>


<script>

function togglePassword(inputId, button) {

    const input = document.getElementById(inputId);

    if (input.type === 'password') {

        input.type = 'text';
        button.textContent = '👁️';

    } else {

        input.type = 'password';
        button.textContent = '👁️';

    }

}

</script>

</body>

</html>