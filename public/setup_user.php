

<?php

require_once '../config/config.php';

/* =========================
   INISIALISASI
========================= */

$message = '';
$type = 'error';

$user = null;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');


/* =========================
   CEK TOKEN PENDAFTARAN (IDOR PREVENTION)
========================= */

if ($token === '') {

    $message = 'Link pendaftaran tidak valid atau token verifikasi tidak ditemukan.';

} else {

    $stmt = $conn->prepare("
        SELECT
            id_admin,
            email,
            username,
            password,
            role,
            status,
            email_verified,
            verification_token,
            verification_expires
        FROM admin
        WHERE verification_token = ?
          AND role = 'USER APP PORTAL'
        LIMIT 1
    ");

    $stmt->execute([$token]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        $message = 'Link pendaftaran tidak valid atau sudah tidak berlaku.';

    } elseif (
        !empty($user['verification_expires']) &&
        strtotime($user['verification_expires']) < time()
    ) {

        $message = 'Link pendaftaran sudah kedaluwarsa. Silakan minta administrator mengirimkan verifikasi ulang.';

    } elseif ((int) $user['email_verified'] !== 1) {

        $message = 'Email belum diverifikasi. Silakan verifikasi email terlebih dahulu.';

    } elseif (
        !empty($user['username']) &&
        !empty($user['password']) &&
        $user['status'] === 'approved'
    ) {

        $message = 'Akun ini sudah selesai didaftarkan.';
        $type = 'success';
    }
}


/* =========================
   PROSES PENDAFTARAN
========================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $user &&
    $type !== 'success'
) {

    if (!csrf_verify()) {

        $message = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';

    } else {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        /* =========================
           VALIDASI USERNAME
        ========================= */

        if ($username === '') {

            $message = 'Username wajib diisi.';

        } elseif (strlen($username) < 4) {

            $message = 'Username minimal 4 karakter.';

        } elseif (strlen($username) > 50) {

            $message = 'Username maksimal 50 karakter.';

        } elseif (!preg_match('/^[A-Za-z0-9._-]+$/', $username)) {

            $message = 'Username hanya boleh menggunakan huruf, angka, titik, underscore, dan tanda strip.';

        }

        /* =========================
           VALIDASI PASSWORD
        ========================= */

        elseif ($password === '') {

            $message = 'Password wajib diisi.';

        } elseif (strlen($password) < 8) {

            $message = 'Password minimal 8 karakter.';

        } elseif ($password !== $passwordConfirmation) {

            $message = 'Konfirmasi password tidak sama.';

        } else {

            /* =========================
               CEK USERNAME
            ========================= */

            $stmtUsername = $conn->prepare("
                SELECT id_admin
                FROM admin
                WHERE username = ?
                  AND id_admin != ?
                LIMIT 1
            ");

            $stmtUsername->execute([
                $username,
                (int) $user['id_admin']
            ]);

            $usernameExists = $stmtUsername->fetch(PDO::FETCH_ASSOC);

            if ($usernameExists) {

                $message = 'Username sudah digunakan. Silakan pilih username lain.';

            } else {

                /* =========================
                   HASH PASSWORD (BCRYPT)
                ========================= */

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                /* =========================
                   SIMPAN DATA USER
                ========================= */

                $stmtUpdate = $conn->prepare("
                    UPDATE admin
                    SET
                        username = ?,
                        password = ?,
                        status = 'approved',
                        email_verified = 1,
                        verification_token = NULL,
                        verification_expires = NULL
                    WHERE id_admin = ?
                      AND role = 'USER APP PORTAL'
                      AND email_verified = 1
                ");

                $stmtUpdate->execute([
                    $username,
                    $hashedPassword,
                    (int) $user['id_admin']
                ]);

                if ($stmtUpdate->rowCount() > 0) {

                    $message = 'Pendaftaran akun berhasil. Akun Anda sudah selesai dibuat.';
                    $type = 'success';

                    $user['username'] = $username;
                    $user['password'] = $hashedPassword;
                    $user['status'] = 'approved';

                } else {

                    $message = 'Pendaftaran gagal. Silakan coba lagi.';
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= $type === 'success'
            ? 'Pendaftaran Berhasil'
            : 'Buat Akun User'
        ?> - BPS Solok Selatan
    </title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f7fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
            background: #ffffff;
            border-radius: 12px;
            padding: 40px 35px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .success-container {
            text-align: center;
        }

        .icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #e8f7ef;
            color: #16835b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: bold;
        }

        .error-icon {
            background: #fdecec;
            color: #d93025;
        }

        h1 {
            font-size: 24px;
            color: #183b7a;
            margin-bottom: 10px;
            text-align: center;
        }

        .description {
            font-size: 14px;
            line-height: 1.6;
            color: #666;
            margin-bottom: 25px;
            text-align: center;
        }

        .message {
            padding: 14px 16px;
            border-radius: 7px;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 22px;
        }

        .message.error {
            background: #fdecec;
            color: #b42318;
            border: 1px solid #f5c2c0;
        }

        .message.success {
            background: #e8f7ef;
            color: #176b45;
            border: 1px solid #b9e3cd;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 7px;
        }

        input {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #d4d9e2;
            border-radius: 7px;
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        input:focus {
            border-color: #0066b3;
        }

        .button {
            width: 100%;
            border: none;
            padding: 13px;
            background: #0066b3;
            color: #ffffff;
            border-radius: 7px;
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 5px;
        }

        .button:hover {
            background: #005494;
        }

        .success-text {
            font-size: 15px;
            line-height: 1.7;
            color: #555;
        }

        @media (max-width: 500px) {

            .container {
                padding: 30px 22px;
            }

            h1 {
                font-size: 21px;
            }

        }

    </style>

</head>

<body>

    <div class="container">

        <?php if ($type === 'success'): ?>

            <div class="success-container">

                <div class="icon">
                    ✓
                </div>

                <h1>
                    Pendaftaran Berhasil
                </h1>

                <p class="success-text">
                    Akun Anda berhasil dibuat dan telah terdaftar sebagai
                    <strong>User App Portal</strong>.
                </p>

            </div>

        <?php elseif ($user): ?>

            <h1>
                Buat Username & Password
            </h1>

            <p class="description">
                Email Anda sudah berhasil diverifikasi.
                Silakan buat username dan password untuk menyelesaikan
                pendaftaran akun.
            </p>

            <?php if ($message !== ''): ?>

                <div class="message error">
                    <?= e($message) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="token"
                    value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
                >

                <div class="form-group">

                    <label for="username">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= e($_POST['username'] ?? '') ?>"
                        placeholder="Masukkan username"
                        maxlength="50"
                        autocomplete="username"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password_confirmation">
                        Konfirmasi Password
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Ulangi password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="button"
                >
                    Daftar
                </button>

            </form>

        <?php else: ?>

            <div class="success-container">

                <div class="icon error-icon">
                    !
                </div>

                <h1>
                    Pendaftaran Tidak Dapat Dilanjutkan
                </h1>

                <div class="message error">
                    <?= e($message) ?>
                </div>

            </div>

        <?php endif; ?>

    </div>

</body>

</html>