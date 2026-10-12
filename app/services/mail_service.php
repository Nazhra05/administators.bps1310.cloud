<?php
/**
 * Layanan Pengiriman Email (Mail Service)
 * BPS Kabupaten Solok Selatan
 *
 * Mengonsolidasikan seluruh fungsi pengiriman email sistem:
 * 1. sendVerificationEmail() - Verifikasi pendaftaran mandiri pengguna (user)
 * 2. sendAdminVerificationEmail() - Undangan verifikasi admin baru
 * 3. sendUserVerificationEmail() - Undangan verifikasi user baru dari admin
 * 4. sendAdminRegistrationNotification() - Notifikasi ke admin saat ada user baru
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Menentukan base URL publik untuk tautan email
 */
if (!function_exists('getMailBaseUrl')) {
    function getMailBaseUrl(): string
    {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
            if (!str_ends_with($scriptDir, '/public') && file_exists(dirname(__DIR__, 2) . '/public')) {
                $scriptDir = rtrim($scriptDir, '/') . '/public';
            }
            return $scheme . '://' . $_SERVER['HTTP_HOST'] . $scriptDir;
        }

        if (defined('APP_URL') && APP_URL !== '' && strpos(APP_URL, 'administators.bps1310.cloud') === false) {
            return rtrim(APP_URL, '/');
        }

        return 'http://localhost/Dashboard_Admin/public';
    }
}

/**
 * Konfigurasi standar instance PHPMailer
 */
if (!function_exists('createMailClient')) {
    function createMailClient(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Timeout    = 5;
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        return $mail;
    }
}

/**
 * Kirim email verifikasi pendaftaran mandiri user
 */
if (!function_exists('sendVerificationEmail')) {
    function sendVerificationEmail(
        string $email,
        string $firstName,
        string $verificationToken
    ): bool {
        try {
            $mail = createMailClient();
            $mail->addAddress($email, $firstName);

            $verificationLink = getMailBaseUrl() . '/verify_email.php?token=' . urlencode($verificationToken);
            $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');

            $mail->isHTML(true);
            $mail->Subject = 'Verifikasi Akun Pengguna (User) - BPS Kabupaten Solok Selatan';
            $mail->Body = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun Pengguna (User)</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif;">
    <div style="max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
        <div style="background:#005baa; padding:25px; text-align:center; color:#ffffff;">
            <h2 style="margin:0;">BPS Kabupaten Solok Selatan</h2>
            <p style="margin:8px 0 0; font-size:14px; opacity:0.9;">Dashboard Admin</p>
        </div>
        <div style="padding:35px;">
            <h3 style="color:#333333; margin-top:0;">Halo ' . $safeFirstName . ',</h3>
            <p style="color:#555555; line-height:1.7;">
                Terima kasih telah melakukan pendaftaran akun Pengguna (User) pada Portal Layanan BPS Kabupaten Solok Selatan.
            </p>
            <p style="color:#555555; line-height:1.7;">
                Silakan klik tombol di bawah ini untuk melakukan verifikasi alamat email Anda.
            </p>
            <div style="text-align:center; margin:30px 0;">
                <a href="' . $verificationLink . '" target="_blank" style="display:inline-block; background:#005baa; color:#ffffff; text-decoration:none; padding:14px 30px; border-radius:8px; font-weight:bold; font-size:15px;">
                    Verifikasi Email Pengguna
                </a>
            </div>
            <p style="color:#777777; font-size:13px; line-height:1.6;">
                Jika tombol di atas tidak dapat diklik, silakan salin dan buka link berikut pada browser Anda:
            </p>
            <div style="background:#f4f7fb; padding:12px; border-radius:6px; word-break:break-all; font-size:12px; color:#555555;">
                ' . $verificationLink . '
            </div>
            <p style="color:#777777; font-size:13px; line-height:1.6; margin-top:25px;">
                Link verifikasi ini berlaku selama <strong>24 jam</strong>.
            </p>
            <p style="color:#777777; font-size:13px; line-height:1.6;">
                Setelah email berhasil diverifikasi, pengajuan akun Anda akan ditinjau dan disetujui oleh administrator melalui menu <strong>Kelola User</strong> sebelum dapat digunakan untuk login.
            </p>
        </div>
        <div style="background:#f4f7fb; padding:20px; text-align:center; color:#888888; font-size:12px;">
            © BPS Kabupaten Solok Selatan
        </div>
    </div>
</body>
</html>';

            $mail->AltBody = "Halo $firstName,\n\n" .
                "Terima kasih telah melakukan pendaftaran akun pada Dashboard Admin BPS Kabupaten Solok Selatan.\n\n" .
                "Silakan verifikasi email Anda melalui link berikut:\n\n" .
                $verificationLink . "\n\n" .
                "Link verifikasi berlaku selama 24 jam.\n\n" .
                "Setelah email berhasil diverifikasi, akun Anda masih menunggu persetujuan administrator.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Gagal mengirim email verifikasi: ' . ($mail->ErrorInfo ?? $e->getMessage()));
            return false;
        }
    }
}

/**
 * Kirim email verifikasi untuk admin yang diundang oleh administrator
 */
if (!function_exists('sendAdminVerificationEmail')) {
    function sendAdminVerificationEmail(
        string $email,
        string $verificationToken
    ): bool {
        try {
            $mail = createMailClient();
            $mail->addAddress($email);

            $verificationLink = getMailBaseUrl() . '/verify_user.php?token=' . urlencode($verificationToken);

            $mail->isHTML(true);
            $mail->Subject = 'Verifikasi Akun Administrator - BPS Solok Selatan';
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; background: #f5f7fb; padding: 30px;">
                <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 32px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06);">
                    <div style="text-align: center; margin-bottom: 24px;">
                        <span style="display: inline-block; background: #e0e7ff; color: #4338ca; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Portal Administrator BPS</span>
                        <h2 style="color: #0066b3; margin: 0; font-size: 22px;">Verifikasi Akun Administrator</h2>
                    </div>
                    <p style="font-size: 15px; color: #333; margin-top: 0;">Halo,</p>
                    <p style="line-height: 1.6; color: #444; font-size: 14px;">Alamat email Anda telah didaftarkan sebagai <strong>ADMINISTRATOR</strong> pada sistem <strong>Dashboard Admin BPS Kabupaten Solok Selatan</strong>.</p>
                    <p style="line-height: 1.6; color: #444; font-size: 14px;">Silakan klik tombol di bawah ini untuk memverifikasi alamat email Anda dan melanjutkan proses pembuatan akun Administrator (pengaturan username dan password):</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8') . '" style="display: inline-block; background: #0066b3; color: #ffffff; text-decoration: none; padding: 13px 28px; border-radius: 7px; font-weight: bold; font-size: 15px; box-shadow: 0 2px 6px rgba(0,102,179,0.3);">Verifikasi Email Admin</a>
                    </div>
                    <p style="color: #777; font-size: 13px; line-height: 1.6; margin-bottom: 6px;">Jika tombol di atas tidak dapat diklik langsung, silakan salin dan buka tautan berikut di browser Anda:</p>
                    <div style="background: #f4f7fb; padding: 12px; border-radius: 6px; word-break: break-all; font-size: 12px; color: #0066b3; font-family: monospace; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                        ' . htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8') . '
                    </div>
                    <p style="color: #666; font-size: 13px; line-height: 1.6;">Link verifikasi ini berlaku selama 24 jam.</p>
                    <p style="color: #888; font-size: 12px; line-height: 1.5; background: #fef3c7; color: #92400e; padding: 10px 14px; border-radius: 6px; border-left: 4px solid #f59e0b;">
                        <strong>Catatan Pengujian Lokal:</strong> Jika Anda membuka email ini di smartphone/HP, buka tautan di browser komputer tempat web berjalan, atau ganti <code>localhost</code> dengan alamat IP lokal komputer Anda.
                    </p>
                    <p style="color: #666; font-size: 13px; line-height: 1.6;">Jika Anda tidak merasa didaftarkan sebagai administrator oleh BPS Kabupaten Solok Selatan, silakan abaikan email ini.</p>
                    <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 25px 0;">
                    <p style="color: #888; font-size: 12px; text-align: center; margin: 0;">Badan Pusat Statistik Kabupaten Solok Selatan<br><small style="color: #aaa;">Sistem Pengelolaan Repositori & Layanan Digital</small></p>
                </div>
            </div>';

            $mail->AltBody = "Verifikasi Akun Administrator - BPS Kabupaten Solok Selatan\n\nAnda telah didaftarkan sebagai ADMINISTRATOR pada sistem Dashboard Admin BPS Kabupaten Solok Selatan.\n\nSilakan buka tautan berikut untuk memverifikasi alamat email Anda dan mengatur username serta password admin:\n\n" . $verificationLink . "\n\nLink berlaku selama 24 jam.";

            return $mail->send();
        } catch (Exception $e) {
            error_log('Gagal mengirim email verifikasi admin: ' . ($mail->ErrorInfo ?? $e->getMessage()));
            return false;
        }
    }
}

/**
 * Kirim email verifikasi untuk user yang didaftarkan oleh admin
 */
if (!function_exists('sendUserVerificationEmail')) {
    function sendUserVerificationEmail(
        string $email,
        string $verificationToken
    ): bool {
        try {
            $mail = createMailClient();
            $mail->addAddress($email);

            $verificationLink = getMailBaseUrl() . '/verify_user.php?token=' . urlencode($verificationToken);

            $mail->isHTML(true);
            $mail->Subject = 'Verifikasi Akun User - BPS Solok Selatan';
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; background: #f5f7fb; padding: 30px;">
                <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 30px; border-radius: 10px;">
                    <h2 style="color: #0066b3; margin-bottom: 20px;">Verifikasi Akun User</h2>
                    <p>Halo,</p>
                    <p style="line-height: 1.6;">Anda telah didaftarkan sebagai <strong>USER</strong> pada sistem BPS Kabupaten Solok Selatan.</p>
                    <p style="line-height: 1.6;">Silakan klik tombol di bawah ini untuk memverifikasi alamat email Anda dan melanjutkan proses pendaftaran.</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8') . '" style="display: inline-block; background: #0066b3; color: #ffffff; text-decoration: none; padding: 13px 25px; border-radius: 7px; font-weight: bold;">Verifikasi Email</a>
                    </div>
                    <p style="color: #666; font-size: 13px; line-height: 1.6;">Link verifikasi ini berlaku selama 24 jam.</p>
                    <p style="color: #666; font-size: 13px; line-height: 1.6;">Jika Anda tidak merasa didaftarkan, abaikan email ini.</p>
                    <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 25px 0;">
                    <p style="color: #888; font-size: 12px; text-align: center;">BPS Kabupaten Solok Selatan</p>
                </div>
            </div>';

            $mail->AltBody = "Verifikasi Akun User BPS Kabupaten Solok Selatan\n\nSilakan buka link berikut untuk memverifikasi email Anda:\n\n" . $verificationLink . "\n\nLink berlaku selama 24 jam.";

            return $mail->send();
        } catch (Exception $e) {
            error_log('Gagal mengirim email verifikasi user: ' . ($mail->ErrorInfo ?? $e->getMessage()));
            return false;
        }
    }
}

/**
 * Kirim notifikasi ke email admin utama saat ada user baru mendaftar mandiri
 */
if (!function_exists('sendAdminRegistrationNotification')) {
    function sendAdminRegistrationNotification(
        string $firstName,
        string $lastName,
        string $username,
        string $email
    ): bool {
        try {
            $mail = createMailClient();
            $mail->addAddress(SMTP_FROM_EMAIL, 'Admin Utama');

            $adminUrl = getMailBaseUrl() . '/kelola_admin.php';

            $mail->isHTML(true);
            $mail->Subject = 'Notifikasi Pendaftaran User Baru - BPS Kabupaten Solok Selatan';
            $mail->Body = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin:0; padding:0; background:#f4f8fc; font-family:Arial, sans-serif;">
            <div style="max-width:600px; margin:30px auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 3px 15px rgba(0,0,0,0.08);">
                <div style="background:#0066b3; padding:25px; text-align:center; color:white;">
                    <h2 style="margin:0;">BPS Kabupaten Solok Selatan</h2>
                    <p style="margin:8px 0 0; font-size:14px;">Dashboard Admin</p>
                </div>
                <div style="padding:30px; color:#333;">
                    <h3 style="color:#005a9c; margin-top:0;">Pendaftaran User Baru</h3>
                    <p>Halo Admin Utama,</p>
                    <p>Ada pengguna baru yang mendaftarkan diri sebagai user pada Portal Layanan BPS Kabupaten Solok Selatan.</p>
                    <div style="background:#f4f8fc; border-left:4px solid #0066b3; padding:18px; margin:20px 0;">
                        <p style="margin:0 0 10px;"><strong>Nama:</strong> ' . htmlspecialchars($firstName . ' ' . $lastName, ENT_QUOTES, 'UTF-8') . '</p>
                        <p style="margin:0 0 10px;"><strong>Username:</strong> ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</p>
                        <p style="margin:0;"><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>
                    </div>
                    <p>Akun tersebut saat ini memiliki status:</p>
                    <p style="display:inline-block; background:#fff3cd; color:#856404; padding:8px 14px; border-radius:6px; font-weight:bold;">
                        MENUNGGU PERSETUJUAN
                    </p>
                    <p style="margin-top:25px;">
                        Silakan buka menu <strong>Kelola User</strong> pada Dashboard Admin untuk memproses pendaftaran tersebut.
                    </p>
                    <div style="text-align:center; margin:30px 0 10px;">
                        <a href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block; background:#0066b3; color:white; text-decoration:none; padding:12px 22px; border-radius:7px; font-weight:bold;">
                            Buka Kelola User
                        </a>
                    </div>
                </div>
                <div style="background:#f4f8fc; padding:15px; text-align:center; color:#777; font-size:12px;">
                    Email otomatis dari Dashboard Admin BPS Kabupaten Solok Selatan.
                </div>
            </div>
        </body>
        </html>';

            $mail->AltBody = "Ada pendaftaran akun user baru.\n\n" .
                "Nama: " . $firstName . " " . $lastName . "\n" .
                "Username: " . $username . "\n" .
                "Email: " . $email . "\n\n" .
                "Status: Menunggu persetujuan administrator.\n\n" .
                "Silakan buka menu Kelola User pada Dashboard Admin.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Gagal mengirim notifikasi pendaftaran user: ' . ($mail->ErrorInfo ?? $e->getMessage()));
            return false;
        }
    }
}

/**
 * 5. Mengirimkan email notifikasi ke pegawai bahwa akun telah disetujui oleh admin
 */
if (!function_exists('sendAccountApprovedNotification')) {
    function sendAccountApprovedNotification(
        string $email,
        string $name,
        string $username
    ): bool {
        try {
            $mail = createMailClient();
            $mail->addAddress($email, $name);

            $portalLoginUrl = getMailBaseUrl() . '/login.php';

            $mail->isHTML(true);
            $mail->Subject = 'Akun Anda Telah Disetujui - BPS Kabupaten Solok Selatan';
            $mail->Body = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin:0; padding:0; background:#f4f8fc; font-family:Arial, sans-serif;">
            <div style="max-width:600px; margin:30px auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 3px 15px rgba(0,0,0,0.08);">
                <div style="background:#0066b3; padding:25px; text-align:center; color:white;">
                    <h2 style="margin:0;">BPS Kabupaten Solok Selatan</h2>
                    <p style="margin:8px 0 0; font-size:14px;">Portal Satu Pintu & Dashboard Pegawai</p>
                </div>
                <div style="padding:30px; color:#333;">
                    <h3 style="color:#005a9c; margin-top:0;">Selamat! Akun Anda Telah Aktif</h3>
                    <p>Halo <strong>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                    <p>Pendaftaran akun Anda pada Portal Layanan BPS Kabupaten Solok Selatan telah <strong>disetujui</strong> oleh Administrator.</p>
                    <div style="background:#f0fdf4; border-left:4px solid #16a34a; padding:18px; margin:20px 0; border-radius:4px;">
                        <p style="margin:0 0 8px; color:#166534;"><strong>Status Akun:</strong> AKTIF / DISETUJUI</p>
                        <p style="margin:0; color:#166534;"><strong>Username:</strong> ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</p>
                    </div>
                    <p>Anda sekarang dapat masuk ke portal untuk mengakses katalog layanan statistik dan ruang repositori kerja pribadi Anda.</p>
                    <div style="text-align:center; margin:30px 0 10px;">
                        <a href="' . htmlspecialchars($portalLoginUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block; background:#0066b3; color:white; text-decoration:none; padding:12px 24px; border-radius:7px; font-weight:bold;">
                            Masuk ke Portal
                        </a>
                    </div>
                </div>
                <div style="background:#f4f8fc; padding:15px; text-align:center; color:#777; font-size:12px;">
                    Email resmi dari Badan Pusat Statistik Kabupaten Solok Selatan.
                </div>
            </div>
        </body>
        </html>';

            $mail->AltBody = "Halo " . $name . ",\n\n" .
                "Pendaftaran akun Anda pada Portal Layanan BPS Kabupaten Solok Selatan telah DISETUJUI oleh Administrator.\n" .
                "Username: " . $username . "\n" .
                "Status: Aktif\n\n" .
                "Silakan login melalui tautan: " . $portalLoginUrl;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Gagal mengirim notifikasi persetujuan akun: ' . ($mail->ErrorInfo ?? $e->getMessage()));
            return false;
        }
    }
}

