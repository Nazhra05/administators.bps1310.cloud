<?php

require_once '../config/config.php';
require_once '../config/auth.php';

$is_real_admin = is_admin_user();
$active_role = auth_active_role();

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $active_role;
$active_nav = 'kategori';

$error = '';

/* Proses simpan kategori */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');

        if ($nama_kategori === '') {
            $error = 'Nama kategori wajib diisi.';
        } else {
            /* Cek apakah kategori sudah ada */
            $cek = $conn->prepare("
                SELECT COUNT(*)
                FROM kategori
                WHERE nama_kategori = ?
            ");
            $cek->execute([$nama_kategori]);

            if ($cek->fetchColumn() > 0) {
                $error = 'Kategori tersebut sudah ada.';
            } else {
                /* Simpan kategori */
                $stmt = $conn->prepare("
                    INSERT INTO kategori (nama_kategori)
                    VALUES (?)
                ");
                $stmt->execute([$nama_kategori]);

                header('Location: kategori.php');
                exit;
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
    <title>Tambah Kategori - BPS Solok Selatan</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <main class="main-content">
        <header class="header">
            <div>
                <h1>Tambah Kategori</h1>
                <p>Tambahkan kategori baru untuk pengelompokan layanan katalog.</p>
            </div>
            <?php include __DIR__ . '/partials/account_dropdown.php'; ?>
        </header>

        <section class="card-form">
            <?php if (!empty($error)): ?>
                <div class="alert-box alert-error" style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="tambah_kategori.php">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">

                <div class="form-group">
                    <label for="nama_kategori">Nama Kategori <span style="color:#dc2626;">*</span></label>
                    <input
                        type="text"
                        id="nama_kategori"
                        name="nama_kategori"
                        class="form-control"
                        placeholder="Contoh: Statistik Sosial, IPDS, Nerwilis"
                        value="<?= htmlspecialchars($_POST['nama_kategori'] ?? ''); ?>"
                        required
                        autofocus
                    >
                </div>

                <div class="form-actions" style="margin-top:25px;display:flex;gap:12px;">
                    <a href="kategori.php" class="btn-batal">Batal</a>
                    <button type="submit" class="btn-simpan">Simpan Kategori</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>