<?php

require_once '../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';


function sendAdminRegistrationNotification(
    string $firstName,
    string $lastName,
    string $username,
    string $email
): bool {

    $mail = new PHPMailer(true);

    try {

        /* =========================
           KONFIGURASI SMTP
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


        /* =========================
           EMAIL ADMIN UTAMA
        ========================= */

        $mail->addAddress(
            SMTP_FROM_EMAIL,
            'Admin Utama'
        );


        /* =========================
           FORMAT EMAIL
        ========================= */

        $mail->isHTML(true);

        $mail->Subject = 'Notifikasi Pendaftaran Admin Baru - BPS Kabupaten Solok Selatan';


        $adminUrl = (defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://administators.bps1310.cloud') . '/kelola_admin.php';

        $mail->Body = '
        <!DOCTYPE html>
        <html lang="id">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>

        <body style="
            margin:0;
            padding:0;
            background:#f4f8fc;
            font-family:Arial, sans-serif;
        ">

            <div style="
                max-width:600px;
                margin:30px auto;
                background:white;
                border-radius:12px;
                overflow:hidden;
                box-shadow:0 3px 15px rgba(0,0,0,0.08);
            ">

                <div style="
                    background:#0066b3;
                    padding:25px;
                    text-align:center;
                    color:white;
                ">

                    <h2 style="margin:0;">
                        BPS Kabupaten Solok Selatan
                    </h2>

                    <p style="
                        margin:8px 0 0;
                        font-size:14px;
                    ">
                        Dashboard Admin
                    </p>

                </div>


                <div style="
                    padding:30px;
                    color:#333;
                ">

                    <h3 style="
                        color:#005a9c;
                        margin-top:0;
                    ">
                        Pendaftaran Admin Baru
                    </h3>

                    <p>
                        Halo Admin Utama,
                    </p>

                    <p>
                        Ada pengguna baru yang mendaftarkan diri
                        sebagai admin pada Dashboard Admin BPS
                        Kabupaten Solok Selatan.
                    </p>


                    <div style="
                        background:#f4f8fc;
                        border-left:4px solid #0066b3;
                        padding:18px;
                        margin:20px 0;
                    ">

                        <p style="margin:0 0 10px;">
                            <strong>Nama:</strong>
                            ' . htmlspecialchars($firstName . ' ' . $lastName) . '
                        </p>

                        <p style="margin:0 0 10px;">
                            <strong>Username:</strong>
                            ' . htmlspecialchars($username) . '
                        </p>

                        <p style="margin:0;">
                            <strong>Email:</strong>
                            ' . htmlspecialchars($email) . '
                        </p>

                    </div>


                    <p>
                        Akun tersebut saat ini memiliki status:
                    </p>

                    <p style="
                        display:inline-block;
                        background:#fff3cd;
                        color:#856404;
                        padding:8px 14px;
                        border-radius:6px;
                        font-weight:bold;
                    ">
                        MENUNGGU PERSETUJUAN
                    </p>


                    <p style="
                        margin-top:25px;
                    ">
                        Silakan buka menu
                        <strong>Kelola Admin</strong>
                        pada Dashboard Admin untuk memproses
                        pendaftaran tersebut.
                    </p>


                    <div style="
                        text-align:center;
                        margin:30px 0 10px;
                    ">

                        <a href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '"
                           style="
                               display:inline-block;
                               background:#0066b3;
                               color:white;
                               text-decoration:none;
                               padding:12px 22px;
                               border-radius:7px;
                               font-weight:bold;
                           ">
                            Buka Kelola Admin
                        </a>

                    </div>

                </div>


                <div style="
                    background:#f4f8fc;
                    padding:15px;
                    text-align:center;
                    color:#777;
                    font-size:12px;
                ">

                    Email otomatis dari
                    Dashboard Admin BPS Kabupaten Solok Selatan.

                </div>

            </div>

        </body>

        </html>
        ';


        $mail->AltBody =
            "Ada pendaftaran admin baru.\n\n" .
            "Nama: " . $firstName . " " . $lastName . "\n" .
            "Username: " . $username . "\n" .
            "Email: " . $email . "\n\n" .
            "Status: Menunggu persetujuan administrator.\n\n" .
            "Silakan buka menu Kelola Admin pada Dashboard Admin.";


        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            'Gagal mengirim notifikasi pendaftaran admin: '
            . $mail->ErrorInfo
        );

        return false;
    }
}