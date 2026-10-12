<?php

require_once __DIR__ . '/../config/config.php';

$message = '';
$type = '';
$prefillEmail = trim($_GET['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
        $type = 'error';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Silakan masukkan alamat email yang valid.';
            $type = 'error';
        } else {
            $stmt = $conn->prepare("
                SELECT id_admin, username, email, first_name, last_name, role, password, email_verified
                FROM admin
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Pesan aman: tidak membocorkan keberadaan email
                $message = 'Jika email terdaftar pada sistem kami dan belum diverifikasi, tautan verifikasi baru telah dikirimkan ke kotak masuk Anda.';
                $type = 'success';
            } elseif ((int)($user['email_verified'] ?? 0) === 1) {
                $message = 'Email ini sudah berhasil diverifikasi sebelumnya. Anda dapat langsung masuk ke dashboard atau portal.';
                $type = 'info';
            } else {
                try {
                    $newToken = bin2hex(random_bytes(32));
                    $stmtUpdate = $conn->prepare("
                        UPDATE admin
                        SET verification_token = ?,
                            verification_expires = DATE_ADD(NOW(), INTERVAL 24 HOUR)
                        WHERE id_admin = ?
                    ");
                    $stmtUpdate->execute([$newToken, $user['id_admin']]);

                    require_once __DIR__ . '/../app/services/mail_service.php';

                    if (empty($user['password']) && strtoupper(trim($user['role'] ?? '')) === 'ADMIN') {
                        sendAdminVerificationEmail($user['email'], $newToken);
                    } elseif (empty($user['password'])) {
                        sendUserVerificationEmail($user['email'], $newToken);
                    } else {
                        $fName = !empty($user['first_name']) ? $user['first_name'] : $user['username'];
                        sendVerificationEmail($user['email'], $fName, $newToken);
                    }

                    log_audit('RESEND_VERIFICATION', 'admin', (int) $user['id_admin'], 'Pengiriman ulang tautan verifikasi email ke ' . $user['email']);

                    $message = 'Tautan verifikasi baru berhasil dikirim ke alamat email Anda. Silakan periksa kotak masuk atau folder spam.';
                    $type = 'success';
                } catch (Throwable $e) {
                    error_log('Gagal mengirim ulang email verifikasi: ' . $e->getMessage());
                    $message = 'Terjadi kesalahan sistem saat mengirim email. Silakan coba lagi nanti.';
                    $type = 'error';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Ulang Verifikasi Email - Dashboard Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #eef5ff, #f8fbff);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            padding: 40px 32px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        .logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 16px;
        }
        h1 {
            color: #005baa;
            font-size: 24px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        p.subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .message {
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 24px;
            text-align: left;
            line-height: 1.5;
        }
        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .message.success {
            background: #f0fdf4;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        .message.info {
            background: #eff6ff;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            transition: border-color 0.15s ease;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: #005baa;
            box-shadow: 0 0 0 3px rgba(0, 91, 170, 0.15);
        }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 13px;
            background: #005baa;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-submit:hover {
            background: #004480;
        }
        .links-group {
            margin-top: 24px;
            display: flex;
            justify-content: center;
            gap: 16px;
            font-size: 13px;
        }
        .links-group a {
            color: #005baa;
            text-decoration: none;
            font-weight: 500;
        }
        .links-group a:hover {
            text-decoration: underline;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="uploads/logo_bps.jpg" alt="Logo BPS" class="logo" onerror="this.style.display='none';">
        <h1>Kirim Ulang Verifikasi</h1>
        <p class="subtitle">Masukkan alamat email Anda untuk menerima tautan aktivasi akun yang baru.</p>

        <?php if (!empty($message)): ?>
            <div class="message <?= htmlspecialchars($type); ?>">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="resend_verification.php">
            <?= csrf_field(); ?>
            <div class="form-group">
                <label for="email">Alamat Email Terdaftar</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="nama@bps.go.id"
                    value="<?= htmlspecialchars($prefillEmail, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>
            <button type="submit" class="btn-submit">Kirim Tautan Verifikasi Baru</button>
        </form>

        <div class="links-group">
            <a href="login.php">Kembali ke Login</a>
            <span>•</span>
            <a href="register.php">Daftar Akun Baru</a>
        </div>

        <div class="footer">
            Badan Pusat Statistik Kabupaten Solok Selatan
        </div>
    </div>
</body>
</html>
