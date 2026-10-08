<?php

require_once '../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =====================================================
   FUNGSI KIRIM EMAIL VERIFIKASI USER
===================================================== */
function sendUserVerificationEmail(
    string $email,
    string $verificationToken
): bool {

    $mail = new PHPMailer(true);

    try {
        /* =========================
           SMTP
        ========================= */
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        /* =========================
           PENGIRIM & PENERIMA
        ========================= */
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);

        /* =========================
           LINK VERIFIKASI
        ========================= */
        $verificationLink = APP_URL . '/verify_user.php?token=' . urlencode($verificationToken);

        /* =========================
           EMAIL CONTENT
        ========================= */
        $mail->isHTML(true);
        $mail->Subject = 'Verifikasi Akun User - BPS Solok Selatan';
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; background: #f5f7fb; padding: 30px;">
                <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 30px; border-radius: 10px;">
                    <h2 style="color: #0066b3; margin-bottom: 20px;">Verifikasi Akun User</h2>
                    <p>Halo,</p>
                    <p style="line-height: 1.6;">Anda telah didaftarkan sebagai <strong>USER APP PORTAL</strong> pada sistem BPS Kabupaten Solok Selatan.</p>
                    <p style="line-height: 1.6;">Silakan klik tombol di bawah ini untuk memverifikasi alamat email Anda dan melanjutkan proses pendaftaran.</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8') . '" style="display: inline-block; background: #0066b3; color: #ffffff; text-decoration: none; padding: 13px 25px; border-radius: 7px; font-weight: bold;">Verifikasi Email</a>
                    </div>
                    <p style="color: #666; font-size: 13px; line-height: 1.6;">Link verifikasi ini berlaku selama 24 jam.</p>
                    <p style="color: #666; font-size: 13px; line-height: 1.6;">Jika Anda tidak merasa didaftarkan, abaikan email ini.</p>
                    <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 25px 0;">
                    <p style="color: #888; font-size: 12px; text-align: center;">BPS Kabupaten Solok Selatan</p>
                </div>
            </div>
        ';

        $mail->AltBody = "Verifikasi Akun User BPS Kabupaten Solok Selatan\n\nSilakan buka link berikut untuk memverifikasi email Anda:\n\n" . $verificationLink . "\n\nLink berlaku selama 24 jam.";

        return $mail->send();

    } catch (Exception $e) {
        return false;
    }
}