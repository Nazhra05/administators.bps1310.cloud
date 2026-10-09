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
            email,
            role,
            email_verified,
            verification_expires,
            status
        FROM admin
        WHERE verification_token = ?
          AND role = 'USER APP PORTAL'
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
        $showSetup = true;

    } else {

        /*
         * Tandai email sudah diverifikasi.
         * Token verifikasi dipertahankan agar dapat digunakan
         * pada tautan setup_user.php?token=... secara aman.
         */
        $stmtUpdate = $conn->prepare("
            UPDATE admin
            SET email_verified = 1
            WHERE id_admin = ?
              AND role = 'USER APP PORTAL'
        ");

        $stmtUpdate->execute([$user['id_admin']]);

        $message = 'Email berhasil diverifikasi.';
        $type = 'success';
        $showSetup = true;
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

    <title>Verifikasi Email - BPS Solok Selatan</title>

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
                Email Berhasil Diverifikasi
            </h1>

            <div class="message">
                <?= e($message) ?>
            </div>

            <?php if ($showSetup): ?>

                <div class="setup-text">
                    Silakan buat username dan password untuk akun Anda.
                </div>

                <a
                    href="setup_user.php?token=<?= urlencode((string) ($user['verification_token'] ?? $token)) ?>"
                    class="button"
                >
                    Buat Username & Password
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