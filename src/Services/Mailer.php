<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private static function createMailer(): ?PHPMailer
    {
        $host = env('SMTP_HOST');
        if (empty($host)) {
            return null;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string)$host;
        $mail->Port = (int)env('SMTP_PORT', 587);

        $user = env('SMTP_USERNAME');
        $pass = env('SMTP_PASSWORD');
        if (!empty($user)) {
            $mail->SMTPAuth = true;
            $mail->Username = (string)$user;
            $mail->Password = (string)$pass;
        }

        if ($mail->Port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($mail->Port === 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $fromEmail = env('SMTP_FROM_EMAIL', 'no-reply@bps1310.cloud');
        $fromName = env('SMTP_FROM_NAME', 'BPS Kabupaten Solok Selatan');
        $mail->setFrom((string)$fromEmail, (string)$fromName);
        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    public static function sendVerificationEmail(string $email, string $name, string $token, bool $isPortalUser = false): bool
    {
        try {
            $mail = self::createMailer();
            if ($mail === null) {
                Logger::warning("SMTP not configured; skipped sending verification email to {$email}");
                return false;
            }

            $mail->addAddress($email, $name);
            $mail->isHTML(true);

            $endpoint = $isPortalUser ? '/verify_user.php?token=' : '/verify_email.php?token=';
            $verifyUrl = url($endpoint . urlencode($token));

            $mail->Subject = 'Verifikasi Akun - BPS Kabupaten Solok Selatan';
            $mail->Body = "
                <div style='font-family: sans-serif; padding: 20px; line-height: 1.6;'>
                    <h2>Halo, " . e($name) . "!</h2>
                    <p>Terima kasih telah mendaftar di sistem BPS Kabupaten Solok Selatan. Silakan klik tombol di bawah ini untuk memverifikasi akun Anda:</p>
                    <p style='margin: 25px 0;'>
                        <a href='{$verifyUrl}' style='background: #0066b3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                            Verifikasi Akun Saya
                        </a>
                    </p>
                    <p>Atau buka tautan berikut: <br><a href='{$verifyUrl}'>{$verifyUrl}</a></p>
                    <p>Tautan ini berlaku selama 24 jam.</p>
                </div>
            ";

            return $mail->send();
        } catch (\Throwable $e) {
            Logger::error("Gagal mengirim email verifikasi: " . $e->getMessage(), ['email' => $email]);
            return false;
        }
    }

    public static function sendAdminNotification(array $userData): bool
    {
        try {
            $mail = self::createMailer();
            if ($mail === null) {
                return false;
            }

            $adminEmail = env('ADMIN_NOTIFICATION_EMAIL', env('SMTP_FROM_EMAIL'));
            if (empty($adminEmail)) {
                return false;
            }

            $mail->addAddress((string)$adminEmail, 'Administrator BPS');
            $mail->isHTML(true);
            $mail->Subject = 'Pemberitahuan Pendaftaran Akun Baru';
            $mail->Body = "
                <div style='font-family: sans-serif; padding: 20px;'>
                    <h3>Pendaftaran Akun Baru</h3>
                    <p>Seorang pengguna baru telah mendaftar dan membutuhkan peninjauan:</p>
                    <ul>
                        <li><strong>Nama:</strong> " . e($userData['first_name'] ?? '') . ' ' . e($userData['last_name'] ?? '') . "</li>
                        <li><strong>Username:</strong> " . e($userData['username'] ?? '') . "</li>
                        <li><strong>Email:</strong> " . e($userData['email'] ?? '') . "</li>
                    </ul>
                </div>
            ";

            return $mail->send();
        } catch (\Throwable $e) {
            Logger::error("Gagal mengirim notifikasi admin: " . $e->getMessage());
            return false;
        }
    }
}
