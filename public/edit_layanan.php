<?php

require_once '../config/config.php';
require_once '../config/auth.php';
require_once __DIR__ . '/partials/icons.php';

/* =========================
   CEK LOGIN ADMIN / USER
========================= */

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$is_real_admin = is_admin_user();
$active_role = auth_active_role();
$admin_role = $active_role;
$user_id = (int) $_SESSION['admin_id'];

/* =========================
   AMBIL ID & DATA LAYANAN
========================= */

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: layanan.php');
    exit;
}

$stmt_layanan = $conn->prepare("
    SELECT
        l.id_layanan,
        l.id_kategori,
        l.created_by,
        l.nama_layanan,
        l.deskripsi_layanan,
        l.url,
        l.logo,
        l.akses,
        l.vpn,
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
    header('Location: layanan.php');
    exit;
}

// User biasa hanya boleh mengedit layanan buatannya sendiri
if (!$is_real_admin && (int) ($layanan['created_by'] ?? 0) !== $user_id) {
    header('Location: layanan.php');
    exit;
}

/* =========================
   AMBIL DATA KATEGORI
========================= */

$stmt = $conn->query("
    SELECT id_kategori, nama_kategori
    FROM kategori
    ORDER BY id_kategori ASC
");

$kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   NILAI FORM & STATE
========================= */

$pesan_error = '';

$nama_layanan = $layanan['nama_layanan'] ?? '';
$deskripsi_layanan = $layanan['deskripsi_layanan'] ?? '';
$url = $layanan['url'] ?? '';
$id_kategori = $layanan['id_kategori'] ?? '';
$akses = $layanan['akses'] ?? 'Public';
$vpn = $layanan['vpn'] ?? 'No';

/* =========================
   PROSES UPDATE
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $pesan_error = 'Sesi keamanan tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $nama_layanan = trim($_POST['nama_layanan'] ?? '');
        $deskripsi_layanan = trim($_POST['deskripsi_layanan'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $id_kategori = (int) ($_POST['id_kategori'] ?? 0);
        $akses = trim($_POST['akses'] ?? 'Public');
        $vpn = trim($_POST['vpn'] ?? 'No');

        if ($nama_layanan === '' || $deskripsi_layanan === '' || $url === '' || $id_kategori <= 0) {
            $pesan_error = 'Nama layanan, deskripsi, URL, dan kategori wajib diisi.';
        } else {
            $logo = $layanan['logo'];

            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $max_size = 2 * 1024 * 1024; // 2MB
                if ($_FILES['logo']['size'] > $max_size) {
                    $pesan_error = 'Ukuran berkas logo maksimal 2 MB.';
                } else {
                    $folder_upload = __DIR__ . '/uploads/';
                    if (!is_dir($folder_upload)) {
                        mkdir($folder_upload, 0777, true);
                    }
                    $nama_asli = basename($_FILES['logo']['name']);
                    $ekstensi = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
                    $format_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];

                    if (in_array($ekstensi, $format_diizinkan, true)) {
                        $nama_file = 'layanan_' . time() . '_' . random_int(100, 999) . '.' . $ekstensi;
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], $folder_upload . $nama_file)) {
                            if (!empty($logo) && $logo !== 'logo_bps.jpg' && file_exists($folder_upload . $logo)) {
                                @unlink($folder_upload . $logo);
                            }
                            $logo = $nama_file;
                        }
                    } else {
                        $pesan_error = 'Format berkas logo harus berupa JPG, JPEG, PNG, atau WebP.';
                    }
                }
            } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_INI_SIZE) {
                $pesan_error = 'Ukuran berkas logo melebihi batas upload server (maksimal 2 MB).';
            }

            if ($pesan_error === '') {
                if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                    $url = 'https://' . $url;
                }

                try {
                    $stmt_update = $conn->prepare("
                        UPDATE layanan
                        SET
                            id_kategori = ?,
                            akses = ?,
                            vpn = ?,
                            nama_layanan = ?,
                            deskripsi_layanan = ?,
                            url = ?,
                            logo = ?
                        WHERE id_layanan = ?
                    ");

                    $stmt_update->execute([
                        $id_kategori,
                        $akses,
                        $vpn,
                        $nama_layanan,
                        $deskripsi_layanan,
                        $url,
                        $logo,
                        $id
                    ]);

                    header('Location: layanan.php');
                    exit;
                } catch (PDOException $e) {
                    $pesan_error = 'Gagal memperbarui data layanan: ' . $e->getMessage();
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
    <title>Edit Layanan - BPS Solok Selatan</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
            background: #f8fafc;
            color: #334155;
            min-height: 100vh;
        }

        /* SIDEBAR */
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
            font-size: 14px;
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

        /* MOBILE MENU */
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

        .sidebar-overlay.show { display: block; }

        /* MAIN CONTENT */
        .main {
            margin-left: 240px;
            padding: 30px 40px;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-left h1 {
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .header-left p {
            font-size: 14px;
            color: #64748b;
        }

        /* FORM CARD (MATCH REPOSITORI PORTAL MODAL-CARD) */
        .form-card {
            background: white;
            border-radius: 14px;
            max-width: 760px;
            width: 100%;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .form-card-header {
            padding: 18px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-card-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .form-card-body {
            padding: 24px;
        }

        .form-card-footer {
            padding: 14px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* FORM CONTROLS */
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            outline: none;
            font-family: inherit;
            background: #ffffff;
            color: #1e293b;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #0066b3;
            box-shadow: 0 0 0 3px rgba(0, 102, 179, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* LOGO PREVIEW */
        .current-logo-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 8px;
        }

        .current-logo-box img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 6px;
        }

        .current-logo-text {
            font-size: 12.5px;
            color: #64748b;
        }

        /* VALIDATION STYLES */
        .input-error {
            border-color: #ef4444 !important;
            background-color: #fffbfa !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        }

        .modal-form-alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-size: 13.5px;
            font-weight: 500;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .field-error-text {
            color: #dc2626;
            font-size: 12px;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }

        /* BUTTONS (MATCH REPOSITORI PORTAL) */
        .category-select {
            padding: 9px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            background: #fff;
            color: #334155;
            cursor: pointer;
            outline: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .category-select:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .btn-tambah {
            background: #0066b3;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(0, 102, 179, 0.25);
            transition: background 0.2s;
            text-decoration: none;
        }

        .btn-tambah:hover {
            background: #004c85;
        }

        .alert-error {
            max-width: 760px;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .mobile-menu-button {
                display: flex;
            }
            .main {
                margin-left: 0;
                padding: 75px 15px 25px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- TOMBOL MENU MOBILE -->
<button
    type="button"
    class="sidebar-toggle-btn mobile-menu-button menu-toggle"
    id="mobileMenuButton"
    onclick="toggleSidebar()"
    aria-label="Toggle navigasi"
>
    <?= render_icon('menu', 'mobile-icon', 20); ?>
</button>

<!-- SIDEBAR -->
<?php
$active_nav = 'layanan';
include __DIR__ . '/partials/navbar.php';
?>

<!-- MAIN -->
<main class="main">
    <div class="header">
        <div class="header-left">
            <h1>Edit Layanan</h1>
            <p>Perbarui informasi layanan yang sudah terdaftar dalam sistem informasi BPS Solok Selatan.</p>
        </div>
        <?php include __DIR__ . '/partials/account_dropdown.php'; ?>
    </div>

    <?php if ($pesan_error !== ''): ?>
        <div class="alert-error">
            ⚠️ <?= htmlspecialchars($pesan_error); ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header">
            <h3>Edit Layanan</h3>
        </div>
        <form id="formEditLayanan" method="POST" action="edit_layanan.php?id=<?= $id; ?>" enctype="multipart/form-data" novalidate>
            <?= csrf_field(); ?>
            <div class="form-card-body">
                <div class="modal-form-alert" id="formAlertBox"></div>

                <div class="form-group">
                    <label for="nama_layanan">Nama Layanan *</label>
                    <input
                        type="text"
                        id="nama_layanan"
                        name="nama_layanan"
                        value="<?= htmlspecialchars($nama_layanan); ?>"
                        required
                        placeholder="Contoh: SIPP - Portal Pelaporan Perusahaan"
                    >
                </div>

                <div class="form-group">
                    <label for="deskripsi_layanan">Deskripsi Layanan *</label>
                    <textarea
                        id="deskripsi_layanan"
                        name="deskripsi_layanan"
                        rows="3"
                        required
                        placeholder="Jelaskan fungsi dan tujuan layanan ini..."
                    ><?= htmlspecialchars($deskripsi_layanan); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="url">URL / Tautan Link *</label>
                    <input
                        type="url"
                        id="url"
                        name="url"
                        value="<?= htmlspecialchars($url); ?>"
                        required
                        placeholder="https://contoh.bps.go.id"
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="id_kategori">Kategori *</label>
                        <select id="id_kategori" name="id_kategori" required>
                            <option value="">Pilih Kategori...</option>
                            <?php foreach ($kategori as $k): ?>
                                <option
                                    value="<?= (int) $k['id_kategori']; ?>"
                                    <?= ((string) $id_kategori === (string) $k['id_kategori']) ? 'selected' : ''; ?>
                                >
                                    <?= htmlspecialchars($k['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="akses">Tipe Akses *</label>
                        <select id="akses" name="akses" required>
                            <option value="Public" <?= ($akses === 'Public') ? 'selected' : ''; ?>>Public (Umum)</option>
                            <option value="Internal" <?= ($akses === 'Internal') ? 'selected' : ''; ?>>Internal (Pegawai BPS)</option>
                            <option value="Private" <?= ($akses === 'Private') ? 'selected' : ''; ?>>Private</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="vpn">Memerlukan VPN? *</label>
                        <select id="vpn" name="vpn" required>
                            <option value="No" <?= ($vpn === 'No') ? 'selected' : ''; ?>>No (Tidak)</option>
                            <option value="Yes" <?= ($vpn === 'Yes') ? 'selected' : ''; ?>>Yes (Ya)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="logo">Logo / Gambar Baru (Opsional)</label>
                        <?php if (!empty($layanan['logo']) && file_exists(__DIR__ . '/uploads/' . $layanan['logo'])): ?>
                            <div class="current-logo-box">
                                <img src="uploads/<?= htmlspecialchars($layanan['logo']); ?>" alt="Logo Saat Ini">
                                <span class="current-logo-text">Logo aktif saat ini. Unggah file baru untuk mengganti.</span>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>
            </div>

            <div class="form-card-footer">
                <a href="layanan.php" class="category-select">Batal</a>
                <button type="submit" class="btn-tambah">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formEditLayanan');
    if (!form) return;

    form.setAttribute('novalidate', 'true');

    form.addEventListener('submit', function(e) {
        const alertBox = document.getElementById('formAlertBox');

        form.querySelectorAll('.field-error-text').forEach(el => el.remove());
        form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

        const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
        let hasError = false;
        let firstInvalid = null;

        inputs.forEach(input => {
            const formGroup = input.closest('.form-group');
            const label = formGroup ? formGroup.querySelector('label') : null;
            const labelText = label ? label.textContent : '';

            const isOptional = /opsional|optional/i.test(labelText) || input.dataset.optional === 'true';

            if (!isOptional) {
                const val = (input.value || '').trim();
                if (val === '') {
                    hasError = true;
                    input.classList.add('input-error');

                    const cleanLabel = labelText.replace(/[\*\(\)]/g, '').trim() || 'Kolom ini';

                    const errSpan = document.createElement('span');
                    errSpan.className = 'field-error-text';
                    errSpan.innerHTML = `⚠️ ${cleanLabel} wajib diisi.`;
                    if (formGroup) {
                        formGroup.appendChild(errSpan);
                    }

                    if (!firstInvalid) firstInvalid = input;
                }
            }
        });

        if (hasError) {
            e.preventDefault();
            e.stopPropagation();
            if (alertBox) {
                alertBox.style.display = 'flex';
                alertBox.innerHTML = `<span>⚠️</span><span><strong>Peringatan:</strong> Harap lengkapi semua kolom yang wajib diisi (kecuali yang bertanda opsional)!</span>`;
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            if (firstInvalid) {
                firstInvalid.focus();
            }
            return false;
        } else {
            if (alertBox) {
                alertBox.style.display = 'none';
            }
        }
    });

    form.querySelectorAll('input, select, textarea').forEach(input => {
        const clearError = function() {
            if (input.value.trim() !== '') {
                input.classList.remove('input-error');
                const formGroup = input.closest('.form-group');
                if (formGroup) {
                    const err = formGroup.querySelector('.field-error-text');
                    if (err) err.remove();
                }
                if (!form.querySelector('.input-error')) {
                    const alertBox = document.getElementById('formAlertBox');
                    if (alertBox) alertBox.style.display = 'none';
                }
            }
        };
        input.addEventListener('input', clearError);
        input.addEventListener('change', clearError);
    });
});
</script>

</body>
</html>