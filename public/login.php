<?php

require_once '../config/config.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify()) {
        $error = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = 'Username dan password wajib diisi.';

    } else {

        $stmt = $conn->prepare("
            SELECT
                id_admin,
                username,
                email,
                password,
                status,
                email_verified
            FROM admin
            WHERE username = ? OR email = ?
            LIMIT 1
        ");

        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        $passwordValid = false;
        if ($admin) {
            $sha256 = hash('sha256', $password);
            if (hash_equals($admin['password'], $sha256)) {
                $passwordValid = true;
            } elseif (password_verify($password, $admin['password'])) {
                $passwordValid = true;
            }
        }

        if (!$admin || !$passwordValid) {

            $error = 'Username atau password salah.';

        } elseif ((int)$admin['email_verified'] !== 1) {

            $error = 'Email Anda belum diverifikasi. Silakan cek email untuk melakukan verifikasi.';

        } elseif ($admin['status'] === 'pending') {

            $error = 'Email sudah diverifikasi. Akun Anda masih menunggu persetujuan administrator.';

        } elseif ($admin['status'] === 'rejected') {

            $error = 'Pendaftaran akun Anda ditolak oleh administrator.';

        } elseif ($admin['status'] === 'approved') {

            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id_admin'];
            $_SESSION['admin_username'] = $admin['username'];

            header('Location: index.php');
            exit;

        } else {

            $error = 'Status akun tidak valid. Silakan hubungi administrator.';
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

    <title>Login Admin - BPS Solok Selatan</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #eef5ff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            width: 400px;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo img {
            width: 90px;
            height: 90px;
            object-fit: contain;
            margin-bottom: 15px;
        }

        .logo h1 {
            color: #005baa;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #005baa;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #005baa;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #00498f;
        }

        .error {
            background: #ffe5e5;
            color: #c62828;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        /* LINK DAFTAR */
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .register-link a {
            color: #005baa;
            font-weight: bold;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

    </style>

</head>

<body>

    <div class="login-container">

        <div class="logo">

            <img
                src="uploads/logo_bps.jpg"
                alt="Logo BPS"
            >

            <h1>Dashboard Admin</h1>

            <p>BPS Kabupaten Solok Selatan</p>

        </div>

        <?php if ($error !== ''): ?>

            <div class="error">
                <?= htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">
            <?= csrf_field(); ?>

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Masukkan username"
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
                    placeholder="Masukkan password"
                    required
                >

            </div>

            <button type="submit" class="btn-login">
                Login
            </button>

        </form>

        <!-- LINK DAFTAR -->
        <div class="register-link">
            Belum punya akun?
            <a href="register.php">Daftar sekarang</a>
        </div>

    </div>

</body>

</html>