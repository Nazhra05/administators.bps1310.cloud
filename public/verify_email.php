<?php

require_once '../config/config.php';


/* =====================================================
   INISIALISASI
===================================================== */

$message = '';
$type = 'error';

$token = $_GET['token'] ?? '';

$token = trim($token);


/* =====================================================
   CEK TOKEN
===================================================== */

if ($token === '') {

    $message = 'Token verifikasi tidak ditemukan. Silakan gunakan link verifikasi yang dikirim ke email Anda.';

} else {

    /* =================================================
       CARI ADMIN BERDASARKAN TOKEN
    ================================================= */

    $stmt = $conn->prepare("
        SELECT
            id_admin,
            email_verified,
            verification_expires
        FROM admin
        WHERE verification_token = ?
        LIMIT 1
    ");

    $stmt->execute([$token]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);


    /* =================================================
       TOKEN TIDAK DITEMUKAN
    ================================================= */

    if (!$admin) {

        $message =
            'Link verifikasi tidak valid atau sudah digunakan.';


    /* =================================================
       EMAIL SUDAH DIVERIFIKASI
    ================================================= */

    } elseif ((int)$admin['email_verified'] === 1) {

        $message =
            'Email Anda sudah berhasil diverifikasi.';

        $type = 'success';


    /* =================================================
       TOKEN KEDALUWARSA
    ================================================= */

    } elseif (
        empty($admin['verification_expires']) ||
        strtotime($admin['verification_expires']) < time()
    ) {

        $message =
            'Link verifikasi sudah kedaluwarsa. Silakan lakukan pendaftaran kembali atau hubungi administrator.';


    /* =================================================
       VERIFIKASI BERHASIL
    ================================================= */

    } else {

        $update = $conn->prepare("
            UPDATE admin
            SET
                email_verified = 1,
                verification_token = NULL,
                verification_expires = NULL
            WHERE id_admin = ?
        ");

        $update->execute([
            $admin['id_admin']
        ]);


        $message =
            'Email berhasil diverifikasi! Akun Anda sekarang menunggu persetujuan administrator.';

        $type = 'success';
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
        Verifikasi Email - Dashboard Admin
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #eef5ff,
                    #f8fbff
                );

            min-height: 100vh;

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 20px;
        }


        .container {

            width: 100%;

            max-width: 520px;

            background: #ffffff;

            padding: 45px 40px;

            border-radius: 18px;

            box-shadow:
                0 10px 30px
                rgba(0, 0, 0, 0.12);

            text-align: center;
        }


        .logo {

            width: 90px;

            height: 90px;

            object-fit: contain;

            margin-bottom: 20px;
        }


        h1 {

            color: #005baa;

            font-size: 27px;

            margin-bottom: 20px;
        }


        .icon {

            font-size: 55px;

            margin-bottom: 15px;
        }


        .message {

            padding: 17px 18px;

            border-radius: 10px;

            line-height: 1.7;

            font-size: 15px;

            margin-bottom: 28px;
        }


        .success {

            background: #e8f7ee;

            color: #18794e;

            border:
                1px solid #b8e6cc;
        }


        .error {

            background: #ffe8e8;

            color: #c62828;

            border:
                1px solid #f2b8b8;
        }


        .btn {

            display: inline-block;

            padding:
                13px 28px;

            background: #005baa;

            color: #ffffff;

            text-decoration: none;

            border-radius: 8px;

            font-weight: bold;

            transition: 0.2s;
        }


        .btn:hover {

            background: #00498f;

            transform: translateY(-1px);
        }


        .footer {

            margin-top: 25px;

            color: #888888;

            font-size: 12px;

            line-height: 1.5;
        }

    </style>

</head>


<body>

    <div class="container">


        <!-- LOGO -->

        <img
            src="uploads/logo_bps.jpg"
            alt="Logo BPS"
            class="logo"
            onerror="this.style.display='none';"
        >


        <!-- ICON -->

        <div class="icon">

            <?php if ($type === 'success'): ?>

                ✓

            <?php else: ?>

                !

            <?php endif; ?>

        </div>


        <!-- TITLE -->

        <h1>
            Verifikasi Email
        </h1>


        <!-- MESSAGE -->

        <div class="message <?= htmlspecialchars($type); ?>">

            <?= htmlspecialchars($message); ?>

        </div>


        <!-- BUTTON -->

        <a
            href="login.php"
            class="btn"
        >
            Kembali ke Login
        </a>


        <!-- FOOTER -->

        <div class="footer">

            Dashboard Admin<br>
            BPS Kabupaten Solok Selatan

        </div>

    </div>

</body>

</html>
