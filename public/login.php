<?php

require_once '../config/config.php';

$error = '';

/* =====================================================
   JIKA SUDAH LOGIN
===================================================== */

if (!empty($_SESSION['admin_id'])) {

    $role = $_SESSION['admin_role'] ?? '';

    if (
        $role === 'ADMIN IT' ||
        $role === 'ADMIN DASHBOARD'
    ) {
        header('Location: index.php');
        exit;
    }

    /*
     * USER APP PORTAL tidak boleh masuk
     * ke Dashboard Admin.
     */
    session_unset();
    session_destroy();

    /*
     * Mulai session kembali agar halaman login
     * tetap bisa digunakan.
     */
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}


/* =====================================================
   PROSES LOGIN
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF jika token dikirimkan
    if (isset($_POST['csrf_token']) && !csrf_verify()) {
        $error = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            /* =================================================
               AMBIL DATA ADMIN (Username atau Email)
            ================================================= */
            $stmt = $conn->prepare("
                SELECT
                    id_admin,
                    first_name,
                    last_name,
                    username,
                    email,
                    password,
                    status,
                    role,
                    email_verified
                FROM admin
                WHERE username = ? OR email = ?
                LIMIT 1
            ");

            $stmt->execute([
                $username,
                $username
            ]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                $error = 'Username atau password salah.';
            } else {

            /* =================================================
               CEK PASSWORD
               
               Mendukung:
               1. password_hash() / password_verify()
               2. SHA-256 untuk akun lama
            ================================================= */

            $passwordValid = false;


            /* PASSWORD HASH BARU */

            if (
                !empty($admin['password']) &&
                password_verify(
                    $password,
                    $admin['password']
                )
            ) {

                $passwordValid = true;

            }


            /* SHA-256 AKUN LAMA */

            if (
                !$passwordValid &&
                !empty($admin['password']) &&
                hash_equals(
                    $admin['password'],
                    hash('sha256', $password)
                )
            ) {

                $passwordValid = true;

            }


            if (!$passwordValid) {

                $error =
                    'Username atau password salah.';

            } elseif (
                (int) $admin['email_verified'] !== 1
            ) {

                $error =
                    'Email Anda belum diverifikasi. Silakan cek email untuk melakukan verifikasi.';

            } elseif (
                $admin['status'] === 'pending'
            ) {

                $error =
                    'Email sudah diverifikasi. Akun Anda masih menunggu persetujuan administrator.';

            } elseif (
                $admin['status'] === 'rejected'
            ) {

                $error =
                    'Pendaftaran akun Anda ditolak oleh administrator.';

            } elseif (
                $admin['status'] !== 'approved'
            ) {

                $error =
                    'Status akun tidak valid. Silakan hubungi administrator.';

            } elseif (
                $admin['role'] !== 'ADMIN IT' &&
                $admin['role'] !== 'ADMIN DASHBOARD'
            ) {

                $error =
                    'Akun USER APP PORTAL tidak dapat login ke Dashboard Admin.';

            } else {

                /* =================================================
                   LOGIN BERHASIL
                ================================================= */

                /* Auto-upgrade legacy hash to modern bcrypt hash */
                if (password_needs_rehash($admin['password'], PASSWORD_DEFAULT) || !password_verify($password, $admin['password'])) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upStmt = $conn->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
                    $upStmt->execute([$newHash, (int)$admin['id_admin']]);
                }

                session_regenerate_id(true);


                $_SESSION['admin_id'] =
                    (int) $admin['id_admin'];

                $_SESSION['admin_username'] =
                    $admin['username'] ?? '';

                $_SESSION['admin_role'] =
                    $admin['role'] ?? '';

                $_SESSION['admin_status'] =
                    $admin['status'] ?? '';

                $_SESSION['admin_email'] =
                    $admin['email'] ?? '';


                /* =================================================
                   REDIRECT
                ================================================= */

                header('Location: index.php');
                exit;
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login Admin - BPS Solok Selatan
    </title>

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

            box-shadow:
                0 8px 25px
                rgba(0, 0, 0, 0.12);
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
                src="assets/logo_bps.jpg"
                alt="Logo BPS"
            >

            <h1>
                Dashboard Admin
            </h1>

            <p>
                BPS Kabupaten Solok Selatan
            </p>

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
                    autocomplete="username"
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
                    autocomplete="current-password"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn-login"
            >
                Login
            </button>

        </form>


        <div class="register-link">

            Belum punya akun?

            <a href="register.php">
                Daftar sekarang
            </a>

        </div>

    </div>

</body>

</html>