<?php

require_once '../config/config.php';

$token = trim($_GET['token'] ?? '');

$message = '';
$type = 'error';
$showSetup = false;

if ($token === '') {
    $message = 'Link verifikasi tidak valid.';
} else {

    $stmt = $conn->prepare("
        SELECT
            id_admin,
            username,
            password,
            email,
            role,
            email_verified,
            verification_expires,
            status
        FROM admin
        WHERE verification_token = ?
        LIMIT 1
    ");

    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        $message = 'Link verifikasi tidak valid atau sudah tidak berlaku.';

    } elseif (
        empty($user['verification_expires']) ||
        strtotime($user['verification_expires']) < time()
    ) {

        $message = 'Link verifikasi sudah kedaluwarsa. Silakan minta administrator mengirimkan verifikasi ulang.';

    } elseif ((int) $user['email_verified'] === 1) {

        $message = 'Email ini sudah berhasil diverifikasi.';
        $type = 'success';
        $showSetup = empty($user['password']);

    } else {

        /*
         * Tandai email sudah diverifikasi.
         * Token dan waktu kedaluwarsa dihapus agar link
         * tidak dapat digunakan kembali.
         */
        $stmtUpdate = $conn->prepare("
            UPDATE admin
            SET
                email_verified = 1,
                verification_token = NULL,
                verification_expires = NULL
            WHERE id_admin = ?
        ");

        $stmtUpdate->execute([$user['id_admin']]);

        $isAdmin = (strtoupper(trim($user['role'] ?? '')) === 'ADMIN');
        if (empty($user['password'])) {
            $message = $isAdmin
                ? 'Email administrator berhasil diverifikasi! Silakan lanjutkan untuk membuat username dan password admin.'
                : 'Email berhasil diverifikasi! Silakan lanjutkan untuk membuat username dan password akun Anda.';
            $showSetup = true;
        } else {
            $message = 'Email akun Pengguna (User) berhasil diverifikasi! Akun Anda sekarang menunggu persetujuan administrator di menu Kelola User.';
            $showSetup = false;
        }
        $type = 'success';
    }
}

$isAdmin = !empty($user) && (strtoupper(trim($user['role'] ?? '')) === 'ADMIN');
$roleLabel = $isAdmin ? 'Administrator' : 'User';
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Verifikasi Email <?= $isAdmin ? 'Admin ' : '' ?>- BPS Solok Selatan</title>

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

        .icon.error {
            background: #fdecec;
            color: #d93025;
        }

        h1 {
            font-size: 24px;
            color: #183b7a;
            margin-bottom: 12px;
        }

        .message {
            font-size: 15px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 25px;
        }

        .setup-text {
            font-size: 15px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 20px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #0066b3;
            color: #ffffff;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
        }

        .button:hover {
            background: #005494;
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

            <div class="icon">
                ✓
            </div>

            <h1>
                Email <?= $isAdmin ? 'Admin ' : '' ?>Berhasil Diverifikasi
            </h1>

            <div class="message">
                <?= e($message) ?>
            </div>

            <?php if ($showSetup): ?>

                <div class="setup-text">
                    Silakan buat username dan password untuk akun <?= strtolower(e($roleLabel)) ?> Anda.
                </div>

                <a
                    href="setup_user.php?id=<?= (int) $user['id_admin'] ?>"
                    class="button"
                >
                    Buat Username & Password <?= $isAdmin ? 'Admin' : '' ?>
                </a>

            <?php else: ?>

                <a
                    href="login.php"
                    class="button"
                >
                    Masuk ke Halaman Login
                </a>

            <?php endif; ?>

        <?php else: ?>

            <div class="icon error">
                !
            </div>

            <h1>
                Verifikasi Gagal
            </h1>

            <div class="message">
                <?= e($message) ?>
            </div>

        <?php endif; ?>

    </div>

</body>

</html>