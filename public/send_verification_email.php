<?php

require_once '../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';


/* =====================================================
   FUNGSI KIRIM EMAIL VERIFIKASI
===================================================== */

function sendVerificationEmail(
    string $email,
    string $firstName,
    string $verificationToken
): bool {

    $mail = new PHPMailer(true);

    try {

        /* =========================
           SMTP GMAIL
        ========================= */

        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;


        /* =========================
           PENGIRIM
        ========================= */

        $mail->setFrom(
            SMTP_FROM_EMAIL,
            SMTP_FROM_NAME
        );

        $mail->addAddress(
            $email,
            $firstName
        );


        /* =========================
           LINK VERIFIKASI
        ========================= */

        $baseUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://administators.bps1310.cloud';
        $verificationLink = $baseUrl . '/verify_email.php?token=' . urlencode($verificationToken);


        /* =========================
           EMAIL HTML
        ========================= */

        $mail->isHTML(true);

        $mail->Subject =
            'Verifikasi Email - BPS Kabupaten Solok Selatan';

        $safeFirstName = htmlspecialchars(
            $firstName,
            ENT_QUOTES,
            'UTF-8'
        );


        $mail->Body = '
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Verifikasi Email</title>

</head>


<body style="
    margin:0;
    padding:0;
    background:#f4f7fb;
    font-family:Arial, Helvetica, sans-serif;
">


    <div style="
        max-width:600px;
        margin:40px auto;
        background:#ffffff;
        border-radius:12px;
        overflow:hidden;
        box-shadow:0 4px 15px rgba(0,0,0,0.08);
    ">


        <!-- HEADER -->

        <div style="
            background:#005baa;
            padding:25px;
            text-align:center;
            color:#ffffff;
        ">

            <h2 style="
                margin:0;
            ">
                BPS Kabupaten Solok Selatan
            </h2>


            <p style="
                margin:8px 0 0;
                font-size:14px;
                opacity:0.9;
            ">
                Dashboard Admin
            </p>

        </div>


        <!-- CONTENT -->

        <div style="
            padding:35px;
        ">


            <h3 style="
                color:#333333;
                margin-top:0;
            ">
                Halo ' . $safeFirstName . ',
            </h3>


            <p style="
                color:#555555;
                line-height:1.7;
            ">
                Terima kasih telah melakukan pendaftaran
                akun pada Dashboard Admin BPS Kabupaten
                Solok Selatan.
            </p>


            <p style="
                color:#555555;
                line-height:1.7;
            ">
                Silakan klik tombol di bawah ini untuk
                melakukan verifikasi alamat email Anda.
            </p>


            <!-- TOMBOL VERIFIKASI -->

            <div style="
                text-align:center;
                margin:30px 0;
            ">


                <a
                    href="' . $verificationLink . '"
                    target="_blank"
                    style="
                        display:inline-block;
                        background:#005baa;
                        color:#ffffff;
                        text-decoration:none;
                        padding:14px 30px;
                        border-radius:8px;
                        font-weight:bold;
                        font-size:15px;
                    "
                >
                    Verifikasi Email
                </a>


            </div>


            <!-- LINK MANUAL -->

            <p style="
                color:#777777;
                font-size:13px;
                line-height:1.6;
            ">
                Jika tombol di atas tidak dapat diklik,
                silakan salin dan buka link berikut pada
                browser Anda:
            </p>


            <div style="
                background:#f4f7fb;
                padding:12px;
                border-radius:6px;
                word-break:break-all;
                font-size:12px;
                color:#555555;
            ">

                ' . $verificationLink . '

            </div>


            <p style="
                color:#777777;
                font-size:13px;
                line-height:1.6;
                margin-top:25px;
            ">
                Link verifikasi ini berlaku selama
                <strong>24 jam</strong>.
            </p>


            <p style="
                color:#777777;
                font-size:13px;
                line-height:1.6;
            ">
                Setelah email berhasil diverifikasi,
                akun Anda masih menunggu persetujuan
                administrator sebelum dapat digunakan
                untuk login.
            </p>


        </div>


        <!-- FOOTER -->

        <div style="
            background:#f4f7fb;
            padding:20px;
            text-align:center;
            color:#888888;
            font-size:12px;
        ">

            © BPS Kabupaten Solok Selatan

        </div>


    </div>


</body>

</html>
';


        /* =========================
           VERSI TEXT
        ========================= */

        $mail->AltBody =
            "Halo $firstName,\n\n" .
            "Terima kasih telah melakukan pendaftaran akun " .
            "pada Dashboard Admin BPS Kabupaten Solok Selatan.\n\n" .
            "Silakan verifikasi email Anda melalui link berikut:\n\n" .
            $verificationLink . "\n\n" .
            "Link verifikasi berlaku selama 24 jam.\n\n" .
            "Setelah email berhasil diverifikasi, akun Anda " .
            "masih menunggu persetujuan administrator.";


        /* =========================
           KIRIM EMAIL
        ========================= */

        $mail->send();

        return true;


    } catch (Exception $e) {

        error_log(
            'Gagal mengirim email verifikasi: ' .
            $mail->ErrorInfo
        );

        return false;
    }
}