<?php

require_once '../config/config.php';
require_once '../config/auth.php';

/* =========================
   AMBIL ID
========================= */

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: layanan.php');
    exit;
}


/* =========================
   AMBIL DATA LAYANAN
   LANGSUNG DARI DATABASE
========================= */

$stmt_layanan = $conn->prepare("
    SELECT
        l.id_layanan,
        l.id_kategori,
        l.nama_layanan,
        l.url,
        l.logo,
        k.nama_kategori
    FROM layanan l
    LEFT JOIN kategori k
        ON l.id_kategori = k.id_kategori
    WHERE l.id_layanan = ?
    LIMIT 1
");

$stmt_layanan->execute([$id]);

$layanan = $stmt_layanan->fetch(PDO::FETCH_ASSOC);

if (!$layanan) {
    die('Data layanan tidak ditemukan.');
}


/* =========================
   AMBIL KATEGORI
========================= */

$stmt = $conn->query("
    SELECT id_kategori, nama_kategori
    FROM kategori
    ORDER BY nama_kategori ASC
");

$kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pesan = '';


/* =========================
   PROSES UPDATE
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify()) {
        $pesan = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $nama_layanan = trim($_POST['nama_layanan'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $id_kategori = $_POST['id_kategori'] ?? '';

    $logo = $layanan['logo'] ?? '';


    /* =========================
       VALIDASI
    ========================= */

    if (
        $nama_layanan === '' ||
        $url === '' ||
        $id_kategori === ''
    ) {

        $pesan = 'Semua data wajib diisi.';

    } else {


        /* =========================
           UPLOAD LOGO BARU
        ========================= */

        if (
            isset($_FILES['logo']) &&
            $_FILES['logo']['error'] === UPLOAD_ERR_OK
        ) {
            $folder_upload = __DIR__ . '/uploads/';

            if (!is_dir($folder_upload)) {
                mkdir($folder_upload, 0755, true);
            }

            $nama_asli = basename($_FILES['logo']['name']);
            $ekstensi = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
            $format_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ekstensi, $format_diizinkan, true)) {
                $pesan = 'Format logo harus berupa JPG, JPEG, PNG, atau WEBP.';
            } elseif ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
                $pesan = 'Ukuran file logo maksimal 5 MB.';
            } else {
                $imageInfo = @getimagesize($_FILES['logo']['tmp_name']);
                if ($imageInfo === false) {
                    $pesan = 'File yang diupload bukan gambar yang valid.';
                } else {
                    $nama_file = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ekstensi;
                    $target = $folder_upload . $nama_file;

                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
                        // Hapus file logo lama jika ada dan bukan default
                        if (!empty($layanan['logo'])) {
                            $oldLogo = basename($layanan['logo']);
                            if ($oldLogo !== 'logo_bps.jpg') {
                                $oldFile = $folder_upload . $oldLogo;
                                if (is_file($oldFile)) {
                                    @unlink($oldFile);
                                }
                            }
                        }
                        $logo = $nama_file;
                    }
                }
            }
        }


        /* =========================
           UPDATE DATABASE
        ========================= */

        try {

            $stmt_update = $conn->prepare("
                UPDATE layanan
                SET
                    id_kategori = ?,
                    nama_layanan = ?,
                    url = ?,
                    logo = ?
                WHERE id_layanan = ?
            ");

            $stmt_update->execute([
                (int) $id_kategori,
                $nama_layanan,
                $url,
                $logo,
                (int) $id
            ]);


            /* =========================
               KEMBALI KE KELOLA LAYANAN
            ========================= */

            header('Location: layanan.php');
            exit;

        } catch (PDOException $e) {
            error_log('Gagal memperbarui data layanan: ' . $e->getMessage());
            $pesan = 'Gagal memperbarui data layanan.';
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

<title>Edit Layanan - BPS Solok Selatan</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f4f8fc;
    color: #333;
    overflow-x: hidden;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {
    width: 240px;
    height: 100vh;
    background: #0066b3;
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    padding: 25px 15px;
    z-index: 9999;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.logo {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 40px;
    padding: 0 15px;
}

.menu {
    list-style: none;
}

.menu li {
    margin-bottom: 8px;
    width: 100%;
}

.menu a {
    display: block;
    width: 100%;
    color: #dceeff;
    text-decoration: none;
    padding: 12px 15px;
    border-radius: 8px;
    transition: 0.2s;
}

.menu a:hover {
    background: rgba(255, 255, 255, 0.15);
    color: white;
}

.menu .active {
    background: white;
    color: #0066b3;
    font-weight: bold;
}


/* =========================
   MOBILE MENU
========================= */

.mobile-menu-button {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    width: 45px;
    height: 45px;
    border: none;
    border-radius: 9px;
    background: #0066b3;
    color: white;
    font-size: 24px;
    cursor: pointer;
    z-index: 10001;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
    align-items: center;
    justify-content: center;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
    z-index: 9998;
}

.sidebar-overlay.show {
    display: block;
}


/* =========================
   MAIN
========================= */

.main {
    margin-left: 240px;
    padding: 30px;
    min-width: 0;
}

.header {
    margin-bottom: 25px;
}

.header h1 {
    font-size: 28px;
    margin-bottom: 8px;
    color: #005a9c;
}

.header p {
    color: #6b7280;
    line-height: 1.5;
}


/* =========================
   ALERT
========================= */

.alert {
    max-width: 850px;
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}


/* =========================
   FORM
========================= */

.form-container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    max-width: 850px;
    box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08);
    border-left: 4px solid #0066b3;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    color: #005a9c;
}

input,
select {
    width: 100%;
    padding: 12px;
    border: 1px solid #d6e3ef;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

input:focus,
select:focus {
    outline: none;
    border-color: #0066b3;
    box-shadow: 0 0 0 2px rgba(0, 102, 179, 0.08);
}


/* =========================
   LOGO
========================= */

.current-logo {
    margin-top: 10px;
    margin-bottom: 10px;
    padding: 10px;
    width: fit-content;
    max-width: 100%;
    border: 1px solid #d6e3ef;
    border-radius: 8px;
    background: #f8fbfe;
}

.current-logo img {
    width: 70px;
    height: 70px;
    max-width: 100%;
    object-fit: contain;
    display: block;
}


/* =========================
   BUTTON
========================= */

.buttons {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn {
    padding: 11px 18px;
    border-radius: 6px;
    border: none;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
    text-align: center;
}

.btn-save {
    background: #0066b3;
    color: white;
}

.btn-save:hover {
    background: #005a9c;
}

.btn-cancel {
    background: #e5edf5;
    color: #005a9c;
}

.btn-cancel:hover {
    background: #d6e5f2;
}


/* =====================================================
   RESPONSIVE MOBILE
===================================================== */

@media (max-width: 768px) {

    /* SIDEBAR */

    .sidebar {
        width: 250px;
        height: 100vh;
        transform: translateX(-100%);
        padding: 25px 15px;
    }

    .sidebar.mobile-open {
        transform: translateX(0);
    }


    /* TOMBOL MENU */

    .mobile-menu-button {
        display: flex;
    }


    /* MAIN */

    .main {
        margin-left: 0;
        padding: 75px 15px 25px;
        width: 100%;
    }


    /* HEADER */

    .header {
        margin-bottom: 20px;
    }

    .header h1 {
        font-size: 24px;
    }

    .header p {
        font-size: 14px;
    }


    /* ALERT */

    .alert {
        width: 100%;
        font-size: 14px;
        line-height: 1.5;
    }


    /* FORM */

    .form-container {
        width: 100%;
        max-width: none;
        padding: 20px;
        border-left: 4px solid #0066b3;
    }

    .form-group {
        margin-bottom: 18px;
    }

    label {
        font-size: 14px;
    }

    input,
    select {
        font-size: 14px;
        padding: 12px;
    }


    /* LOGO */

    .current-logo {
        padding: 8px;
    }

    .current-logo img {
        width: 65px;
        height: 65px;
    }


    /* BUTTON */

    .buttons {
        flex-direction: column;
        gap: 10px;
    }

    .btn {
        width: 100%;
        padding: 12px;
    }

}


/* =====================================================
   HP SANGAT KECIL
===================================================== */

@media (max-width: 480px) {

    .main {
        padding-left: 12px;
        padding-right: 12px;
    }

    .header h1 {
        font-size: 22px;
    }

    .header p {
        font-size: 13px;
    }

    .form-container {
        padding: 18px 15px;
    }

}

</style>

</head>

<body>


<!-- =====================================================
     TOMBOL MENU MOBILE
===================================================== -->

<button
    type="button"
    class="mobile-menu-button"
    id="mobileMenuButton"
    onclick="toggleMobileSidebar()"
    aria-label="Buka menu"
>
    ☰
</button>


<!-- =====================================================
     OVERLAY MOBILE
===================================================== -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
    onclick="closeMobileSidebar()"
></div>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar" id="sidebar">

    <div class="logo">
        Dashboard Admin
    </div>

    <ul class="menu">

        <li>
            <a href="index.php">
                Dashboard
            </a>
        </li>

        <li>
            <a href="layanan.php" class="active">
                Kelola Layanan
            </a>
        </li>

        <li>
            <a href="kategori.php">
                Kategori
            </a>
        </li>

        <li>
            <a href="kelola_admin.php">
                Kelola Admin
            </a>
        </li>

        <li>
            <a href="pengaturan.php">
                Pengaturan
            </a>
        </li>

    </ul>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">

    <div class="header">

        <h1>Edit Layanan</h1>

        <p>
            Ubah informasi layanan BPS Kabupaten Solok Selatan.
        </p>

    </div>


    <?php if ($pesan !== ''): ?>

        <div class="alert">
            <?= htmlspecialchars($pesan); ?>
        </div>

    <?php endif; ?>


    <div class="form-container">

        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field(); ?>

            <div class="form-group">

                <label>
                    Nama Layanan
                </label>

                <input
                    type="text"
                    name="nama_layanan"
                    value="<?= htmlspecialchars($layanan['nama_layanan'] ?? ''); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    URL / Link
                </label>

                <input
                    type="url"
                    name="url"
                    value="<?= htmlspecialchars($layanan['url'] ?? ''); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Kategori
                </label>

                <select
                    name="id_kategori"
                    required
                >

                    <?php foreach ($kategori as $k): ?>

                        <option
                            value="<?= $k['id_kategori']; ?>"
                            <?= $k['id_kategori'] == ($layanan['id_kategori'] ?? '') ? 'selected' : ''; ?>
                        >
                            <?= htmlspecialchars($k['nama_kategori']); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Logo
                </label>

                <?php if (!empty($layanan['logo'])): ?>

                    <div class="current-logo">

                        <img
                            src="uploads/<?= htmlspecialchars($layanan['logo']); ?>"
                            alt="Logo Layanan"
                        >

                    </div>

                <?php endif; ?>

                <br>

                <input
                    type="file"
                    name="logo"
                    accept=".jpg,.jpeg,.png,.webp"
                >

            </div>


            <div class="buttons">

                <button
                    type="submit"
                    class="btn btn-save"
                >
                    Simpan Perubahan
                </button>

                <a
                    href="layanan.php"
                    class="btn btn-cancel"
                >
                    Batal
                </a>

            </div>

        </form>

    </div>

</main>


<script>

/* =========================
   SIDEBAR MOBILE
========================= */

function toggleMobileSidebar() {

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const button = document.getElementById('mobileMenuButton');

    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('show');

    if (sidebar.classList.contains('mobile-open')) {

        button.innerHTML = '✕';
        button.setAttribute('aria-label', 'Tutup menu');

    } else {

        button.innerHTML = '☰';
        button.setAttribute('aria-label', 'Buka menu');

    }

}


/* =========================
   TUTUP SIDEBAR MOBILE
========================= */

function closeMobileSidebar() {

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const button = document.getElementById('mobileMenuButton');

    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('show');

    button.innerHTML = '☰';
    button.setAttribute('aria-label', 'Buka menu');

}


/* =========================
   TUTUP SIDEBAR SETELAH
   PILIH MENU
========================= */

document.querySelectorAll('.menu a').forEach(function(link) {

    link.addEventListener('click', function() {

        if (window.innerWidth <= 768) {
            closeMobileSidebar();
        }

    });

});

</script>

</body>

</html>