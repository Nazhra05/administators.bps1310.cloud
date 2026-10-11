<?php
/**
 * Halaman Kelola Repositori Private - Dashboard User
 * BPS Kabupaten Solok Selatan
 * Mendukung 7 Kategori Koleksi Pribadi:
 * 1. Favorite
 * 2. Kumpulan Link Website
 * 3. Kumpulan Link Data
 * 4. Aplikasi Mobile
 * 5. Link Drive (Folder)
 * 6. Link Foto
 * 7. Link Lainnya
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/user_repo.php';
require_once __DIR__ . '/partials/icons.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['admin_id'];
$is_real_admin = is_admin_user();
$active_role = auth_active_role();
$admin_username = $_SESSION['admin_username'] ?? 'User';

$pesan_sukses = '';
$pesan_error = '';

/* =====================================================
   HANDLE POST FORM SUBMISSIONS (Fallback Non-AJAX)
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Hapus dari Favorite
    if ($action === 'remove_favorite') {
        $id_layanan = (int) ($_POST['id_layanan'] ?? 0);
        if ($id_layanan > 0) {
            user_remove_favorite($user_id, $id_layanan);
            $pesan_sukses = 'Layanan berhasil dihapus dari Favorite.';
        }
    }

    // 2. Tambah Link Website
    elseif ($action === 'add_link') {
        $nama = trim($_POST['nama_website'] ?? '');
        $url = trim($_POST['url_website'] ?? '');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($nama === '' || $url === '') {
            $pesan_error = 'Nama website dan URL wajib diisi.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_add_link($user_id, $nama, $url, $desc);
            $pesan_sukses = 'Link website baru berhasil ditambahkan!';
        }
    }

    // 3. Edit Link Website
    elseif ($action === 'edit_link') {
        $id_link = (int) ($_POST['id_link'] ?? 0);
        $nama = trim($_POST['nama_website'] ?? '');
        $url = trim($_POST['url_website'] ?? '');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($id_link <= 0 || $nama === '' || $url === '') {
            $pesan_error = 'Data link website tidak lengkap.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_update_link($user_id, $id_link, $nama, $url, $desc);
            $pesan_sukses = 'Link website berhasil diperbarui!';
        }
    }

    // 4. Hapus Link Website
    elseif ($action === 'delete_link') {
        $id_link = (int) ($_POST['id_link'] ?? 0);
        if ($id_link > 0) {
            user_delete_link($user_id, $id_link);
            $pesan_sukses = 'Link website berhasil dihapus.';
        }
    }

    // 5. Tambah Link Data
    elseif ($action === 'add_data_link') {
        $nama = trim($_POST['nama_dokumen'] ?? '');
        $url = trim($_POST['url_dokumen'] ?? '');
        $jenis = trim($_POST['jenis_dokumen'] ?? 'Lainnya');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($nama === '' || $url === '') {
            $pesan_error = 'Nama dokumen dan URL wajib diisi.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_add_data_link($user_id, $nama, $url, $jenis, $desc);
            $pesan_sukses = 'Link data/dokumen baru berhasil disimpan!';
        }
    }

    // 6. Edit Link Data
    elseif ($action === 'edit_data_link') {
        $id_data = (int) ($_POST['id_data_link'] ?? 0);
        $nama = trim($_POST['nama_dokumen'] ?? '');
        $url = trim($_POST['url_dokumen'] ?? '');
        $jenis = trim($_POST['jenis_dokumen'] ?? 'Lainnya');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($id_data <= 0 || $nama === '' || $url === '') {
            $pesan_error = 'Data link dokumen tidak lengkap.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_update_data_link($user_id, $id_data, $nama, $url, $jenis, $desc);
            $pesan_sukses = 'Link data/dokumen berhasil diperbarui!';
        }
    }

    // 7. Hapus Link Data
    elseif ($action === 'delete_data_link') {
        $id_data = (int) ($_POST['id_data_link'] ?? 0);
        if ($id_data > 0) {
            user_delete_data_link($user_id, $id_data);
            $pesan_sukses = 'Link data/dokumen berhasil dihapus.';
        }
    }

    // 8. Tambah Aplikasi Mobile
    elseif ($action === 'add_mobile_app') {
        $nama = trim($_POST['nama_aplikasi'] ?? '');
        $url = trim($_POST['url_aplikasi'] ?? '');
        $platform = trim($_POST['platform'] ?? 'Android');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($nama === '' || $url === '') {
            $pesan_error = 'Nama aplikasi dan tautan aplikasi wajib diisi.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_add_mobile_app($user_id, $nama, $url, $platform, $desc);
            $pesan_sukses = 'Data aplikasi mobile berhasil disimpan!';
        }
    }

    // 9. Edit Aplikasi Mobile
    elseif ($action === 'edit_mobile_app') {
        $id_app = (int) ($_POST['id_app'] ?? 0);
        $nama = trim($_POST['nama_aplikasi'] ?? '');
        $url = trim($_POST['url_aplikasi'] ?? '');
        $platform = trim($_POST['platform'] ?? 'Android');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($id_app <= 0 || $nama === '' || $url === '') {
            $pesan_error = 'Data aplikasi mobile tidak lengkap.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_update_mobile_app($user_id, $id_app, $nama, $url, $platform, $desc);
            $pesan_sukses = 'Data aplikasi mobile berhasil diperbarui!';
        }
    }

    // 10. Hapus Aplikasi Mobile
    elseif ($action === 'delete_mobile_app') {
        $id_app = (int) ($_POST['id_app'] ?? 0);
        if ($id_app > 0) {
            user_delete_mobile_app($user_id, $id_app);
            $pesan_sukses = 'Data aplikasi mobile berhasil dihapus.';
        }
    }

    // 11. Tambah Link Drive (Folder)
    elseif ($action === 'add_drive_link') {
        $nama = trim($_POST['nama_folder'] ?? '');
        $url = trim($_POST['url_folder'] ?? '');
        $jenisDrive = trim($_POST['jenis_drive'] ?? 'Google Drive');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($nama === '' || $url === '') {
            $pesan_error = 'Nama folder dan URL folder wajib diisi.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_add_drive_link($user_id, $nama, $url, $jenisDrive, $desc);
            $pesan_sukses = 'Tautan folder drive berhasil disimpan!';
        }
    }

    // 12. Edit Link Drive (Folder)
    elseif ($action === 'edit_drive_link') {
        $id_drive = (int) ($_POST['id_drive'] ?? 0);
        $nama = trim($_POST['nama_folder'] ?? '');
        $url = trim($_POST['url_folder'] ?? '');
        $jenisDrive = trim($_POST['jenis_drive'] ?? 'Google Drive');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($id_drive <= 0 || $nama === '' || $url === '') {
            $pesan_error = 'Data folder drive tidak lengkap.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_update_drive_link($user_id, $id_drive, $nama, $url, $jenisDrive, $desc);
            $pesan_sukses = 'Tautan folder drive berhasil diperbarui!';
        }
    }

    // 13. Hapus Link Drive (Folder)
    elseif ($action === 'delete_drive_link') {
        $id_drive = (int) ($_POST['id_drive'] ?? 0);
        if ($id_drive > 0) {
            user_delete_drive_link($user_id, $id_drive);
            $pesan_sukses = 'Tautan folder drive berhasil dihapus.';
        }
    }

    // 14. Tambah Link Foto
    elseif ($action === 'add_photo_link') {
        $judul = trim($_POST['judul_foto'] ?? '');
        $url = trim($_POST['url_foto'] ?? '');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($judul === '' || $url === '') {
            $pesan_error = 'Judul foto dan URL foto/album wajib diisi.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_add_photo_link($user_id, $judul, $url, $desc);
            $pesan_sukses = 'Tautan foto/album berhasil disimpan!';
        }
    }

    // 15. Edit Link Foto
    elseif ($action === 'edit_photo_link') {
        $id_photo = (int) ($_POST['id_photo'] ?? 0);
        $judul = trim($_POST['judul_foto'] ?? '');
        $url = trim($_POST['url_foto'] ?? '');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($id_photo <= 0 || $judul === '' || $url === '') {
            $pesan_error = 'Data foto/album tidak lengkap.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_update_photo_link($user_id, $id_photo, $judul, $url, $desc);
            $pesan_sukses = 'Tautan foto/album berhasil diperbarui!';
        }
    }

    // 16. Hapus Link Foto
    elseif ($action === 'delete_photo_link') {
        $id_photo = (int) ($_POST['id_photo'] ?? 0);
        if ($id_photo > 0) {
            user_delete_photo_link($user_id, $id_photo);
            $pesan_sukses = 'Tautan foto berhasil dihapus.';
        }
    }

    // 17. Tambah Link Lainnya
    elseif ($action === 'add_other_link') {
        $nama = trim($_POST['nama_link'] ?? '');
        $url = trim($_POST['url_link'] ?? '');
        $label = trim($_POST['label'] ?? 'Referensi');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($nama === '' || $url === '') {
            $pesan_error = 'Nama tautan dan URL wajib diisi.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_add_other_link($user_id, $nama, $url, $label, $desc);
            $pesan_sukses = 'Tautan baru berhasil disimpan ke Link Lainnya!';
        }
    }

    // 18. Edit Link Lainnya
    elseif ($action === 'edit_other_link') {
        $id_other = (int) ($_POST['id_other'] ?? 0);
        $nama = trim($_POST['nama_link'] ?? '');
        $url = trim($_POST['url_link'] ?? '');
        $label = trim($_POST['label'] ?? 'Referensi');
        $desc = trim($_POST['deskripsi'] ?? '');

        if ($id_other <= 0 || $nama === '' || $url === '') {
            $pesan_error = 'Data tautan tidak lengkap.';
        } else {
            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }
            user_update_other_link($user_id, $id_other, $nama, $url, $label, $desc);
            $pesan_sukses = 'Tautan berhasil diperbarui!';
        }
    }

    // 19. Hapus Link Lainnya
    elseif ($action === 'delete_other_link') {
        $id_other = (int) ($_POST['id_other'] ?? 0);
        if ($id_other > 0) {
            user_delete_other_link($user_id, $id_other);
            $pesan_sukses = 'Tautan berhasil dihapus.';
        }
    }
}

/* =====================================================
   FETCH DATA REPOSITORI PRIVATE MILIK USER (7 KATEGORI)
===================================================== */
$favorites = user_get_all_favorites($user_id);
$links = user_get_links($user_id);
$data_links = user_get_data_links($user_id);
$mobile_apps = user_get_mobile_apps($user_id);
$drive_links = user_get_drive_links($user_id);
$photo_links = user_get_photo_links($user_id);
$other_links = user_get_other_links($user_id);

$active_page = 'private';
$current_tab = $_GET['tab'] ?? 'favorite';
if ($current_tab === 'website') {
    $current_tab = 'links';
}
$valid_tabs = ['favorite', 'links', 'data', 'mobile_app', 'drive', 'photo', 'other'];
if (!in_array($current_tab, $valid_tabs, true)) {
    $current_tab = 'favorite';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Repositori Private - Dashboard <?= ($active_role === 'ADMIN') ? 'Admin' : 'User'; ?> BPS Solok Selatan</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f8fc;
            color: #1f2937;
            overflow-x: hidden;
        }

        /* HEADER */
        .header {
            margin-bottom: 22px;
        }

        .header-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            width: 100%;
        }

        .header-left h1,
        .header-title h1 {
            font-size: 28px;
            line-height: 1.25;
            font-weight: 700;
            color: #005a9c;
            margin-bottom: 6px;
        }

        .header-left p,
        .header-title p {
            color: #6b7280;
            margin-top: 0;
            font-size: 15px;
            line-height: 1.5;
        }

        /* ALERTS */
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* OVERVIEW CATEGORY CARDS (2 KOLOM SESUAI DESAIN USER) */
        .cat-overview-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px 24px;
            margin-bottom: 28px;
        }
        .cat-overview-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px 24px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }
        .cat-overview-card:hover {
            border-color: #0066b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 102, 179, 0.08);
        }
        .cat-overview-card.active {
            border-color: #0066b3;
            background: #f8fbff;
            box-shadow: 0 2px 10px rgba(0, 102, 179, 0.12);
        }
        .cat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .cat-icon-svg {
            width: 26px;
            height: 26px;
            display: block;
        }
        .cat-count-badge {
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 12px;
            background: #f1f5f9;
            color: #64748b;
            transition: all 0.2s ease;
        }
        .cat-overview-card.active .cat-count-badge {
            background: #0066b3;
            color: white;
        }
        .cat-overview-title {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .cat-overview-card.active .cat-overview-title {
            color: #005a9c;
        }
        .cat-overview-subtitle {
            font-size: 13.5px;
            color: #6b7280;
            line-height: 1.45;
        }
        @media (max-width: 680px) {
            .cat-overview-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }
        }

        /* TABS NAVIGATION */
        .tabs-header {
            display: flex;
            gap: 8px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .tab-btn {
            flex: 1;
            min-width: fit-content;
            padding: 10px 16px;
            border-radius: 8px;
            border: none;
            background: none;
            font-size: 13.5px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .tab-btn:hover {
            color: #0066b3;
            background: #f8fafc;
        }
        .tab-btn.active {
            background: #0066b3;
            color: white;
            box-shadow: 0 2px 6px rgba(0, 102, 179, 0.25);
        }
        .tab-badge {
            background: rgba(0, 0, 0, 0.08);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11.5px;
        }
        .tab-btn.active .tab-badge {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* SECTION CONTROLS & SEARCH BAR */
        .section-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 14px;
        }
        .section-bar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .section-bar h2 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0 12px;
            min-width: 240px;
            max-width: 320px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-box:focus-within {
            border-color: #0066b3;
            box-shadow: 0 0 0 3px rgba(0, 102, 179, 0.1);
        }
        .search-box input {
            border: none;
            outline: none;
            padding: 8px 6px;
            font-size: 13px;
            font-family: inherit;
            width: 100%;
            background: transparent;
        }
        .btn-add {
            background: #0066b3;
            color: white;
            border: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(0, 102, 179, 0.2);
            transition: background 0.2s;
            text-decoration: none;
        }
        .btn-add:hover { background: #004c85; color: white; }

        /* FAVORITE GRID */
        .fav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 18px;
        }
        .fav-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .fav-card:hover {
            border-color: #0066b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 102, 179, 0.08);
        }
        .fav-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }
        .fav-logo {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: #e6f0fa;
            color: #0066b3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .fav-logo img { width: 100%; height: 100%; object-fit: cover; }
        .fav-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }
        .fav-meta {
            display: flex;
            gap: 6px;
            margin-top: 4px;
            flex-wrap: wrap;
        }
        .fav-desc {
            font-size: 13px;
            color: #64748b;
            line-height: 1.45;
            margin-bottom: 18px;
            min-height: 38px;
        }
        .fav-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
        }

        /* CARDS GRID FOR COLLECTIONS */
        .data-card-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }
        .link-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .link-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .link-card-top {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }
        .link-icon-box {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .icon-web { background: #e0f2fe; color: #0284c7; }
        .icon-sheets { background: #dcfce7; color: #16a34a; }
        .icon-docs { background: #dbeafe; color: #2563eb; }
        .icon-excel { background: #d1fae5; color: #059669; }
        .icon-word { background: #e0e7ff; color: #4338ca; }
        .icon-pdf { background: #fee2e2; color: #dc2626; }
        .icon-other { background: #f1f5f9; color: #64748b; }
        .icon-mobile { background: #f0fdf4; color: #16a34a; }
        .icon-drive { background: #eff6ff; color: #2563eb; }
        .icon-photo { background: #fff7ed; color: #ea580c; }

        .link-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }
        .link-card-url {
            font-size: 12px;
            color: #0066b3;
            text-decoration: none;
            word-break: break-all;
            margin-top: 3px;
            display: block;
        }
        .link-card-desc {
            font-size: 13px;
            color: #64748b;
            line-height: 1.4;
            margin: 10px 0 14px 0;
        }
        .link-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            gap: 8px;
        }
        .link-actions-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-sm-action {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            background: #fff;
            cursor: pointer;
            text-decoration: none;
            color: #475569;
            transition: all 0.15s;
        }
        .btn-sm-action:hover { background: #f8fafc; color: #0f172a; }
        .btn-sm-del { color: #dc2626; border-color: #fecaca; background: #fff; }
        .btn-sm-del:hover { background: #fee2e2; }
        .btn-sm-launch {
            background: #0066b3;
            color: white;
            border-color: #0066b3;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-sm-launch:hover { background: #004c85; color: white; }

        /* BADGES */
        .badge {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
        }
        .badge-category { background: #e6f0fa; color: #0066b3; }
        .badge-doc { font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 4px; }
        .b-sheets { background: #dcfce7; color: #16a34a; }
        .b-docs { background: #dbeafe; color: #2563eb; }
        .b-excel { background: #d1fae5; color: #059669; }
        .b-word { background: #e0e7ff; color: #4338ca; }
        .b-pdf { background: #fee2e2; color: #dc2626; }
        .b-other { background: #f1f5f9; color: #64748b; }

        /* PHOTO PREVIEW BOX */
        .photo-preview-box {
            width: 100%;
            height: 140px;
            border-radius: 8px;
            overflow: hidden;
            background: #f1f5f9;
            margin-bottom: 12px;
            position: relative;
            border: 1px solid #e2e8f0;
        }
        .photo-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .photo-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
        }

        /* EMPTY STATE */
        .empty-box {
            background: white;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 50px 24px;
            text-align: center;
            color: #64748b;
        }
        .empty-icon { font-size: 40px; margin-bottom: 12px; }
        .empty-title { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
        .empty-subtitle { font-size: 13.5px; max-width: 440px; margin: 0 auto 18px auto; line-height: 1.5; }
        .search-empty-box {
            display: none;
            background: white;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            color: #64748b;
            margin-top: 15px;
        }

        /* MODAL STYLES */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
        }
        .modal-overlay.show { display: flex; }
        .modal-card {
            background: white;
            border-radius: 14px;
            max-width: 560px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: modalFade 0.2s ease-out;
        }
        @keyframes modalFade {
            from { opacity: 0; transform: scale(0.97); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            padding: 18px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h3 { font-size: 17px; font-weight: 700; color: #0f172a; }
        .modal-close-btn { background: none; border: none; font-size: 22px; color: #64748b; cursor: pointer; }
        .modal-body { padding: 22px; }
        .modal-footer {
            padding: 14px 22px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            font-family: inherit;
            outline: none;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color: #0066b3;
            box-shadow: 0 0 0 3px rgba(0, 102, 179, 0.1);
        }
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
            animation: modalFade 0.2s ease-out;
        }
        .field-error-text {
            color: #dc2626;
            font-size: 12px;
            margin-top: 5px;
            display: block;
            font-weight: 500;
            animation: modalFade 0.15s ease-out;
        }

        @media (max-width: 768px) {
            .header-top { flex-direction: column; gap: 15px; }
            .tabs-header { flex-wrap: nowrap; overflow-x: auto; }
            .section-bar { flex-direction: column; align-items: stretch; }
            .section-bar-left { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>
<body>


<!-- SIDEBAR USER -->
<?php include __DIR__ . '/partials/sidebar_user.php'; ?>

<!-- MAIN -->
<main class="main" id="mainContent">

    <?php
    $page_title = 'Kelola Repositori Private';
    $page_subtitle = 'Koleksi pribadi Anda yang terstruktur dalam 7 kategori: Layanan Favorit, Website, Data/Dokumen, Aplikasi Mobile, Drive, Foto, dan Link Lainnya.';
    include __DIR__ . '/partials/topbar.php';
    ?>

    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success">
            <?= render_icon('check-circle', 'alert-icon', 18); ?>
            <span><?= htmlspecialchars($pesan_sukses); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($pesan_error): ?>
        <div class="alert alert-error">
            <?= render_icon('alert-triangle', 'alert-icon', 18); ?>
            <span><?= htmlspecialchars($pesan_error); ?></span>
        </div>
    <?php endif; ?>

    <!-- PEMBERITAHUAN MIGRASI KE PORTAL PEGAWAI (NEXT.JS) -->
    <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 22px;">💡</span>
            <div>
                <strong style="font-size: 13px; color: #1e3a8a;">Ruang Kerja Pegawai Terintegrasi:</strong>
                <p style="margin: 2px 0 0; font-size: 12px; color: #1d4ed8;">Repositori pribadi pegawai kini dapat diakses dan dikelola langsung pada Portal Digital BPS Solok Selatan (landing page) tanpa perlu membuka dashboard ini.</p>
            </div>
        </div>
        <a href="http://localhost:3000" target="_blank" rel="noopener noreferrer" style="background: #005AA9; color: white; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            <span>Buka Portal Pegawai</span> &rarr;
        </a>
    </div>

    <!-- OVERVIEW CATEGORY CARDS (2 KOLOM SESUAI DESAIN USER) -->
    <div class="cat-overview-grid">
        <!-- 1. Favorite -->
        <div class="cat-overview-card <?= ($current_tab === 'favorite') ? 'active' : ''; ?>" onclick="switchTab('favorite')">
            <div class="cat-card-header">
                <svg class="cat-icon-svg" style="color: #ef4444;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                </svg>
                <span class="cat-count-badge"><?= count($favorites); ?> item</span>
            </div>
            <div class="cat-overview-title">Favorite</div>
            <div class="cat-overview-subtitle">Layanan portal favorit</div>
        </div>

        <!-- 2. Link Website -->
        <div class="cat-overview-card <?= ($current_tab === 'links') ? 'active' : ''; ?>" onclick="switchTab('links')">
            <div class="cat-card-header">
                <svg class="cat-icon-svg" style="color: #2563eb;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                    <path d="M2 12h20"/>
                </svg>
                <span class="cat-count-badge"><?= count($links); ?> item</span>
            </div>
            <div class="cat-overview-title">Link Website</div>
            <div class="cat-overview-subtitle">Website yang disimpan</div>
        </div>

        <!-- 3. Link Data -->
        <div class="cat-overview-card <?= ($current_tab === 'data') ? 'active' : ''; ?>" onclick="switchTab('data')">
            <div class="cat-card-header">
                <svg class="cat-icon-svg" style="color: #16a34a;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2"/>
                    <path d="M3 12h18"/>
                    <path d="M12 3v18"/>
                </svg>
                <span class="cat-count-badge"><?= count($data_links); ?> item</span>
            </div>
            <div class="cat-overview-title">Link Data</div>
            <div class="cat-overview-subtitle">Spreadsheet dan dokumen</div>
        </div>

        <!-- 4. Aplikasi Mobile -->
        <div class="cat-overview-card <?= ($current_tab === 'mobile_app') ? 'active' : ''; ?>" onclick="switchTab('mobile_app')">
            <div class="cat-card-header">
                <svg class="cat-icon-svg" style="color: #8b5cf6;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="14" height="20" x="5" y="2" rx="3"/>
                    <path d="M12 18h.01"/>
                </svg>
                <span class="cat-count-badge"><?= count($mobile_apps); ?> item</span>
            </div>
            <div class="cat-overview-title">Aplikasi Mobile</div>
            <div class="cat-overview-subtitle">Referensi aplikasi Android/iOS</div>
        </div>

        <!-- 5. Link Drive (Folder) -->
        <div class="cat-overview-card <?= ($current_tab === 'drive') ? 'active' : ''; ?>" onclick="switchTab('drive')">
            <div class="cat-card-header">
                <svg class="cat-icon-svg" style="color: #d97706;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 8 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>
                </svg>
                <span class="cat-count-badge"><?= count($drive_links); ?> item</span>
            </div>
            <div class="cat-overview-title">Link Drive (Folder)</div>
            <div class="cat-overview-subtitle">Folder Google Drive dan cloud</div>
        </div>

        <!-- 6. Link Foto -->
        <div class="cat-overview-card <?= ($current_tab === 'photo') ? 'active' : ''; ?>" onclick="switchTab('photo')">
            <div class="cat-card-header">
                <svg class="cat-icon-svg" style="color: #ea580c;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2"/>
                    <circle cx="9" cy="9" r="2"/>
                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                </svg>
                <span class="cat-count-badge"><?= count($photo_links); ?> item</span>
            </div>
            <div class="cat-overview-title">Link Foto</div>
            <div class="cat-overview-subtitle">Foto, gambar, dan album</div>
        </div>

        <!-- 7. Link Lainnya -->
        <div class="cat-overview-card <?= ($current_tab === 'other') ? 'active' : ''; ?>" onclick="switchTab('other')">
            <div class="cat-card-header">
                <svg class="cat-icon-svg" style="color: #2563eb;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
                <span class="cat-count-badge"><?= count($other_links); ?> item</span>
            </div>
            <div class="cat-overview-title">Link Lainnya</div>
            <div class="cat-overview-subtitle">Tautan yang tidak masuk kategori lain</div>
        </div>
    </div>


    <!-- =====================================================
         KATEGORI 1: FAVORITE
    ===================================================== -->
    <section class="tab-content <?= ($current_tab === 'favorite') ? 'active' : ''; ?>" id="tabFavorite">
        <div class="section-bar">
            <div class="section-bar-left">
                <h2>Layanan Portal Favorit (<?= count($favorites); ?>)</h2>
                <div class="search-box">
                    <?= render_icon('search', 'search-icon', 16); ?>
                    <input type="text" placeholder="Cari layanan favorit..." oninput="filterCards(this.value)">
                </div>
            </div>
            <a href="repositori_portal.php" class="btn-add">
                <?= render_icon('plus', '', 14); ?> Tambah dari Portal
            </a>
        </div>

        <div class="search-empty-box" id="emptySearchFavorite">
            <p>Tidak ada layanan favorit yang cocok dengan pencarian.</p>
        </div>

        <?php if (empty($favorites)): ?>
            <div class="empty-box">
                <div class="empty-icon"><?= render_icon('star', '', 40); ?></div>
                <div class="empty-title">Belum Ada Layanan Favorit</div>
                <p class="empty-subtitle">
                    Anda belum menandai layanan apapun sebagai favorit. Buka Kelola Repositori Portal dan klik tombol Pin/Favorit pada layanan yang sering Anda akses.
                </p>
                <a href="repositori_portal.php" class="btn-add">
                    <span>Jelajahi Repositori Portal</span>
                    <?= render_icon('arrow-right', '', 14); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="fav-grid">
                <?php foreach ($favorites as $fav): ?>
                    <?php $layId = (int) $fav['id_layanan']; ?>
                    <div class="fav-card searchable-item" id="favItem<?= $layId; ?>" data-search-text="<?= htmlspecialchars(strtolower($fav['nama_layanan'] . ' ' . ($fav['deskripsi_layanan'] ?? '') . ' ' . ($fav['nama_kategori'] ?? ''))); ?>">
                        <div>
                            <div class="fav-header">
                                <div class="fav-logo">
                                    <?php if (!empty($fav['logo']) && file_exists(__DIR__ . '/uploads/' . $fav['logo'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($fav['logo']); ?>" alt="<?= htmlspecialchars($fav['nama_layanan']); ?>">
                                    <?php else: ?>
                                        <?= htmlspecialchars(strtoupper(substr($fav['nama_layanan'], 0, 2))); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 class="fav-title"><?= htmlspecialchars($fav['nama_layanan']); ?></h3>
                                    <div class="fav-meta">
                                        <span class="badge badge-category"><?= htmlspecialchars($fav['nama_kategori'] ?? 'Umum'); ?></span>
                                        <span class="badge" style="background: #e2e8f0; color: #475569;"><?= htmlspecialchars($fav['akses'] ?? 'Public'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <p class="fav-desc">
                                <?= htmlspecialchars($fav['deskripsi_layanan'] ?: 'Tidak ada deskripsi layanan.'); ?>
                            </p>
                        </div>
                        <div class="fav-footer">
                            <form method="POST" action="repositori_private.php?tab=favorite" onsubmit="return confirm('Hapus layanan ini dari Favorite pribadi Anda?');" style="display: inline;">
                                <input type="hidden" name="action" value="remove_favorite">
                                <input type="hidden" name="id_layanan" value="<?= $layId; ?>">
                                <button type="submit" class="btn-sm-action btn-sm-del" title="Hapus dari Favorite">
                                    <?= render_icon('trash-2', '', 13); ?> Hapus Favorit
                                </button>
                            </form>

                            <?php if (!empty($fav['url'])): ?>
                                <a href="<?= htmlspecialchars($fav['url']); ?>" target="_blank" rel="noopener noreferrer" class="btn-sm-action btn-sm-launch">
                                    <span>Buka Layanan</span> <?= render_icon('external-link', '', 12); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- =====================================================
         KATEGORI 2: KUMPULAN LINK WEBSITE
    ===================================================== -->
    <section class="tab-content <?= ($current_tab === 'links') ? 'active' : ''; ?>" id="tabLinks">
        <div class="section-bar">
            <div class="section-bar-left">
                <h2>Kumpulan Link Website Pribadi (<?= count($links); ?>)</h2>
                <div class="search-box">
                    <?= render_icon('search', 'search-icon', 16); ?>
                    <input type="text" placeholder="Cari link website..." oninput="filterCards(this.value)">
                </div>
            </div>
            <button type="button" class="btn-add" onclick="openModal('modalAddLink')">
                <?= render_icon('plus', '', 14); ?> Tambah Link Website
            </button>
        </div>

        <div class="search-empty-box" id="emptySearchLinks">
            <p>Tidak ada link website yang cocok dengan pencarian.</p>
        </div>

        <?php if (empty($links)): ?>
            <div class="empty-box">
                <div class="empty-icon"><?= render_icon('globe', '', 40); ?></div>
                <div class="empty-title">Belum Ada Link Website</div>
                <p class="empty-subtitle">
                    Simpan kumpulan tautan situs web atau sistem eksternal yang sering Anda gunakan untuk mempermudah pekerjaan sehari-hari.
                </p>
                <button type="button" class="btn-add" onclick="openModal('modalAddLink')">
                    <?= render_icon('plus', '', 14); ?> Tambah Link Website Pertama
                </button>
            </div>
        <?php else: ?>
            <div class="data-card-list">
                <?php foreach ($links as $lnk): ?>
                    <?php $lId = (int) $lnk['id_link']; ?>
                    <div class="link-card searchable-item" data-search-text="<?= htmlspecialchars(strtolower($lnk['nama_website'] . ' ' . $lnk['url_website'] . ' ' . ($lnk['deskripsi'] ?? ''))); ?>">
                        <div>
                            <div class="link-card-top">
                                <div class="link-icon-box icon-web"><?= render_icon('globe', '', 20); ?></div>
                                <div style="flex: 1; overflow: hidden;">
                                    <div class="link-card-title"><?= htmlspecialchars($lnk['nama_website']); ?></div>
                                    <a href="<?= htmlspecialchars($lnk['url_website']); ?>" target="_blank" rel="noopener noreferrer" class="link-card-url">
                                        <?= htmlspecialchars($lnk['url_website']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($lnk['deskripsi'])): ?>
                                <p class="link-card-desc"><?= htmlspecialchars($lnk['deskripsi']); ?></p>
                            <?php else: ?>
                                <div style="margin-bottom: 14px;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="link-card-footer">
                            <div class="link-actions-group">
                                <button type="button" class="btn-sm-action" onclick="openEditLinkModal(<?= $lId; ?>, '<?= htmlspecialchars(addslashes($lnk['nama_website'])); ?>', '<?= htmlspecialchars(addslashes($lnk['url_website'])); ?>', '<?= htmlspecialchars(addslashes($lnk['deskripsi'] ?? '')); ?>')">
                                    <?= render_icon('pencil', '', 12); ?> Edit
                                </button>
                                <form method="POST" action="repositori_private.php?tab=links" onsubmit="return confirm('Apakah Anda yakin ingin menghapus link website ini?');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_link">
                                    <input type="hidden" name="id_link" value="<?= $lId; ?>">
                                    <button type="submit" class="btn-sm-action btn-sm-del" title="Hapus Link">
                                        <?= render_icon('trash-2', '', 13); ?>
                                    </button>
                                </form>
                            </div>
                            <a href="<?= htmlspecialchars($lnk['url_website']); ?>" target="_blank" rel="noopener noreferrer" class="btn-sm-action btn-sm-launch">
                                <span>Buka Website</span> <?= render_icon('external-link', '', 12); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- =====================================================
         KATEGORI 3: KUMPULAN LINK DATA
    ===================================================== -->
    <section class="tab-content <?= ($current_tab === 'data') ? 'active' : ''; ?>" id="tabData">
        <div class="section-bar">
            <div class="section-bar-left">
                <h2>Kumpulan Link Data & Dokumen (<?= count($data_links); ?>)</h2>
                <div class="search-box">
                    <?= render_icon('search', 'search-icon', 16); ?>
                    <input type="text" placeholder="Cari link dokumen/data..." oninput="filterCards(this.value)">
                </div>
            </div>
            <button type="button" class="btn-add" onclick="openModal('modalAddDataLink')">
                <?= render_icon('plus', '', 14); ?> Tambah Link Data
            </button>
        </div>

        <div class="search-empty-box" id="emptySearchData">
            <p>Tidak ada link data/dokumen yang cocok dengan pencarian.</p>
        </div>

        <?php if (empty($data_links)): ?>
            <div class="empty-box">
                <div class="empty-icon"><?= render_icon('file-text', '', 40); ?></div>
                <div class="empty-title">Belum Ada Link Data & Dokumen</div>
                <p class="empty-subtitle">
                    Kumpulkan tautan Google Sheets, Google Docs, spreadsheet Excel, berkas Word, atau laporan PDF penting di sini.
                </p>
                <button type="button" class="btn-add" onclick="openModal('modalAddDataLink')">
                    <?= render_icon('plus', '', 14); ?> Tambah Link Data Pertama
                </button>
            </div>
        <?php else: ?>
            <div class="data-card-list">
                <?php foreach ($data_links as $d): ?>
                    <?php
                        $dId = (int) $d['id_data_link'];
                        $jenis = $d['jenis_dokumen'] ?? 'Lainnya';
                        $iconClass = 'icon-other';
                        $badgeClass = 'b-other';

                        if ($jenis === 'Google Sheets') { $iconClass = 'icon-sheets'; $badgeClass = 'b-sheets'; }
                        elseif ($jenis === 'Google Docs') { $iconClass = 'icon-docs'; $badgeClass = 'b-docs'; }
                        elseif ($jenis === 'Excel') { $iconClass = 'icon-excel'; $badgeClass = 'b-excel'; }
                        elseif ($jenis === 'Word') { $iconClass = 'icon-word'; $badgeClass = 'b-word'; }
                        elseif ($jenis === 'PDF') { $iconClass = 'icon-pdf'; $badgeClass = 'b-pdf'; }
                    ?>
                    <div class="link-card searchable-item" data-search-text="<?= htmlspecialchars(strtolower($d['nama_dokumen'] . ' ' . $d['url_dokumen'] . ' ' . $jenis . ' ' . ($d['deskripsi'] ?? ''))); ?>">
                        <div>
                            <div class="link-card-top">
                                <div class="link-icon-box <?= $iconClass; ?>"><?= render_icon('file-text', '', 20); ?></div>
                                <div style="flex: 1; overflow: hidden;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                        <div class="link-card-title"><?= htmlspecialchars($d['nama_dokumen']); ?></div>
                                        <span class="badge-doc <?= $badgeClass; ?>"><?= htmlspecialchars($jenis); ?></span>
                                    </div>
                                    <a href="<?= htmlspecialchars($d['url_dokumen']); ?>" target="_blank" rel="noopener noreferrer" class="link-card-url">
                                        <?= htmlspecialchars($d['url_dokumen']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($d['deskripsi'])): ?>
                                <p class="link-card-desc"><?= htmlspecialchars($d['deskripsi']); ?></p>
                            <?php else: ?>
                                <div style="margin-bottom: 14px;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="link-card-footer">
                            <div class="link-actions-group">
                                <button type="button" class="btn-sm-action" onclick="openEditDataModal(<?= $dId; ?>, '<?= htmlspecialchars(addslashes($d['nama_dokumen'])); ?>', '<?= htmlspecialchars(addslashes($d['url_dokumen'])); ?>', '<?= htmlspecialchars(addslashes($jenis)); ?>', '<?= htmlspecialchars(addslashes($d['deskripsi'] ?? '')); ?>')">
                                    <?= render_icon('pencil', '', 12); ?> Edit
                                </button>
                                <form method="POST" action="repositori_private.php?tab=data" onsubmit="return confirm('Apakah Anda yakin ingin menghapus link data ini?');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_data_link">
                                    <input type="hidden" name="id_data_link" value="<?= $dId; ?>">
                                    <button type="submit" class="btn-sm-action btn-sm-del" title="Hapus Link Data">
                                        <?= render_icon('trash-2', '', 13); ?>
                                    </button>
                                </form>
                            </div>
                            <a href="<?= htmlspecialchars($d['url_dokumen']); ?>" target="_blank" rel="noopener noreferrer" class="btn-sm-action btn-sm-launch">
                                <span>Buka Dokumen</span> <?= render_icon('external-link', '', 12); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- =====================================================
         KATEGORI 4: APLIKASI MOBILE
    ===================================================== -->
    <section class="tab-content <?= ($current_tab === 'mobile_app') ? 'active' : ''; ?>" id="tabMobile_app">
        <div class="section-bar">
            <div class="section-bar-left">
                <h2>Katalog Referensi Aplikasi Mobile (<?= count($mobile_apps); ?>)</h2>
                <div class="search-box">
                    <?= render_icon('search', 'search-icon', 16); ?>
                    <input type="text" placeholder="Cari aplikasi mobile..." oninput="filterCards(this.value)">
                </div>
            </div>
            <button type="button" class="btn-add" onclick="openModal('modalAddMobile')">
                <?= render_icon('plus', '', 14); ?> Tambah Aplikasi Mobile
            </button>
        </div>

        <div class="search-empty-box" id="emptySearchMobile">
            <p>Tidak ada aplikasi mobile yang cocok dengan pencarian.</p>
        </div>

        <?php if (empty($mobile_apps)): ?>
            <div class="empty-box">
                <div class="empty-icon"><?= render_icon('smartphone', '', 40); ?></div>
                <div class="empty-title">Belum Ada Aplikasi Mobile Tersimpan</div>
                <p class="empty-subtitle">
                    Simpan katalog tautan aplikasi mobile BPS atau aplikasi penunjang kerja (Play Store, App Store, atau APK resmi) yang Anda butuhkan.
                </p>
                <button type="button" class="btn-add" onclick="openModal('modalAddMobile')">
                    <?= render_icon('plus', '', 14); ?> Tambah Aplikasi Pertama
                </button>
            </div>
        <?php else: ?>
            <div class="data-card-list">
                <?php foreach ($mobile_apps as $app): ?>
                    <?php
                        $appId = (int) $app['id_app'];
                        $plat = $app['platform'] ?? 'Android';
                        $platBadge = '<span class="badge" style="background:#dcfce7; color:#15803d;">Android</span>';
                        if ($plat === 'iOS') {
                            $platBadge = '<span class="badge" style="background:#e0e7ff; color:#4338ca;">iOS</span>';
                        } elseif ($plat === 'Android & iOS') {
                            $platBadge = '<span class="badge" style="background:#f3e8ff; color:#7e22ce;">Android & iOS</span>';
                        }
                    ?>
                    <div class="link-card searchable-item" data-search-text="<?= htmlspecialchars(strtolower($app['nama_aplikasi'] . ' ' . $app['url_aplikasi'] . ' ' . $plat . ' ' . ($app['deskripsi'] ?? ''))); ?>">
                        <div>
                            <div class="link-card-top">
                                <div class="link-icon-box icon-mobile"><?= render_icon('smartphone', '', 20); ?></div>
                                <div style="flex: 1; overflow: hidden;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                        <div class="link-card-title"><?= htmlspecialchars($app['nama_aplikasi']); ?></div>
                                        <?= $platBadge; ?>
                                    </div>
                                    <a href="<?= htmlspecialchars($app['url_aplikasi']); ?>" target="_blank" rel="noopener noreferrer" class="link-card-url">
                                        <?= htmlspecialchars($app['url_aplikasi']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($app['deskripsi'])): ?>
                                <p class="link-card-desc"><?= htmlspecialchars($app['deskripsi']); ?></p>
                            <?php else: ?>
                                <div style="margin-bottom: 14px;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="link-card-footer">
                            <div class="link-actions-group">
                                <button type="button" class="btn-sm-action" onclick="openEditMobileModal(<?= $appId; ?>, '<?= htmlspecialchars(addslashes($app['nama_aplikasi'])); ?>', '<?= htmlspecialchars(addslashes($app['url_aplikasi'])); ?>', '<?= htmlspecialchars(addslashes($plat)); ?>', '<?= htmlspecialchars(addslashes($app['deskripsi'] ?? '')); ?>')">
                                    <?= render_icon('pencil', '', 12); ?> Edit
                                </button>
                                <form method="POST" action="repositori_private.php?tab=mobile_app" onsubmit="return confirm('Apakah Anda yakin ingin menghapus aplikasi mobile ini?');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_mobile_app">
                                    <input type="hidden" name="id_app" value="<?= $appId; ?>">
                                    <button type="submit" class="btn-sm-action btn-sm-del" title="Hapus Aplikasi">
                                        <?= render_icon('trash-2', '', 13); ?>
                                    </button>
                                </form>
                            </div>
                            <a href="<?= htmlspecialchars($app['url_aplikasi']); ?>" target="_blank" rel="noopener noreferrer" class="btn-sm-action btn-sm-launch">
                                <span>Buka / Unduh</span> <?= render_icon('external-link', '', 12); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- =====================================================
         KATEGORI 5: LINK DRIVE (FOLDER)
    ===================================================== -->
    <section class="tab-content <?= ($current_tab === 'drive') ? 'active' : ''; ?>" id="tabDrive">
        <div class="section-bar">
            <div class="section-bar-left">
                <h2>Kumpulan Link Drive & Folder Cloud (<?= count($drive_links); ?>)</h2>
                <div class="search-box">
                    <?= render_icon('search', 'search-icon', 16); ?>
                    <input type="text" placeholder="Cari folder cloud..." oninput="filterCards(this.value)">
                </div>
            </div>
            <button type="button" class="btn-add" onclick="openModal('modalAddDrive')">
                <?= render_icon('plus', '', 14); ?> Tambah Link Drive
            </button>
        </div>

        <div class="search-empty-box" id="emptySearchDrive">
            <p>Tidak ada link folder drive yang cocok dengan pencarian.</p>
        </div>

        <?php if (empty($drive_links)): ?>
            <div class="empty-box">
                <div class="empty-icon"><?= render_icon('folder', '', 40); ?></div>
                <div class="empty-title">Belum Ada Tautan Folder Cloud Tersimpan</div>
                <p class="empty-subtitle">
                    Simpan tautan ke direktori folder cloud (seperti Google Drive, OneDrive, atau Dropbox) untuk memudahkan kolaborasi berkas tim.
                </p>
                <button type="button" class="btn-add" onclick="openModal('modalAddDrive')">
                    <?= render_icon('plus', '', 14); ?> Tambah Link Drive Pertama
                </button>
            </div>
        <?php else: ?>
            <div class="data-card-list">
                <?php foreach ($drive_links as $drv): ?>
                    <?php
                        $drvId = (int) $drv['id_drive'];
                        $jDrive = $drv['jenis_drive'] ?? 'Google Drive';
                        $drvBadge = '<span class="badge" style="background:#e0f2fe; color:#0284c7;">Google Drive</span>';
                        if ($jDrive === 'OneDrive') {
                            $drvBadge = '<span class="badge" style="background:#dbeafe; color:#1d4ed8;">OneDrive</span>';
                        } elseif ($jDrive === 'Dropbox') {
                            $drvBadge = '<span class="badge" style="background:#e0e7ff; color:#3730a3;">Dropbox</span>';
                        } elseif ($jDrive === 'Lainnya') {
                            $drvBadge = '<span class="badge" style="background:#f1f5f9; color:#475569;">Cloud Storage</span>';
                        }
                    ?>
                    <div class="link-card searchable-item" data-search-text="<?= htmlspecialchars(strtolower($drv['nama_folder'] . ' ' . $drv['url_folder'] . ' ' . $jDrive . ' ' . ($drv['deskripsi'] ?? ''))); ?>">
                        <div>
                            <div class="link-card-top">
                                <div class="link-icon-box icon-drive"><?= render_icon('folder', '', 20); ?></div>
                                <div style="flex: 1; overflow: hidden;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                        <div class="link-card-title"><?= htmlspecialchars($drv['nama_folder']); ?></div>
                                        <?= $drvBadge; ?>
                                    </div>
                                    <a href="<?= htmlspecialchars($drv['url_folder']); ?>" target="_blank" rel="noopener noreferrer" class="link-card-url">
                                        <?= htmlspecialchars($drv['url_folder']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($drv['deskripsi'])): ?>
                                <p class="link-card-desc"><?= htmlspecialchars($drv['deskripsi']); ?></p>
                            <?php else: ?>
                                <div style="margin-bottom: 14px;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="link-card-footer">
                            <div class="link-actions-group">
                                <button type="button" class="btn-sm-action" onclick="openEditDriveModal(<?= $drvId; ?>, '<?= htmlspecialchars(addslashes($drv['nama_folder'])); ?>', '<?= htmlspecialchars(addslashes($drv['url_folder'])); ?>', '<?= htmlspecialchars(addslashes($jDrive)); ?>', '<?= htmlspecialchars(addslashes($drv['deskripsi'] ?? '')); ?>')">
                                    <?= render_icon('pencil', '', 12); ?> Edit
                                </button>
                                <form method="POST" action="repositori_private.php?tab=drive" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tautan folder drive ini?');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_drive_link">
                                    <input type="hidden" name="id_drive" value="<?= $drvId; ?>">
                                    <button type="submit" class="btn-sm-action btn-sm-del" title="Hapus Link Drive">
                                        <?= render_icon('trash-2', '', 13); ?>
                                    </button>
                                </form>
                            </div>
                            <a href="<?= htmlspecialchars($drv['url_folder']); ?>" target="_blank" rel="noopener noreferrer" class="btn-sm-action btn-sm-launch">
                                <span>Buka Folder</span> <?= render_icon('external-link', '', 12); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- =====================================================
         KATEGORI 6: LINK FOTO
    ===================================================== -->
    <section class="tab-content <?= ($current_tab === 'photo') ? 'active' : ''; ?>" id="tabPhoto">
        <div class="section-bar">
            <div class="section-bar-left">
                <h2>Koleksi Link Foto & Album Dokumentasi (<?= count($photo_links); ?>)</h2>
                <div class="search-box">
                    <?= render_icon('search', 'search-icon', 16); ?>
                    <input type="text" placeholder="Cari tautan foto..." oninput="filterCards(this.value)">
                </div>
            </div>
            <button type="button" class="btn-add" onclick="openModal('modalAddPhoto')">
                <?= render_icon('plus', '', 14); ?> Tambah Link Foto
            </button>
        </div>

        <div class="search-empty-box" id="emptySearchPhoto">
            <p>Tidak ada tautan foto/album yang cocok dengan pencarian.</p>
        </div>

        <?php if (empty($photo_links)): ?>
            <div class="empty-box">
                <div class="empty-icon"><?= render_icon('image', '', 40); ?></div>
                <div class="empty-title">Belum Ada Tautan Foto Tersimpan</div>
                <p class="empty-subtitle">
                    Simpan tautan ke gambar dokumentasi kegiatan, galeri foto, atau tautan album Google Photos yang Anda perlukan.
                </p>
                <button type="button" class="btn-add" onclick="openModal('modalAddPhoto')">
                    <?= render_icon('plus', '', 14); ?> Tambah Link Foto Pertama
                </button>
            </div>
        <?php else: ?>
            <div class="data-card-list">
                <?php foreach ($photo_links as $pht): ?>
                    <?php $pId = (int) $pht['id_photo']; ?>
                    <div class="link-card searchable-item" data-search-text="<?= htmlspecialchars(strtolower($pht['judul_foto'] . ' ' . $pht['url_foto'] . ' ' . ($pht['deskripsi'] ?? ''))); ?>">
                        <div>
                            <!-- PREVIEW THUMBNAIL FOTO -->
                            <div class="photo-preview-box">
                                <img src="<?= htmlspecialchars($pht['url_foto']); ?>" alt="<?= htmlspecialchars($pht['judul_foto']); ?>" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="photo-fallback" style="display: none;">
                                    <?= render_icon('image', '', 32); ?>
                                    <span style="font-size: 11px; color: #64748b; margin-top: 4px;">Tautan Foto / Album</span>
                                </div>
                            </div>

                            <div class="link-card-top">
                                <div class="link-icon-box icon-photo"><?= render_icon('image', '', 20); ?></div>
                                <div style="flex: 1; overflow: hidden;">
                                    <div class="link-card-title"><?= htmlspecialchars($pht['judul_foto']); ?></div>
                                    <a href="<?= htmlspecialchars($pht['url_foto']); ?>" target="_blank" rel="noopener noreferrer" class="link-card-url">
                                        <?= htmlspecialchars($pht['url_foto']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($pht['deskripsi'])): ?>
                                <p class="link-card-desc"><?= htmlspecialchars($pht['deskripsi']); ?></p>
                            <?php else: ?>
                                <div style="margin-bottom: 14px;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="link-card-footer">
                            <div class="link-actions-group">
                                <button type="button" class="btn-sm-action" onclick="openEditPhotoModal(<?= $pId; ?>, '<?= htmlspecialchars(addslashes($pht['judul_foto'])); ?>', '<?= htmlspecialchars(addslashes($pht['url_foto'])); ?>', '<?= htmlspecialchars(addslashes($pht['deskripsi'] ?? '')); ?>')">
                                    <?= render_icon('pencil', '', 12); ?> Edit
                                </button>
                                <form method="POST" action="repositori_private.php?tab=photo" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tautan foto ini?');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_photo_link">
                                    <input type="hidden" name="id_photo" value="<?= $pId; ?>">
                                    <button type="submit" class="btn-sm-action btn-sm-del" title="Hapus Foto">
                                        <?= render_icon('trash-2', '', 13); ?>
                                    </button>
                                </form>
                            </div>
                            <a href="<?= htmlspecialchars($pht['url_foto']); ?>" target="_blank" rel="noopener noreferrer" class="btn-sm-action btn-sm-launch">
                                <span>Buka Foto</span> <?= render_icon('external-link', '', 12); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- =====================================================
         KATEGORI 7: LINK LAINNYA
    ===================================================== -->
    <section class="tab-content <?= ($current_tab === 'other') ? 'active' : ''; ?>" id="tabOther">
        <div class="section-bar">
            <div class="section-bar-left">
                <h2>Kumpulan Link Lainnya & Referensi (<?= count($other_links); ?>)</h2>
                <div class="search-box">
                    <?= render_icon('search', 'search-icon', 16); ?>
                    <input type="text" placeholder="Cari tautan referensi..." oninput="filterCards(this.value)">
                </div>
            </div>
            <button type="button" class="btn-add" onclick="openModal('modalAddOther')">
                <?= render_icon('plus', '', 14); ?> Tambah Link Lainnya
            </button>
        </div>

        <div class="search-empty-box" id="emptySearchOther">
            <p>Tidak ada tautan yang cocok dengan pencarian.</p>
        </div>

        <?php if (empty($other_links)): ?>
            <div class="empty-box">
                <div class="empty-icon"><?= render_icon('link', '', 40); ?></div>
                <div class="empty-title">Belum Ada Tautan Lainnya</div>
                <p class="empty-subtitle">
                    Simpan tautan referensi, tools online, artikel, regulasi, atau sumber daya digital bebas yang tidak masuk ke kategori lain.
                </p>
                <button type="button" class="btn-add" onclick="openModal('modalAddOther')">
                    <?= render_icon('plus', '', 14); ?> Tambah Link Pertama
                </button>
            </div>
        <?php else: ?>
            <div class="data-card-list">
                <?php foreach ($other_links as $oth): ?>
                    <?php $oId = (int) $oth['id_other']; ?>
                    <div class="link-card searchable-item" data-search-text="<?= htmlspecialchars(strtolower($oth['nama_link'] . ' ' . $oth['url_link'] . ' ' . ($oth['label'] ?? '') . ' ' . ($oth['deskripsi'] ?? ''))); ?>">
                        <div>
                            <div class="link-card-top">
                                <div class="link-icon-box icon-other"><?= render_icon('link', '', 20); ?></div>
                                <div style="flex: 1; overflow: hidden;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                        <div class="link-card-title"><?= htmlspecialchars($oth['nama_link']); ?></div>
                                        <span class="badge" style="background: #e2e8f0; color: #334155;">
                                            <?= htmlspecialchars($oth['label'] ?: 'Referensi'); ?>
                                        </span>
                                    </div>
                                    <a href="<?= htmlspecialchars($oth['url_link']); ?>" target="_blank" rel="noopener noreferrer" class="link-card-url">
                                        <?= htmlspecialchars($oth['url_link']); ?>
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($oth['deskripsi'])): ?>
                                <p class="link-card-desc"><?= htmlspecialchars($oth['deskripsi']); ?></p>
                            <?php else: ?>
                                <div style="margin-bottom: 14px;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="link-card-footer">
                            <div class="link-actions-group">
                                <button type="button" class="btn-sm-action" onclick="openEditOtherModal(<?= $oId; ?>, '<?= htmlspecialchars(addslashes($oth['nama_link'])); ?>', '<?= htmlspecialchars(addslashes($oth['url_link'])); ?>', '<?= htmlspecialchars(addslashes($oth['label'] ?? 'Referensi')); ?>', '<?= htmlspecialchars(addslashes($oth['deskripsi'] ?? '')); ?>')">
                                    <?= render_icon('pencil', '', 12); ?> Edit
                                </button>
                                <form method="POST" action="repositori_private.php?tab=other" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tautan ini?');" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_other_link">
                                    <input type="hidden" name="id_other" value="<?= $oId; ?>">
                                    <button type="submit" class="btn-sm-action btn-sm-del" title="Hapus Tautan">
                                        <?= render_icon('trash-2', '', 13); ?>
                                    </button>
                                </form>
                            </div>
                            <a href="<?= htmlspecialchars($oth['url_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn-sm-action btn-sm-launch">
                                <span>Buka Tautan</span> <?= render_icon('external-link', '', 12); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</main>

<!-- =====================================================
     MODALS FOR ALL CATEGORIES
===================================================== -->

<!-- 1. Modal Tambah Link Website -->
<div class="modal-overlay" id="modalAddLink" onclick="closeModalOnBackdrop(event, 'modalAddLink')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Tambah Link Website Pribadi</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalAddLink')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=links">
            <input type="hidden" name="action" value="add_link">
            <div class="modal-body">
                <div class="form-group">
                    <label for="w_nama">Nama Website *</label>
                    <input type="text" id="w_nama" name="nama_website" required placeholder="Contoh: BPS RI Pusat / Sirusa">
                </div>
                <div class="form-group">
                    <label for="w_url">URL Website *</label>
                    <input type="url" id="w_url" name="url_website" required placeholder="https://www.bps.go.id">
                </div>
                <div class="form-group">
                    <label for="w_desc">Deskripsi (Opsional)</label>
                    <textarea id="w_desc" name="deskripsi" rows="2" placeholder="Catatan singkat tentang tautan ini..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalAddLink')">Batal</button>
                <button type="submit" class="btn-add">Simpan Website</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal Edit Link Website -->
<div class="modal-overlay" id="modalEditLink" onclick="closeModalOnBackdrop(event, 'modalEditLink')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Link Website Pribadi</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalEditLink')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=links">
            <input type="hidden" name="action" value="edit_link">
            <input type="hidden" name="id_link" id="edit_link_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_w_nama">Nama Website *</label>
                    <input type="text" id="edit_w_nama" name="nama_website" required>
                </div>
                <div class="form-group">
                    <label for="edit_w_url">URL Website *</label>
                    <input type="url" id="edit_w_url" name="url_website" required>
                </div>
                <div class="form-group">
                    <label for="edit_w_desc">Deskripsi (Opsional)</label>
                    <textarea id="edit_w_desc" name="deskripsi" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalEditLink')">Batal</button>
                <button type="submit" class="btn-add">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal Tambah Link Data -->
<div class="modal-overlay" id="modalAddDataLink" onclick="closeModalOnBackdrop(event, 'modalAddDataLink')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Tambah Link Data / Dokumen</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalAddDataLink')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=data">
            <input type="hidden" name="action" value="add_data_link">
            <div class="modal-body">
                <div class="form-group">
                    <label for="d_nama">Nama Dokumen / Data *</label>
                    <input type="text" id="d_nama" name="nama_dokumen" required placeholder="Contoh: Rekap Data Produksi Padi 2026">
                </div>
                <div class="form-group">
                    <label for="d_jenis">Jenis Dokumen *</label>
                    <select id="d_jenis" name="jenis_dokumen" required>
                        <option value="Google Sheets">📊 Google Sheets (Spreadsheet Online)</option>
                        <option value="Google Docs">📝 Google Docs (Dokumen Online)</option>
                        <option value="Excel">📈 Microsoft Excel (.xlsx)</option>
                        <option value="Word">📄 Microsoft Word (.docx)</option>
                        <option value="PDF">📕 Dokumen PDF (.pdf)</option>
                        <option value="Lainnya">📁 Sumber Data / Berkas Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="d_url">URL / Tautan Dokumen *</label>
                    <input type="url" id="d_url" name="url_dokumen" required placeholder="https://docs.google.com/spreadsheets/d/...">
                </div>
                <div class="form-group">
                    <label for="d_desc">Deskripsi (Opsional)</label>
                    <textarea id="d_desc" name="deskripsi" rows="2" placeholder="Keterangan singkat isi data atau dokumen..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalAddDataLink')">Batal</button>
                <button type="submit" class="btn-add">Simpan Dokumen</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal Edit Link Data -->
<div class="modal-overlay" id="modalEditDataLink" onclick="closeModalOnBackdrop(event, 'modalEditDataLink')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Link Data / Dokumen</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalEditDataLink')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=data">
            <input type="hidden" name="action" value="edit_data_link">
            <input type="hidden" name="id_data_link" id="edit_data_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_d_nama">Nama Dokumen / Data *</label>
                    <input type="text" id="edit_d_nama" name="nama_dokumen" required>
                </div>
                <div class="form-group">
                    <label for="edit_d_jenis">Jenis Dokumen *</label>
                    <select id="edit_d_jenis" name="jenis_dokumen" required>
                        <option value="Google Sheets">📊 Google Sheets (Spreadsheet Online)</option>
                        <option value="Google Docs">📝 Google Docs (Dokumen Online)</option>
                        <option value="Excel">📈 Microsoft Excel (.xlsx)</option>
                        <option value="Word">📄 Microsoft Word (.docx)</option>
                        <option value="PDF">📕 Dokumen PDF (.pdf)</option>
                        <option value="Lainnya">📁 Sumber Data / Berkas Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_d_url">URL / Tautan Dokumen *</label>
                    <input type="url" id="edit_d_url" name="url_dokumen" required>
                </div>
                <div class="form-group">
                    <label for="edit_d_desc">Deskripsi (Opsional)</label>
                    <textarea id="edit_d_desc" name="deskripsi" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalEditDataLink')">Batal</button>
                <button type="submit" class="btn-add">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- 5. Modal Tambah Aplikasi Mobile -->
<div class="modal-overlay" id="modalAddMobile" onclick="closeModalOnBackdrop(event, 'modalAddMobile')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Tambah Referensi Aplikasi Mobile</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalAddMobile')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=mobile_app">
            <input type="hidden" name="action" value="add_mobile_app">
            <div class="modal-body">
                <div class="form-group">
                    <label for="m_nama">Nama Aplikasi *</label>
                    <input type="text" id="m_nama" name="nama_aplikasi" required placeholder="Contoh: FASIH BPS / Wilkerstat">
                </div>
                <div class="form-group">
                    <label for="m_platform">Platform *</label>
                    <select id="m_platform" name="platform" required>
                        <option value="Android">🤖 Android (Google Play Store / APK)</option>
                        <option value="iOS">🍎 iOS (Apple App Store)</option>
                        <option value="Android & iOS">📱 Multiplatform (Android & iOS)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="m_url">URL Aplikasi / Download *</label>
                    <input type="url" id="m_url" name="url_aplikasi" required placeholder="https://play.google.com/store/apps/details?id=...">
                </div>
                <div class="form-group">
                    <label for="m_desc">Deskripsi (Opsional)</label>
                    <textarea id="m_desc" name="deskripsi" rows="2" placeholder="Kegunaan aplikasi, instruksi login, dsb..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalAddMobile')">Batal</button>
                <button type="submit" class="btn-add">Simpan Aplikasi</button>
            </div>
        </form>
    </div>
</div>

<!-- 6. Modal Edit Aplikasi Mobile -->
<div class="modal-overlay" id="modalEditMobile" onclick="closeModalOnBackdrop(event, 'modalEditMobile')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Referensi Aplikasi Mobile</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalEditMobile')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=mobile_app">
            <input type="hidden" name="action" value="edit_mobile_app">
            <input type="hidden" name="id_app" id="edit_app_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_m_nama">Nama Aplikasi *</label>
                    <input type="text" id="edit_m_nama" name="nama_aplikasi" required>
                </div>
                <div class="form-group">
                    <label for="edit_m_platform">Platform *</label>
                    <select id="edit_m_platform" name="platform" required>
                        <option value="Android">🤖 Android (Google Play Store / APK)</option>
                        <option value="iOS">🍎 iOS (Apple App Store)</option>
                        <option value="Android & iOS">📱 Multiplatform (Android & iOS)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_m_url">URL Aplikasi / Download *</label>
                    <input type="url" id="edit_m_url" name="url_aplikasi" required>
                </div>
                <div class="form-group">
                    <label for="edit_m_desc">Deskripsi (Opsional)</label>
                    <textarea id="edit_m_desc" name="deskripsi" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalEditMobile')">Batal</button>
                <button type="submit" class="btn-add">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- 7. Modal Tambah Link Drive -->
<div class="modal-overlay" id="modalAddDrive" onclick="closeModalOnBackdrop(event, 'modalAddDrive')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Tambah Link Drive (Folder)</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalAddDrive')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=drive">
            <input type="hidden" name="action" value="add_drive_link">
            <div class="modal-body">
                <div class="form-group">
                    <label for="drv_nama">Nama Folder *</label>
                    <input type="text" id="drv_nama" name="nama_folder" required placeholder="Contoh: Dokumentasi Sensus Pertanian 2026">
                </div>
                <div class="form-group">
                    <label for="drv_jenis">Layanan Penyimpanan Cloud *</label>
                    <select id="drv_jenis" name="jenis_drive" required>
                        <option value="Google Drive">📁 Google Drive</option>
                        <option value="OneDrive">☁️ Microsoft OneDrive</option>
                        <option value="Dropbox">📦 Dropbox</option>
                        <option value="Lainnya">🗂️ Layanan Cloud Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="drv_url">URL Folder *</label>
                    <input type="url" id="drv_url" name="url_folder" required placeholder="https://drive.google.com/drive/folders/...">
                </div>
                <div class="form-group">
                    <label for="drv_desc">Deskripsi (Opsional)</label>
                    <textarea id="drv_desc" name="deskripsi" rows="2" placeholder="Keterangan isi folder, akses pembagian..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalAddDrive')">Batal</button>
                <button type="submit" class="btn-add">Simpan Folder</button>
            </div>
        </form>
    </div>
</div>

<!-- 8. Modal Edit Link Drive -->
<div class="modal-overlay" id="modalEditDrive" onclick="closeModalOnBackdrop(event, 'modalEditDrive')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Link Drive (Folder)</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalEditDrive')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=drive">
            <input type="hidden" name="action" value="edit_drive_link">
            <input type="hidden" name="id_drive" id="edit_drive_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_drv_nama">Nama Folder *</label>
                    <input type="text" id="edit_drv_nama" name="nama_folder" required>
                </div>
                <div class="form-group">
                    <label for="edit_drv_jenis">Layanan Penyimpanan Cloud *</label>
                    <select id="edit_drv_jenis" name="jenis_drive" required>
                        <option value="Google Drive">📁 Google Drive</option>
                        <option value="OneDrive">☁️ Microsoft OneDrive</option>
                        <option value="Dropbox">📦 Dropbox</option>
                        <option value="Lainnya">🗂️ Layanan Cloud Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_drv_url">URL Folder *</label>
                    <input type="url" id="edit_drv_url" name="url_folder" required>
                </div>
                <div class="form-group">
                    <label for="edit_drv_desc">Deskripsi (Opsional)</label>
                    <textarea id="edit_drv_desc" name="deskripsi" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalEditDrive')">Batal</button>
                <button type="submit" class="btn-add">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- 9. Modal Tambah Link Foto -->
<div class="modal-overlay" id="modalAddPhoto" onclick="closeModalOnBackdrop(event, 'modalAddPhoto')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Tambah Link Foto / Album</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalAddPhoto')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=photo">
            <input type="hidden" name="action" value="add_photo_link">
            <div class="modal-body">
                <div class="form-group">
                    <label for="pht_judul">Nama / Judul Foto *</label>
                    <input type="text" id="pht_judul" name="judul_foto" required placeholder="Contoh: Foto Kegiatan Apel Pagi Hari Statistik">
                </div>
                <div class="form-group">
                    <label for="pht_url">URL Foto / Album *</label>
                    <input type="url" id="pht_url" name="url_foto" required placeholder="https://photos.google.com/... atau URL file gambar">
                </div>
                <div class="form-group">
                    <label for="pht_desc">Deskripsi (Opsional)</label>
                    <textarea id="pht_desc" name="deskripsi" rows="2" placeholder="Keterangan singkat momen foto atau album..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalAddPhoto')">Batal</button>
                <button type="submit" class="btn-add">Simpan Foto</button>
            </div>
        </form>
    </div>
</div>

<!-- 10. Modal Edit Link Foto -->
<div class="modal-overlay" id="modalEditPhoto" onclick="closeModalOnBackdrop(event, 'modalEditPhoto')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Link Foto / Album</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalEditPhoto')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=photo">
            <input type="hidden" name="action" value="edit_photo_link">
            <input type="hidden" name="id_photo" id="edit_photo_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_pht_judul">Nama / Judul Foto *</label>
                    <input type="text" id="edit_pht_judul" name="judul_foto" required>
                </div>
                <div class="form-group">
                    <label for="edit_pht_url">URL Foto / Album *</label>
                    <input type="url" id="edit_pht_url" name="url_foto" required>
                </div>
                <div class="form-group">
                    <label for="edit_pht_desc">Deskripsi (Opsional)</label>
                    <textarea id="edit_pht_desc" name="deskripsi" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalEditPhoto')">Batal</button>
                <button type="submit" class="btn-add">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- 11. Modal Tambah Link Lainnya -->
<div class="modal-overlay" id="modalAddOther" onclick="closeModalOnBackdrop(event, 'modalAddOther')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Tambah Link Lainnya & Referensi</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalAddOther')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=other">
            <input type="hidden" name="action" value="add_other_link">
            <div class="modal-body">
                <div class="form-group">
                    <label for="oth_nama">Nama Tautan / Sumber Daya *</label>
                    <input type="text" id="oth_nama" name="nama_link" required placeholder="Contoh: Portal Regulasi Satu Data Indonesia">
                </div>
                <div class="form-group">
                    <label for="oth_label">Kategori / Label *</label>
                    <select id="oth_label" name="label" required>
                        <option value="Referensi">📚 Referensi</option>
                        <option value="Tools Online">🛠️ Tools Online</option>
                        <option value="Artikel">📰 Artikel</option>
                        <option value="Dokumentasi">📖 Dokumentasi</option>
                        <option value="Regulasi">⚖️ Regulasi</option>
                        <option value="Tutorial">💡 Tutorial</option>
                        <option value="Lainnya">🔗 Sumber Daya Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="oth_url">URL Tautan *</label>
                    <input type="url" id="oth_url" name="url_link" required placeholder="https://...">
                </div>
                <div class="form-group">
                    <label for="oth_desc">Deskripsi (Opsional)</label>
                    <textarea id="oth_desc" name="deskripsi" rows="2" placeholder="Keterangan singkat tentang link ini..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalAddOther')">Batal</button>
                <button type="submit" class="btn-add">Simpan Tautan</button>
            </div>
        </form>
    </div>
</div>

<!-- 12. Modal Edit Link Lainnya -->
<div class="modal-overlay" id="modalEditOther" onclick="closeModalOnBackdrop(event, 'modalEditOther')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Link Lainnya & Referensi</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalEditOther')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="repositori_private.php?tab=other">
            <input type="hidden" name="action" value="edit_other_link">
            <input type="hidden" name="id_other" id="edit_other_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_oth_nama">Nama Tautan / Sumber Daya *</label>
                    <input type="text" id="edit_oth_nama" name="nama_link" required>
                </div>
                <div class="form-group">
                    <label for="edit_oth_label">Kategori / Label *</label>
                    <select id="edit_oth_label" name="label" required>
                        <option value="Referensi">📚 Referensi</option>
                        <option value="Tools Online">🛠️ Tools Online</option>
                        <option value="Artikel">📰 Artikel</option>
                        <option value="Dokumentasi">📖 Dokumentasi</option>
                        <option value="Regulasi">⚖️ Regulasi</option>
                        <option value="Tutorial">💡 Tutorial</option>
                        <option value="Lainnya">🔗 Sumber Daya Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_oth_url">URL Tautan *</label>
                    <input type="url" id="edit_oth_url" name="url_link" required>
                </div>
                <div class="form-group">
                    <label for="edit_oth_desc">Deskripsi (Opsional)</label>
                    <textarea id="edit_oth_desc" name="deskripsi" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-action" onclick="closeModal('modalEditOther')">Batal</button>
                <button type="submit" class="btn-add">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
const tabMap = {
    'favorite': 'Favorite',
    'links': 'Links',
    'website': 'Links',
    'data': 'Data',
    'mobile_app': 'Mobile_app',
    'drive': 'Drive',
    'photo': 'Photo',
    'other': 'Other'
};

function switchTab(tabName) {
    if (tabName === 'website') tabName = 'links';

    const allTabs = ['favorite', 'links', 'data', 'mobile_app', 'drive', 'photo', 'other'];

    allTabs.forEach(t => {
        // Tab buttons
        const btn = document.querySelector(`.tab-btn[onclick*="${t}"]`);
        if (btn) btn.classList.remove('active');

        // Overview cards
        const card = document.querySelector(`.cat-overview-card[onclick*="${t}"]`);
        if (card) card.classList.remove('active');

        // Tab content sections
        const content = document.getElementById(`tab${tabMap[t]}`);
        if (content) content.classList.remove('active');
    });

    const activeBtn = document.querySelector(`.tab-btn[onclick*="${tabName}"]`);
    const activeCard = document.querySelector(`.cat-overview-card[onclick*="${tabName}"]`);
    const activeContent = document.getElementById(`tab${tabMap[tabName]}`);

    if (activeBtn) activeBtn.classList.add('active');
    if (activeCard) activeCard.classList.add('active');
    if (activeContent) activeContent.classList.add('active');

    // Update query param without full reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);

    // Reset filter
    const activeSearch = activeContent ? activeContent.querySelector('.search-box input') : null;
    if (activeSearch) {
        activeSearch.value = '';
        filterCards('');
    }
}

function filterCards(keyword) {
    keyword = (keyword || '').toLowerCase().trim();
    const activeSection = document.querySelector('.tab-content.active');
    if (!activeSection) return;

    const items = activeSection.querySelectorAll('.searchable-item');
    let visibleCount = 0;

    items.forEach(item => {
        const text = (item.getAttribute('data-search-text') || '').toLowerCase();
        if (!keyword || text.includes(keyword)) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const emptySearch = activeSection.querySelector('.search-empty-box');
    if (emptySearch) {
        if (keyword && visibleCount === 0 && items.length > 0) {
            emptySearch.style.display = 'block';
        } else {
            emptySearch.style.display = 'none';
        }
    }
}

function clearModalErrors(modal) {
    if (!modal) return;
    const alertBox = modal.querySelector('.modal-form-alert');
    if (alertBox) {
        alertBox.style.display = 'none';
        alertBox.innerHTML = '';
    }
    modal.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    modal.querySelectorAll('.field-error-text').forEach(el => el.remove());
}

function openModal(id) {
    const m = document.getElementById(id);
    if (m) {
        clearModalErrors(m);
        m.classList.add('show');
    }
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (m) {
        clearModalErrors(m);
        m.classList.remove('show');
    }
}

function closeModalOnBackdrop(e, id) {
    if (e.target.id === id) closeModal(id);
}

document.addEventListener('DOMContentLoaded', function() {
    const modalForms = document.querySelectorAll('.modal-overlay form');
    modalForms.forEach(form => {
        // Matikan default validation tooltip browser agar tampilan warning seragam & konsisten
        form.setAttribute('novalidate', 'true');

        form.addEventListener('submit', function(e) {
            const modalBody = form.querySelector('.modal-body');
            let alertBox = form.querySelector('.modal-form-alert');
            if (!alertBox && modalBody) {
                alertBox = document.createElement('div');
                alertBox.className = 'modal-form-alert';
                modalBody.insertBefore(alertBox, modalBody.firstChild);
            }

            // Bersihkan pesan error sebelumnya
            form.querySelectorAll('.field-error-text').forEach(el => el.remove());
            form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

            const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
            let hasError = false;
            let firstInvalid = null;
            const missingLabels = [];

            inputs.forEach(input => {
                const formGroup = input.closest('.form-group');
                const label = formGroup ? formGroup.querySelector('label') : null;
                const labelText = label ? label.textContent : '';

                // Kolom dianggap opsional HANYA jika mengandung tulisan 'opsional' / 'optional'
                const isOptional = /opsional|optional/i.test(labelText) || input.dataset.optional === 'true';

                if (!isOptional) {
                    const val = (input.value || '').trim();
                    if (val === '') {
                        hasError = true;
                        input.classList.add('input-error');

                        const cleanLabel = labelText.replace(/[\*\(\)]/g, '').trim() || 'Kolom ini';
                        missingLabels.push(cleanLabel);

                        const errSpan = document.createElement('span');
                        errSpan.className = 'field-error-text';
                        errSpan.textContent = `${cleanLabel} wajib diisi.`;
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
                    alertBox.innerHTML = `<span><strong>Perhatian:</strong> Harap lengkapi seluruh isian wajib sebelum menyimpan data.</span>`;
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

        // Hapus penanda error saat pengguna mulai mengetik atau memilih data
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
                        const alertBox = form.querySelector('.modal-form-alert');
                        if (alertBox) alertBox.style.display = 'none';
                    }
                }
            };
            input.addEventListener('input', clearError);
            input.addEventListener('change', clearError);
        });
    });
});

function openEditLinkModal(id, nama, url, desc) {
    document.getElementById('edit_link_id').value = id;
    document.getElementById('edit_w_nama').value = nama;
    document.getElementById('edit_w_url').value = url;
    document.getElementById('edit_w_desc').value = desc;
    openModal('modalEditLink');
}

function openEditDataModal(id, nama, url, jenis, desc) {
    document.getElementById('edit_data_id').value = id;
    document.getElementById('edit_d_nama').value = nama;
    document.getElementById('edit_d_url').value = url;
    document.getElementById('edit_d_jenis').value = jenis;
    document.getElementById('edit_d_desc').value = desc;
    openModal('modalEditDataLink');
}

function openEditMobileModal(id, nama, url, platform, desc) {
    document.getElementById('edit_app_id').value = id;
    document.getElementById('edit_m_nama').value = nama;
    document.getElementById('edit_m_url').value = url;
    document.getElementById('edit_m_platform').value = platform;
    document.getElementById('edit_m_desc').value = desc;
    openModal('modalEditMobile');
}

function openEditDriveModal(id, nama, url, jenis, desc) {
    document.getElementById('edit_drive_id').value = id;
    document.getElementById('edit_drv_nama').value = nama;
    document.getElementById('edit_drv_url').value = url;
    document.getElementById('edit_drv_jenis').value = jenis;
    document.getElementById('edit_drv_desc').value = desc;
    openModal('modalEditDrive');
}

function openEditPhotoModal(id, judul, url, desc) {
    document.getElementById('edit_photo_id').value = id;
    document.getElementById('edit_pht_judul').value = judul;
    document.getElementById('edit_pht_url').value = url;
    document.getElementById('edit_pht_desc').value = desc;
    openModal('modalEditPhoto');
}

function openEditOtherModal(id, nama, url, label, desc) {
    document.getElementById('edit_other_id').value = id;
    document.getElementById('edit_oth_nama').value = nama;
    document.getElementById('edit_oth_url').value = url;
    document.getElementById('edit_oth_label').value = label;
    document.getElementById('edit_oth_desc').value = desc;
    openModal('modalEditOther');
}
</script>

</body>
</html>
