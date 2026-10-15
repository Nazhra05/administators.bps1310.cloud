<?php 
 
require_once '../config/config.php'; 
require_once '../config/auth.php'; 
require_once __DIR__ . '/partials/icons.php'; 
 
$is_real_admin = is_admin_user();
$active_role = auth_active_role();
$admin_role = $active_role;
$user_id = (int) ($_SESSION['admin_id'] ?? 0);
 
$pesan_sukses = '';
$pesan_error = '';

/* ===================================================== 
   PROSES TAMBAH / EDIT LAYANAN VIA POST
===================================================== */ 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah_layanan') {
        $nama_layanan = trim($_POST['nama_layanan'] ?? '');
        $deskripsi_layanan = trim($_POST['deskripsi_layanan'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $id_kategori = (int) ($_POST['id_kategori'] ?? 0);
        $akses = trim($_POST['akses'] ?? 'Public');
        $vpn = trim($_POST['vpn'] ?? 'No');

        if ($nama_layanan === '' || $deskripsi_layanan === '' || $url === '' || $id_kategori <= 0) {
            $pesan_error = 'Nama layanan, deskripsi, URL, dan kategori wajib diisi.';
        } else {
            $logo = 'logo_bps.jpg';
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $max_size = 2 * 1024 * 1024; // 2MB
                if ($_FILES['logo']['size'] > $max_size) {
                    $pesan_error = 'Ukuran berkas logo maksimal 2 MB.';
                } else {
                    $uploadDir = __DIR__ . '/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                        $logoName = 'layanan_' . time() . '_' . random_int(100, 999) . '.' . $ext;
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logoName)) {
                            $logo = $logoName;
                        }
                    } else {
                        $pesan_error = 'Format berkas logo harus berupa JPG, JPEG, PNG, atau WebP.';
                    }
                }
            } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_INI_SIZE) {
                $pesan_error = 'Ukuran berkas logo melebihi batas upload server (maksimal 2 MB).';
            }

            if ($pesan_error === '' && $active_role === 'USER') {
                $countStmt = $conn->prepare("SELECT COUNT(*) FROM layanan WHERE created_by = ?");
                $countStmt->execute([$user_id]);
                if ((int) $countStmt->fetchColumn() >= 3) {
                    $pesan_error = 'Batas maksimal penambahan layanan portal telah tercapai (maksimal 3 layanan). Silakan hapus salah satu layanan untuk menambahkan yang baru.';
                }
            }

            if ($pesan_error === '') {
                if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                    $url = 'https://' . $url;
                }

                try {
                    $stmt = $conn->prepare("
                        INSERT INTO layanan (id_kategori, created_by, akses, vpn, nama_layanan, deskripsi_layanan, url, logo)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $id_kategori,
                        $user_id,
                        $akses,
                        $vpn,
                        $nama_layanan,
                        $deskripsi_layanan,
                        $url,
                        $logo
                    ]);
                    $pesan_sukses = 'Layanan baru berhasil ditambahkan!';
                } catch (PDOException $e) {
                    $pesan_error = 'Gagal menyimpan layanan: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'edit_layanan') {
        $id_layanan = (int) ($_POST['id_layanan'] ?? 0);
        $nama_layanan = trim($_POST['nama_layanan'] ?? '');
        $deskripsi_layanan = trim($_POST['deskripsi_layanan'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $id_kategori = (int) ($_POST['id_kategori'] ?? 0);
        $akses = trim($_POST['akses'] ?? 'Public');
        $vpn = trim($_POST['vpn'] ?? 'No');

        if ($id_layanan <= 0 || $nama_layanan === '' || $deskripsi_layanan === '' || $url === '' || $id_kategori <= 0) {
            $pesan_error = 'Nama layanan, deskripsi, URL, dan kategori wajib diisi.';
        } else {
            $chk = $conn->prepare("SELECT id_layanan, logo, created_by FROM layanan WHERE id_layanan = ?");
            $chk->execute([$id_layanan]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);

            if ($row && ($is_real_admin || (int) $row['created_by'] === $user_id)) {
                $logo = $row['logo'];
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $max_size = 2 * 1024 * 1024; // 2MB
                    if ($_FILES['logo']['size'] > $max_size) {
                        $pesan_error = 'Ukuran berkas logo maksimal 2 MB.';
                    } else {
                        $uploadDir = __DIR__ . '/uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                            $logoName = 'layanan_' . time() . '_' . random_int(100, 999) . '.' . $ext;
                            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logoName)) {
                                if (!empty($logo) && $logo !== 'logo_bps.jpg' && file_exists($uploadDir . $logo)) {
                                    @unlink($uploadDir . $logo);
                                }
                                $logo = $logoName;
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
                        $stmt = $conn->prepare("
                            UPDATE layanan 
                            SET id_kategori = ?, akses = ?, vpn = ?, nama_layanan = ?, deskripsi_layanan = ?, url = ?, logo = ?
                            WHERE id_layanan = ?
                        ");
                        $stmt->execute([
                            $id_kategori,
                            $akses,
                            $vpn,
                            $nama_layanan,
                            $deskripsi_layanan,
                            $url,
                            $logo,
                            $id_layanan
                        ]);
                        $pesan_sukses = 'Layanan berhasil diperbarui!';
                    } catch (PDOException $e) {
                        $pesan_error = 'Gagal memperbarui layanan: ' . $e->getMessage();
                    }
                }
            } else {
                $pesan_error = 'Anda tidak memiliki hak untuk mengedit layanan ini.';
            }
        }
    } elseif ($action === 'hapus_layanan' || $action === 'delete_layanan') {
        $id_layanan = (int) ($_POST['id_layanan'] ?? $_POST['id'] ?? 0);
        if ($id_layanan > 0) {
            $chk = $conn->prepare("SELECT id_layanan, logo, created_by FROM layanan WHERE id_layanan = ?");
            $chk->execute([$id_layanan]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);

            if ($row && ((int) $row['created_by'] === $user_id || $is_real_admin)) {
                $del = $conn->prepare("DELETE FROM layanan WHERE id_layanan = ?");
                $del->execute([$id_layanan]);
                if (!empty($row['logo']) && $row['logo'] !== 'logo_bps.jpg') {
                    @unlink(__DIR__ . '/uploads/' . basename($row['logo']));
                }
                $pesan_sukses = 'Layanan Anda berhasil dihapus dari portal.';
            } else {
                $pesan_error = 'Anda tidak memiliki hak untuk menghapus layanan ini.';
            }
        }
    } elseif ($action === 'toggle_favorite') {
        $id_layanan = (int) ($_POST['id_layanan'] ?? 0);
        if ($id_layanan > 0) {
            require_once __DIR__ . '/../config/user_repo.php';
            $isFav = user_toggle_favorite($user_id, $id_layanan);
            $pesan_sukses = $isFav ? 'Layanan berhasil ditambahkan ke Favorite!' : 'Layanan telah dihapus dari Favorite.';
        }
    }
}

/* ===================================================== 
   CEK FILTER KATEGORI 
===================================================== */ 
 
$selected_kategori = null; 
 
if ( 
    isset($_GET['id_kategori']) && 
    is_numeric($_GET['id_kategori']) && 
    (int) $_GET['id_kategori'] > 0 
) { 
 
    $selected_kategori = (int) $_GET['id_kategori']; 
 
} 
 
 
/* ===================================================== 
   AMBIL DATA LAYANAN + KATEGORI 
===================================================== */ 
 
$user_id = (int) ($_SESSION['admin_id'] ?? 0);

if ($active_role === 'USER') {
    // Role USER: Hanya melihat layanan miliknya sendiri
    $stmt = $conn->prepare(" 
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
        WHERE l.created_by = ?
        ORDER BY l.id_kategori ASC, l.id_layanan ASC 
    "); 
    $stmt->execute([$user_id]);
    $data_layanan = $stmt->fetchAll(PDO::FETCH_ASSOC); 
} else {
    // Role ADMIN: Melihat seluruh layanan
    $stmt = $conn->query(" 
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
        ORDER BY l.id_kategori ASC, l.id_layanan ASC 
    "); 
    $data_layanan = $stmt->fetchAll(PDO::FETCH_ASSOC); 
}
 
 
/* ===================================================== 
   AMBIL SEMUA KATEGORI 
===================================================== */ 
 
$stmt_kategori = $conn->query(" 
    SELECT 
        id_kategori, 
        nama_kategori 
    FROM kategori 
    ORDER BY id_kategori ASC 
"); 
 
$data_kategori = $stmt_kategori->fetchAll(PDO::FETCH_ASSOC); 
 
 
/* ===================================================== 
   GROUP LAYANAN BERDASARKAN KATEGORI 
===================================================== */ 
 
$layanan_per_kategori = []; 
 
foreach ($data_kategori as $kategori) { 
 
    $layanan_per_kategori[$kategori['id_kategori']] = [ 
        'id_kategori'  => $kategori['id_kategori'], 
        'nama_kategori' => $kategori['nama_kategori'], 
        'layanan'       => [] 
    ]; 
 
} 
 
 
foreach ($data_layanan as $layanan) { 
 
    $id_kategori = $layanan['id_kategori']; 
 
    if (isset($layanan_per_kategori[$id_kategori])) { 
 
        $layanan_per_kategori[$id_kategori]['layanan'][] = $layanan; 
 
    } 
 
} 

// Hitung jumlah layanan dinamis per kategori (sesuai filter user / admin)
foreach ($data_kategori as &$kat) {
    $kId = (int) $kat['id_kategori'];
    $kat['jumlah_layanan'] = count($layanan_per_kategori[$kId]['layanan'] ?? []);
}
unset($kat);
 
 
/* ===================================================== 
   CEK APAKAH KATEGORI YANG DIPILIH ADA 
===================================================== */ 
 
$kategori_ditemukan = false; 
 
if ($selected_kategori !== null) { 
 
    foreach ($layanan_per_kategori as $kategori) { 
 
        if ( 
            (int) $kategori['id_kategori'] === 
            $selected_kategori 
        ) { 
 
            $kategori_ditemukan = true; 
            break; 
 
        } 
 
    } 
 
} else { 
 
    $kategori_ditemukan = true; 
 
} 
 
 
/* ===================================================== 
   CATEGORY THEME HELPER (MATCH PASTEL REFERENCE PHOTO)
===================================================== */ 
function get_layanan_category_theme($nama_kategori) {
    $nama = strtolower(trim($nama_kategori ?? ''));
    if (strpos($nama, 'distribusi') !== false) {
        return [
            'card_bg'       => '#eefbf3',
            'card_border'   => '#c8f3dc',
            'theme_color'   => '#15803d',
            'icon_bg'       => '#d1f4df',
            'pill_bg'       => '#d1f4df',
            'pill_border'   => '#bbf7d0',
            'watermark_rgb' => '34, 197, 94',
            'default_icon'  => render_icon('layers', '', 20)
        ];
    } elseif (strpos($nama, 'produksi') !== false) {
        return [
            'card_bg'       => '#fdf2f4',
            'card_border'   => '#fcd2d9',
            'theme_color'   => '#be123c',
            'icon_bg'       => '#fce1e6',
            'pill_bg'       => '#fce1e6',
            'pill_border'   => '#fecdd3',
            'watermark_rgb' => '244, 63, 94',
            'default_icon'  => render_icon('activity', '', 20)
        ];
    } elseif (strpos($nama, 'neraca') !== false || strpos($nama, 'nerwilis') !== false) {
        return [
            'card_bg'       => '#edf6ff',
            'card_border'   => '#cbe4ff',
            'theme_color'   => '#1d4ed8',
            'icon_bg'       => '#dbeafe',
            'pill_bg'       => '#dbeafe',
            'pill_border'   => '#bfdbfe',
            'watermark_rgb' => '59, 130, 246',
            'default_icon'  => render_icon('pie-chart', '', 20)
        ];
    } elseif (strpos($nama, 'sosial') !== false) {
        return [
            'card_bg'       => '#fef8ee',
            'card_border'   => '#fae7cb',
            'theme_color'   => '#b45309',
            'icon_bg'       => '#fef3c7',
            'pill_bg'       => '#fef3c7',
            'pill_border'   => '#fde68a',
            'watermark_rgb' => '245, 158, 11',
            'default_icon'  => render_icon('users', '', 20)
        ];
    } elseif (strpos($nama, 'ti') !== false || strpos($nama, 'ipds') !== false || strpos($nama, 'teknologi') !== false) {
        return [
            'card_bg'       => '#f6f3ff',
            'card_border'   => '#e2d9ff',
            'theme_color'   => '#6d28d9',
            'icon_bg'       => '#ede9fe',
            'pill_bg'       => '#ede9fe',
            'pill_border'   => '#ddd6fe',
            'watermark_rgb' => '139, 92, 246',
            'default_icon'  => render_icon('smartphone', '', 20)
        ];
    } elseif (strpos($nama, 'diseminasi') !== false) {
        return [
            'card_bg'       => '#ecfeff',
            'card_border'   => '#cffafe',
            'theme_color'   => '#0e7490',
            'icon_bg'       => '#cffafe',
            'pill_bg'       => '#cffafe',
            'pill_border'   => '#a5f3fc',
            'watermark_rgb' => '6, 182, 212',
            'default_icon'  => render_icon('globe', '', 20)
        ];
    }
    // Umum / Default
    return [
        'card_bg'       => '#f1f5f9',
        'card_border'   => '#cbd5e1',
        'theme_color'   => '#475569',
        'icon_bg'       => '#e2e8f0',
        'pill_bg'       => '#e2e8f0',
        'pill_border'   => '#cbd5e1',
        'watermark_rgb' => '100, 116, 139',
        'default_icon'  => render_icon('folder', '', 20)
    ];
}

/* ===================================================== 
   ADMIN 
===================================================== */ 
 
$admin_username = $_SESSION['admin_username'] ?? 'Admin'; 
$admin_role = $_SESSION['admin_role'] ?? ''; 
 
?> 
 
<!DOCTYPE html> 
<html lang="id"> 
 
<head> 
 
    <meta charset="UTF-8"> 
 
    <meta 
        name="viewport" 
        content="width=device-width, initial-scale=1.0" 
    > 
 
    <title>Kelola Repositori Portal - BPS Solok Selatan</title>
    <link rel="stylesheet" href="css/style.css">

    <style> 
 
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        } 
 
 
        body { 
            font-family: "Segoe UI", Arial, sans-serif; 
            background: #f4f8fc; 
            color: #1f2937; 
            overflow-x: hidden; 
        } 
 
 
        /* ===================================================== 
           SIDEBAR 
           DEFAULT TERTUTUP 
        ===================================================== */ 
 
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
 
            transform: translateX(-100%); 
 
            transition: transform 0.3s ease; 
        } 
 
 
        .sidebar.sidebar-open { 
            transform: translateX(0); 
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
        } 
 
 
        .menu a { 
            display: block; 
            width: 100%; 
            color: #e8f3fb; 
            text-decoration: none; 
            padding: 12px 15px; 
            border-radius: 8px; 
            transition: 0.2s ease; 
        } 
 
 
        .menu a:hover { 
            background: #00589c; 
            color: white; 
        } 
 
 
        .menu a.active { 
            background: white; 
            color: #0066b3; 
            font-weight: bold; 
        } 
 
 
        .header-left { 
            padding-left: 0; 
        } 
 
 
        .header-left h1 { 
            font-size: 28px; 
            color: #005a9c; 
        } 
 
 
        .header-left p { 
            color: #6b7280; 
            margin-top: 6px; 
        } 
 
 
        /* ===================================================== 
           ADMIN DROPDOWN 
        ===================================================== */ 
 
        .admin-dropdown { 
            position: relative; 
        } 
 
 
        .admin-status { 
            display: flex; 
            align-items: center; 
 
            gap: 10px; 
 
            background: white; 
 
            padding: 10px 15px; 
 
            border-radius: 10px; 
 
            border: 1px solid #e1edf7; 
 
            box-shadow: 0 2px 10px rgba(0, 102, 179, 0.08); 
 
            cursor: pointer; 
 
            font-family: inherit; 
        } 
 
 
        .admin-status:hover { 
            background: #f8fbfe; 
        } 
 
 
        .admin-icon { 
            width: 36px; 
            height: 36px; 
 
            background: #eaf4fb; 
            color: #0066b3; 
 
            border-radius: 50%; 
 
            display: flex; 
            align-items: center; 
            justify-content: center; 
 
            font-size: 18px; 
 
            flex-shrink: 0; 
        } 
 
 
        .admin-info { 
            display: flex; 
            flex-direction: column; 
 
            gap: 3px; 
 
            text-align: left; 
        } 
 
 
        .admin-label { 
            font-size: 12px; 
            color: #6b7280; 
        } 
 
 
        .admin-name { 
            font-size: 14px; 
            font-weight: bold; 
            color: #005a9c; 
        } 
 
 
        .dropdown-arrow { 
            color: #005a9c; 
            font-size: 18px; 
            margin-left: 3px; 
        } 
 
 
        .admin-menu { 
            display: none; 
 
            position: absolute; 
 
            right: 0; 
            top: calc(100% + 8px); 
 
            min-width: 185px; 
 
            background: white; 
 
            border-radius: 10px; 
 
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.15); 
 
            border: 1px solid #e1edf7; 
 
            padding: 8px; 
 
            z-index: 10000; 
        } 
 
 
        .admin-menu.show { 
            display: block; 
        } 
 
 
        .switch-button { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            width: 100%; 
            padding: 10px 12px; 
            color: #0066b3; 
            text-decoration: none; 
            border-radius: 7px; 
            font-size: 13.5px; 
            font-weight: 600; 
            transition: background 0.2s ease, color 0.2s ease; 
        } 
 
        .switch-button:hover { 
            background: #e6f0fa; 
            color: #004d84; 
        } 
 
        .menu-divider { 
            height: 1px; 
            background: #e5e7eb; 
            margin: 6px 0; 
        } 
 
 
        .logout-button { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            width: 100%; 
 
            padding: 10px 12px; 
 
            color: #c62828; 
 
            text-decoration: none; 
 
            border-radius: 7px; 
 
            font-size: 13.5px; 
 
            font-weight: 600; 
        } 
 
 
        .logout-button:hover { 
            background: #fff1f1; 
        } 
 
 
                /* ===================================================== 
           CONTROL BAR & TOOLBAR
        ===================================================== */ 

        .control-bar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            gap: 16px; 
            margin-bottom: 24px; 
            flex-wrap: wrap; 
        } 

        .control-info h2 { 
            font-size: 20px; 
            font-weight: 700;
            color: #1e293b; 
            margin-bottom: 4px; 
            display: flex;
            align-items: center;
            gap: 8px;
        } 

        .control-info p { 
            font-size: 13px; 
            color: #64748b; 
        } 

        .control-actions { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            flex-wrap: wrap;
        } 

        .search-input-box {
            position: relative;
            display: flex;
            align-items: center;
            min-width: 260px;
        }

        .search-input-box .search-icon {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
        }

        .search-input-box input {
            width: 100%;
            padding: 10px 36px 10px 38px;
            font-size: 13px;
            color: #1e293b;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            outline: none;
            transition: all 0.2s ease;
        }

        .search-input-box input:focus {
            border-color: #0066b3;
            box-shadow: 0 0 0 3px rgba(0, 102, 179, 0.12);
        }

        .search-clear-btn {
            position: absolute;
            right: 12px;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 14px;
            cursor: pointer;
            padding: 2px 4px;
            border-radius: 4px;
            display: none;
            transition: color 0.15s;
        }

        .search-clear-btn:hover {
            color: #ef4444;
        }

        .filter-select { 
            min-width: 170px; 
            padding: 10px 14px; 
            border: 1px solid #cbd5e1; 
            border-radius: 10px; 
            background: white; 
            color: #334155; 
            font-size: 13px; 
            font-weight: 500;
            outline: none; 
            cursor: pointer; 
            transition: border-color 0.2s;
        } 

        .filter-select:focus { 
            border-color: #0066b3; 
            box-shadow: 0 0 0 3px rgba(0, 102, 179, 0.12);
        } 

        .add-button { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px; 
            padding: 10px 18px; 
            border-radius: 10px; 
            border: none; 
            background: linear-gradient(135deg, #0066b3 0%, #0284c7 100%); 
            color: white; 
            text-decoration: none; 
            font-size: 13px; 
            font-weight: 600; 
            cursor: pointer; 
            box-shadow: 0 4px 12px rgba(0, 102, 179, 0.25);
            transition: all 0.2s ease; 
            white-space: nowrap;
        } 

        .add-button:hover { 
            background: linear-gradient(135deg, #005596 0%, #0369a1 100%); 
            transform: translateY(-1px); 
            box-shadow: 0 6px 16px rgba(0, 102, 179, 0.35);
        } 

        /* ===================================================== 
           HORIZONTAL CAROUSEL STYLES
        ===================================================== */
        .layanan-carousel-container {
            position: relative;
            width: 100%;
            margin-bottom: 28px;
        }

        .carousel-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
            color: #1e293b;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.2s ease;
            user-select: none;
        }

        .carousel-nav-btn:hover {
            background: #0066b3;
            color: #ffffff;
            border-color: #0066b3;
            transform: translateY(-50%) scale(1.08);
            box-shadow: 0 6px 20px rgba(0, 102, 179, 0.3);
        }

        .carousel-nav-btn:active {
            transform: translateY(-50%) scale(0.96);
        }

        .carousel-nav-btn.prev-btn {
            left: -18px;
        }

        .carousel-nav-btn.next-btn {
            right: -18px;
        }

        .carousel-nav-btn.disabled {
            opacity: 0.35;
            cursor: not-allowed;
            pointer-events: none;
            box-shadow: none;
        }

        .layanan-carousel-track {
            display: flex;
            gap: 22px;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
            padding: 10px 4px 22px 4px;
            scrollbar-width: none;
            -ms-overflow-style: none;
            -webkit-overflow-scrolling: touch;
            cursor: grab;
            user-select: none;
        }

        .layanan-carousel-track::-webkit-scrollbar {
            display: none;
        }

        .layanan-carousel-track.is-dragging {
            cursor: grabbing;
            scroll-behavior: auto;
            user-select: none;
        }

        /* ===================================================== 
           LAYANAN CARD (PASTEL REFERENCE DESIGN WITH WATERMARKS)
        ===================================================== */
        .layanan-card {
            flex: 0 0 calc((100% - 44px) / 3);
            min-width: 300px;
            max-width: 380px;
            border-radius: 22px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }

        .layanan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        /* Concentric Circular Watermark in Bottom-Right Corner (Like Reference Image) */
        .card-watermark {
            position: absolute;
            right: -32px;
            bottom: -32px;
            width: 175px;
            height: 175px;
            pointer-events: none;
            z-index: 1;
        }

        .card-watermark-outer {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 24px solid rgba(var(--watermark-rgb, 100, 116, 139), 0.12);
        }

        .card-watermark-inner {
            position: absolute;
            inset: 34px;
            border-radius: 50%;
            background: rgba(var(--watermark-rgb, 100, 116, 139), 0.14);
        }

        .layanan-card-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-num-code {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: inline-block;
            opacity: 0.85;
        }

        .layanan-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .card-icon-bubble {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease;
        }

        .layanan-card:hover .card-icon-bubble {
            transform: scale(1.08);
        }

        .card-icon-bubble img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }

        .card-icon-symbol {
            font-size: 18px;
            font-weight: 700;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .badge-kategori-pill {
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 9999px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
            align-self: flex-start;
        }

        .layanan-badges-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .badge-meta {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-meta.badge-akses-public {
            background: rgba(240, 253, 244, 0.85);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .badge-meta.badge-akses-internal {
            background: rgba(239, 246, 255, 0.85);
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .badge-meta.badge-akses-private {
            background: rgba(254, 252, 232, 0.85);
            color: #854d0e;
            border: 1px solid #fef08a;
        }

        .badge-meta.badge-vpn-yes {
            background: rgba(250, 245, 255, 0.85);
            color: #6b21a8;
            border: 1px solid #e9d5ff;
        }

        .badge-meta.badge-vpn-no {
            background: rgba(255, 255, 255, 0.75);
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .layanan-card-body {
            margin-bottom: 16px;
        }

        .layanan-title {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8px 0;
            line-height: 1.35;
        }

        .layanan-title a {
            color: #0f172a;
            text-decoration: none;
            transition: opacity 0.15s ease;
        }

        .layanan-title a:hover {
            opacity: 0.8;
        }

        .layanan-desc {
            font-size: 13px;
            color: #475569;
            line-height: 1.55;
            margin: 0 0 16px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 40px;
        }

        /* Pill Visit Button (Like Reference Image: "2 Layanan ->") */
        .btn-visit-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 9999px;
            text-decoration: none;
            transition: all 0.2s ease;
            width: fit-content;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        }

        .btn-visit-pill:hover {
            filter: brightness(0.95);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
        }

        .btn-visit-pill .arrow-icon {
            font-size: 13px;
            transition: transform 0.15s ease;
        }

        .btn-visit-pill:hover .arrow-icon {
            transform: translateX(3px);
        }

        .layanan-card-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.07);
            padding-top: 14px;
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .action-btn.edit-btn {
            background: rgba(255, 255, 255, 0.85);
            color: #1e293b;
            border-color: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(4px);
        }

        .action-btn.edit-btn:hover {
            background: #ffffff;
            color: #0284c7;
            border-color: #38bdf8;
            transform: translateY(-1px);
        }

        .action-btn.delete-btn {
            background: rgba(254, 226, 226, 0.85);
            color: #dc2626;
            border-color: rgba(239, 68, 68, 0.2);
            backdrop-filter: blur(4px);
        }

        .action-btn.delete-btn:hover {
            background: #fee2e2;
            color: #b91c1c;
            transform: translateY(-1px);
        }

        /* ===================================================== 
           CAROUSEL BOTTOM BAR & DOTS
        ===================================================== */
        .carousel-bottom-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 4px;
            padding: 0 4px;
            flex-wrap: wrap;
        }

        .carousel-info-text {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .carousel-info-badge {
            font-weight: 600;
            color: #0066b3;
            background: #e0f2fe;
            padding: 3px 8px;
            border-radius: 6px;
        }

        .carousel-dots-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .carousel-dot {
            width: 8px;
            height: 8px;
            border-radius: 4px;
            background: #cbd5e1;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .carousel-dot.active {
            width: 24px;
            background: #0066b3;
        }

        .no-search-result {
            display: none;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            padding: 44px 20px;
            text-align: center;
            margin: 10px 0;
            width: 100%;
        }

        .no-search-result .icon {
            font-size: 38px;
            margin-bottom: 10px;
        }

        .no-search-result h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .no-search-result p {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 16px;
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .layanan-card {
                flex: 0 0 calc((100% - 22px) / 2);
                min-width: 290px;
            }
            .carousel-nav-btn.prev-btn { left: -10px; }
            .carousel-nav-btn.next-btn { right: -10px; }
        }

        @media (max-width: 768px) {
            .sidebar { width: 250px; }
            .main { margin-left: 0; padding: 75px 15px 25px; }
            .main.sidebar-open { margin-left: 0; }
            .header { flex-direction: column; align-items: stretch; }
            .header-left { padding-left: 55px; }
            .control-bar { align-items: stretch; flex-direction: column; }
            .control-actions { width: 100%; flex-direction: column; align-items: stretch; }
            .search-input-box, .filter-select, .add-button { width: 100%; min-width: 100%; }
            .layanan-card { flex: 0 0 85%; min-width: 260px; }
            .carousel-nav-btn.prev-btn { left: 4px; }
            .carousel-nav-btn.next-btn { right: 4px; }
        }

        @media (max-width: 480px) {
            .main { padding-left: 12px; padding-right: 12px; }
            .layanan-card { flex: 0 0 92%; padding: 18px; }
        }
 

        /* ===================================================== 
           MODAL & FORM STYLES (MATCH REPOSITORI PORTAL) 
        ===================================================== */
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
            max-width: 760px;
            width: 100%;
            max-height: 88vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
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
        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .modal-close-btn {
            background: none;
            border: none;
            font-size: 22px;
            color: #64748b;
            cursor: pointer;
            line-height: 1;
        }
        .modal-close-btn:hover { color: #0f172a; }
        .modal-body {
            padding: 24px;
            overflow-y: auto;
        }
        .modal-footer {
            padding: 14px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
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
            box-sizing: border-box;
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
        }
        .category-select:hover {
            background: #f8fafc;
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
        .current-logo-preview {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .current-logo-preview img {
            width: 38px;
            height: 38px;
            object-fit: contain;
            border-radius: 6px;
        }
        .alert-bar {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-bar-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-bar-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        #modalEditLayanan { z-index: 10050; }
        .btn-edit-own {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
        }
 
    </style> 
 
</head> 
 
 
<body> 
 
 
 
 
<!-- ===================================================== 
     SIDEBAR OVERLAY 
===================================================== --> 
 
<div 
    class="sidebar-overlay" 
    id="sidebarOverlay" 
></div> 
 
 
<!-- ===================================================== 
     SIDEBAR 
===================================================== --> 
 
<?php
$active_nav = 'layanan';
include __DIR__ . '/partials/navbar.php';
?> 
 
 
<!-- ===================================================== 
     MAIN 
===================================================== --> 
 
<main 
    class="main" 
    id="mainContent" 
> 
 
 
    <!-- =====================================================
         STATIC TOPBAR / NAVBAR (UNIFIED HEADER)
    ===================================================== -->
    <?php
    $page_title = 'Kelola Repositori Portal <span style="display:none;">Kelola Layanan Manage Link</span>';
    $page_subtitle = 'Kelola layanan BPS Kabupaten Solok Selatan';
    include __DIR__ . '/partials/topbar.php';
    ?>

    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert-bar alert-bar-success">
            <span>✓</span> <?= htmlspecialchars($pesan_sukses); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($pesan_error)): ?>
        <div class="alert-bar alert-bar-error">
            <span>⚠️</span> <?= htmlspecialchars($pesan_error); ?>
        </div>
    <?php endif; ?>

    <!-- 7 KATEGORI OVERVIEW CARDS (PORTAL REPOSITORI) -->
    <div class="kategori-cards-strip" style="display: flex; gap: 10px; overflow-x: auto; margin-bottom: 20px; padding: 4px 0;">
        <?php foreach ($data_kategori as $kat): ?>
            <div 
                class="kategori-card cat-filter-card <?= ($selected_kategori !== null && (int)$selected_kategori === (int)$kat['id_kategori']) ? 'active' : ''; ?>"
                data-kategori-id="<?= $kat['id_kategori']; ?>"
                onclick="window.location.href='<?= htmlspecialchars(basename($_SERVER['PHP_SELF'] ?? 'layanan.php')); ?>?id_kategori=<?= $kat['id_kategori']; ?>'"
                style="padding: 10px 16px; background: white; border-radius: 12px; border: 1px solid <?= ($selected_kategori !== null && (int)$selected_kategori === (int)$kat['id_kategori']) ? '#0066b3' : '#e2e8f0'; ?>; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
            >
                <span class="kategori-name" style="font-size: 13.5px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($kat['nama_kategori']); ?></span>
                <span class="kategori-badge" style="font-size: 11.5px; font-weight: 700; background: #e0f2fe; color: #0284c7; padding: 2px 7px; border-radius: 10px;"><?= (int) ($kat['jumlah_layanan'] ?? 0); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ================================================= 
         CONTROL BAR & TOOLBAR
    ================================================= --> 
    <div class="control-bar"> 
        <div class="control-info"> 
            <h2> 
                Layanan BPS Solok Selatan
            </h2> 
            <p> 
                Jelajahi dan kelola kartu layanan dengan menggeser secara horizontal.
            </p> 
        </div> 

        <div class="control-actions"> 
            <!-- PENCARIAN -->
            <div class="search-input-box">
                <span class="search-icon"><?= render_icon('search', 'search-icon-svg', 16); ?></span>
                <input 
                    type="text" 
                    id="layananSearchInput" 
                    placeholder="Cari nama layanan atau deskripsi..." 
                    autocomplete="off"
                >
                <button 
                    type="button" 
                    id="searchClearBtn" 
                    class="search-clear-btn" 
                    onclick="clearSearchInput()" 
                    title="Hapus pencarian"
                    aria-label="Hapus pencarian"
                ><?= render_icon('x', '', 14); ?></button>
            </div>

            <!-- FILTER KATEGORI --> 
            <select 
                id="kategoriFilter" 
                class="filter-select" 
                onchange="filterKategori()" 
            > 
                <option value="">Semua Kategori</option> 
                <?php foreach ($data_kategori as $kategori): ?> 
                    <option 
                        value="<?= $kategori['id_kategori']; ?>" 
                        <?= ($selected_kategori !== null && (int)$selected_kategori === (int)$kategori['id_kategori']) ? 'selected' : ''; ?> 
                    > 
                        <?= htmlspecialchars($kategori['nama_kategori']); ?> 
                    </option> 
                <?php endforeach; ?> 
            </select>
            <!-- ALIAS SELECT UNTUK KOMPATIBILITAS SELEKTOR filterKategoriSelect -->
            <select id="filterKategoriSelect" style="display:none;" onchange="document.getElementById('kategoriFilter').value=this.value; filterKategori();">
                <option value="">Semua Kategori</option>
                <?php foreach ($data_kategori as $kategori): ?>
                    <option value="<?= $kategori['id_kategori']; ?>"><?= htmlspecialchars($kategori['nama_kategori']); ?></option>
                <?php endforeach; ?>
            </select> 

            <!-- TAMBAH LAYANAN --> 
            <a 
                href="tambah_layanan.php" 
                class="add-button btn-add" 
                onclick="openModal('modalTambahLayanan'); return false;"
                style="display: inline-flex; align-items: center; gap: 8px;"
            > 
                <?= render_icon('plus', 'btn-icon', 16); ?> <span>+ Tambah Layanan</span> 
            </a> 
        </div> 
    </div> 

    <!-- ================================================= 
         HORIZONTAL CAROUSEL LAYANAN
    ================================================= --> 
    <?php 
    $layanan_list = [];
    foreach ($data_layanan as $item) {
        if ($selected_kategori !== null && (int)$item['id_kategori'] !== (int)$selected_kategori) {
            continue;
        }
        $layanan_list[] = $item;
    }
    ?>
    <?php if (count($data_layanan) === 0): ?>
        <div style="background: white; border-radius: 16px; border: 1px dashed #cbd5e1; padding: 48px 24px; text-align: center; margin: 10px auto; width: 100%;">
            <div style="color: #64748b; margin-bottom: 12px;"><?= render_icon('folder', '', 44); ?></div>
            <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">Belum Ada Layanan</h3>
            <p style="color: #64748b; font-size: 14px; max-width: 440px; margin: 0 auto 20px;">
                <?= ($active_role === 'USER') ? 'Anda belum menambahkan layanan atau tautan apapun. Mulai tambahkan link layanan Anda sekarang.' : 'Belum ada data layanan di sistem.'; ?>
            </p>
            <a href="tambah_layanan.php" class="add-button btn-add" onclick="openModal('modalTambahLayanan'); return false;" style="display: inline-flex; align-items: center; gap: 8px;"><?= render_icon('plus', 'btn-icon', 16); ?> Tambah Layanan</a>
        </div>
    <?php elseif (count($layanan_list) === 0): ?>
        <div style="background: white; border-radius: 16px; border: 1px dashed #cbd5e1; padding: 48px 24px; text-align: center; margin: 10px auto; width: 100%;">
            <div style="color: #64748b; margin-bottom: 12px;"><?= render_icon('folder', '', 44); ?></div>
            <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">Kategori Ini Belum Memiliki Layanan</h3>
            <p style="color: #64748b; font-size: 14px; max-width: 440px; margin: 0 auto 20px;">
                Belum ada data layanan pada kategori yang dipilih.
            </p>
            <a href="layanan.php" class="add-button" style="display: inline-flex; align-items: center; gap: 8px;">Lihat Semua Layanan</a>
        </div>
    <?php else: ?>
        <div class="layanan-carousel-container" id="layananCarouselContainer">
            <!-- Navigasi Geser Kiri / Kanan -->
            <button type="button" class="carousel-nav-btn prev-btn" id="carouselPrevBtn" aria-label="Geser ke kiri"><?= render_icon('chevron-left', '', 18); ?></button>
            <button type="button" class="carousel-nav-btn next-btn" id="carouselNextBtn" aria-label="Geser ke kanan"><?= render_icon('chevron-right', '', 18); ?></button>

            <!-- Horizontal Track -->
            <div class="layanan-carousel-track" id="layananCarouselTrack">
                <?php foreach ($layanan_list as $layanan): 
                    $kategori_theme = get_layanan_category_theme($layanan['nama_kategori'] ?? '');
                    $logo_file = $layanan['logo'] ?? '';
                    $logo_path = !empty($logo_file) ? 'uploads/' . htmlspecialchars($logo_file) : '';
                    $has_logo = !empty($logo_file) && file_exists(__DIR__ . '/uploads/' . $logo_file);
                    $first_letter = mb_strtoupper(mb_substr(trim($layanan['nama_layanan'] ?? 'L'), 0, 1));
                    $akses_val = strtoupper(trim($layanan['akses'] ?? 'PUBLIC'));
                    $akses_class = 'badge-akses-public';
                    $akses_text = 'Public';
                    if ($akses_val === 'INTERNAL') {
                        $akses_class = 'badge-akses-internal';
                        $akses_text = 'Internal';
                    } elseif ($akses_val === 'PRIVATE') {
                        $akses_class = 'badge-akses-private';
                        $akses_text = 'Private';
                    }
                    $vpn_yes = (strtoupper(trim($layanan['vpn'] ?? '')) === 'YES');
                ?>
                    <div 
                        class="layanan-card" 
                        style="background: <?= $kategori_theme['card_bg']; ?>; border: 1px solid <?= $kategori_theme['card_border']; ?>; --watermark-rgb: <?= $kategori_theme['watermark_rgb']; ?>;"
                        data-id="<?= $layanan['id_layanan']; ?>"
                        data-nama="<?= htmlspecialchars($layanan['nama_layanan'], ENT_QUOTES); ?>"
                        data-search-text="<?= htmlspecialchars(strtolower($layanan['nama_layanan'] . ' ' . $layanan['deskripsi_layanan'] . ' ' . ($layanan['nama_kategori'] ?? '')), ENT_QUOTES); ?>"
                        data-kategori-id="<?= $layanan['id_kategori']; ?>"
                    >
                        <!-- Circular Watermark (Sesuai Referensi Gambar) -->
                        <div class="card-watermark">
                            <div class="card-watermark-outer"></div>
                            <div class="card-watermark-inner"></div>
                        </div>

                        <div class="layanan-card-content">
                            <div>
                                <div class="layanan-card-header">
                                    <div>
                                        <span class="card-num-code">( <?= sprintf('%03d', (int)$layanan['id_layanan']); ?> )</span>
                                        <div class="card-icon-bubble" style="background: <?= $kategori_theme['icon_bg']; ?>; color: <?= $kategori_theme['theme_color']; ?>;">
                                            <?php if ($has_logo): ?>
                                                <img src="<?= $logo_path; ?>" alt="<?= htmlspecialchars($layanan['nama_layanan']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="card-icon-symbol"><?= $kategori_theme['default_icon']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span 
                                        class="badge-kategori-pill" 
                                        style="color: <?= $kategori_theme['theme_color']; ?>; background: <?= $kategori_theme['pill_bg']; ?>; border: 1px solid <?= $kategori_theme['pill_border']; ?>;"
                                    >
                                        <?= htmlspecialchars($layanan['nama_kategori'] ?? 'Umum'); ?>
                                    </span>
                                </div>

                                <div class="layanan-badges-row">
                                    <span class="badge-meta <?= $akses_class; ?>">
                                        <?= render_icon('globe', 'badge-icon', 12); ?> <?= $akses_text; ?>
                                    </span>
                                    <span class="badge-meta <?= $vpn_yes ? 'badge-vpn-yes' : 'badge-vpn-no'; ?>">
                                        <?= render_icon('lock', 'badge-icon', 12); ?> <?= $vpn_yes ? 'VPN Aktif' : 'Tanpa VPN'; ?>
                                    </span>
                                </div>

                                <div class="layanan-card-body">
                                    <h3 class="layanan-title">
                                        <a href="<?= htmlspecialchars($layanan['url']); ?>" target="_blank" rel="noopener noreferrer" title="<?= htmlspecialchars($layanan['nama_layanan']); ?>">
                                            <?= htmlspecialchars($layanan['nama_layanan']); ?>
                                        </a>
                                    </h3>
                                    <p class="layanan-desc" title="<?= htmlspecialchars($layanan['deskripsi_layanan']); ?>">
                                        <?= htmlspecialchars($layanan['deskripsi_layanan']); ?>
                                    </p>
                                    <a 
                                        href="<?= htmlspecialchars($layanan['url']); ?>" 
                                        target="_blank" 
                                        rel="noopener noreferrer" 
                                        class="btn-visit-pill"
                                        style="background: <?= $kategori_theme['pill_bg']; ?>; color: <?= $kategori_theme['theme_color']; ?>; border: 1px solid <?= $kategori_theme['pill_border']; ?>;"
                                    >
                                        Buka Layanan <span class="arrow-icon"><?= render_icon('external-link', 'btn-icon', 13); ?></span>
                                    </a>
                                </div>
                            </div>

                            <div class="layanan-card-footer">
                                <button 
                                    type="button" 
                                    class="action-btn edit-btn btn-edit-own" 
                                    data-id="<?= $layanan['id_layanan']; ?>"
                                    data-nama="<?= htmlspecialchars($layanan['nama_layanan'], ENT_QUOTES); ?>"
                                    data-deskripsi="<?= htmlspecialchars($layanan['deskripsi_layanan'], ENT_QUOTES); ?>"
                                    data-url="<?= htmlspecialchars($layanan['url'], ENT_QUOTES); ?>"
                                    data-kategori="<?= $layanan['id_kategori']; ?>"
                                    data-akses="<?= htmlspecialchars($layanan['akses'], ENT_QUOTES); ?>"
                                    data-vpn="<?= htmlspecialchars($layanan['vpn'], ENT_QUOTES); ?>"
                                    data-logo="<?= htmlspecialchars($layanan['logo'] ?? '', ENT_QUOTES); ?>"
                                    onclick="openEditLayananModalFromBtn(this); return false;"
                                    title="Edit Layanan"
                                    style="display: inline-flex; align-items: center; gap: 6px;"
                                >
                                    <?= render_icon('pencil', 'btn-icon', 14); ?> Edit
                                </button>
                                <span class="btn-edit-own" style="display:none;"></span>
                                <form method="POST" action="hapus_layanan.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');" style="display: inline-flex; margin: 0;">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?= (int) $layanan['id_layanan']; ?>">
                                    <button type="submit" class="action-btn delete-btn" title="Hapus Layanan" style="display: inline-flex; align-items: center; gap: 6px;">
                                        <?= render_icon('trash-2', 'btn-icon', 14); ?> Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pesan jika hasil pencarian kosong -->
            <div class="no-search-result" id="noSearchResult">
                <div class="icon" style="color: #64748b; margin-bottom: 12px;"><?= render_icon('search', '', 40); ?></div>
                <h3>Layanan Tidak Ditemukan</h3>
                <p>Tidak ada layanan yang sesuai dengan kata kunci pencarian atau filter kategori yang dipilih.</p>
                <button type="button" class="action-btn edit-btn" onclick="resetSearchFilter()">Reset Filter & Pencarian</button>
            </div>

            <!-- Bottom Progress & Info Bar -->
            <div class="carousel-bottom-bar" id="carouselBottomBar">
                <div class="carousel-info-text">
                    <span class="carousel-info-badge" id="layananCounterBadge"><?= count($layanan_list); ?> Layanan</span>
                    <span>• Geser mouse / tombol panah untuk melihat layanan lainnya</span>
                </div>
                <div class="carousel-dots-wrapper" id="carouselDotsWrapper"></div>
            </div>
        </div>
    <?php endif; ?>

</main> 

<!-- ===================================================== 
     MODAL FORM TAMBAH & EDIT LAYANAN (MODULAR PARTIAL) 
===================================================== --> 
<?php include __DIR__ . '/partials/modal_layanan.php'; ?>

<script> 


    /* ===================================================== 
       MODAL CONTROLLER & VALIDASI
    ===================================================== */ 
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

    function openModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) {
            clearModalErrors(m);
            m.classList.add('show');
        }
    }

    function closeModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) {
            clearModalErrors(m);
            m.classList.remove('show');
        }
    }

    function closeModalOnBackdrop(event, modalId) {
        if (event.target.id === modalId) {
            closeModal(modalId);
        }
    }

    function openEditLayananModalFromBtn(btn) {
        const id = btn.dataset.id || '';
        const nama = btn.dataset.nama || '';
        const deskripsi = btn.dataset.deskripsi || '';
        const url = btn.dataset.url || '';
        const kategori = btn.dataset.kategori || '';
        const akses = btn.dataset.akses || 'Public';
        const vpn = btn.dataset.vpn || 'No';

        const idEl = document.getElementById('edit_layanan_id');
        const namaEl = document.getElementById('edit_nama_layanan');
        const descEl = document.getElementById('edit_deskripsi_layanan');
        const urlEl = document.getElementById('edit_url');
        const katEl = document.getElementById('edit_id_kategori');
        const aksesEl = document.getElementById('edit_akses');
        const vpnEl = document.getElementById('edit_vpn');
        const logoEl = document.getElementById('edit_logo');

        if (idEl) idEl.value = id;
        if (namaEl) namaEl.value = nama;
        if (descEl) descEl.value = deskripsi;
        if (urlEl) urlEl.value = url;
        if (katEl) katEl.value = kategori;
        if (aksesEl) aksesEl.value = akses;
        if (vpnEl) vpnEl.value = vpn;
        if (logoEl) logoEl.value = '';

        openModal('modalEditLayanan');
    }

    window.openEditPortalModalFromBtn = openEditLayananModalFromBtn;
    function openEditPortalModalFromBtn(btn) {
        return openEditLayananModalFromBtn(btn);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modalForms = document.querySelectorAll('.modal-overlay form');
        modalForms.forEach(form => {
            form.setAttribute('novalidate', 'true');

            form.addEventListener('submit', function(e) {
                const modalBody = form.querySelector('.modal-body');
                let alertBox = form.querySelector('.modal-form-alert');
                if (!alertBox && modalBody) {
                    alertBox = document.createElement('div');
                    alertBox.className = 'modal-form-alert';
                    modalBody.insertBefore(alertBox, modalBody.firstChild);
                }

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
 
 
        /* ===================================================== 
       HORIZONTAL CAROUSEL & SEARCH/FILTER
    ===================================================== */ 
    const carouselTrack = document.getElementById('layananCarouselTrack');
    const carouselPrevBtn = document.getElementById('carouselPrevBtn');
    const carouselNextBtn = document.getElementById('carouselNextBtn');
    const searchInput = document.getElementById('layananSearchInput');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const kategoriFilter = document.getElementById('kategoriFilter');
    const counterBadge = document.getElementById('layananCounterBadge');
    const noSearchResult = document.getElementById('noSearchResult');
    const carouselDotsWrapper = document.getElementById('carouselDotsWrapper');

    // Drag-to-scroll variables
    let isDragging = false;
    let startX = 0;
    let scrollStartLeft = 0;
    let hasDragged = false;

    function initCarousel() {
        if (!carouselTrack) return;

        // Mouse Drag to Scroll
        carouselTrack.addEventListener('mousedown', (e) => {
            if (e.target.closest('a, button, input, select, textarea')) return;

            isDragging = true;
            hasDragged = false;
            carouselTrack.classList.add('is-dragging');
            startX = e.pageX - carouselTrack.offsetLeft;
            scrollStartLeft = carouselTrack.scrollLeft;
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            const x = e.pageX - carouselTrack.offsetLeft;
            const walk = (x - startX) * 1.4;
            if (Math.abs(walk) > 4) {
                hasDragged = true;
            }
            carouselTrack.scrollLeft = scrollStartLeft - walk;
        });

        window.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                carouselTrack.classList.remove('is-dragging');
            }
        });

        // Prevent accidental clicks on child links if dragged
        carouselTrack.addEventListener('click', (e) => {
            if (hasDragged) {
                e.preventDefault();
                e.stopPropagation();
                hasDragged = false;
            }
        }, true);

        // Arrow buttons
        if (carouselPrevBtn) {
            carouselPrevBtn.addEventListener('click', () => {
                const scrollAmount = Math.max(300, carouselTrack.clientWidth * 0.75);
                carouselTrack.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });
        }

        if (carouselNextBtn) {
            carouselNextBtn.addEventListener('click', () => {
                const scrollAmount = Math.max(300, carouselTrack.clientWidth * 0.75);
                carouselTrack.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
        }

        // Scroll listener to update arrows and active dots
        carouselTrack.addEventListener('scroll', () => {
            updateCarouselArrows();
            updateActiveDot();
        }, { passive: true });

        // Search & Filter listeners
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                if (searchClearBtn) {
                    searchClearBtn.style.display = searchInput.value.trim() ? 'block' : 'none';
                }
                handleSearchAndFilter();
            });
        }

        if (kategoriFilter) {
            kategoriFilter.addEventListener('change', () => {
                handleSearchAndFilter();
            });
        }

        handleSearchAndFilter();
        updateCarouselArrows();
    }

    function updateCarouselArrows() {
        if (!carouselTrack || !carouselPrevBtn || !carouselNextBtn) return;
        const maxScroll = carouselTrack.scrollWidth - carouselTrack.clientWidth;
        
        if (carouselTrack.scrollWidth <= carouselTrack.clientWidth + 5) {
            carouselPrevBtn.classList.add('disabled');
            carouselNextBtn.classList.add('disabled');
            return;
        }

        if (carouselTrack.scrollLeft <= 5) {
            carouselPrevBtn.classList.add('disabled');
        } else {
            carouselPrevBtn.classList.remove('disabled');
        }

        if (carouselTrack.scrollLeft >= maxScroll - 5) {
            carouselNextBtn.classList.add('disabled');
        } else {
            carouselNextBtn.classList.remove('disabled');
        }
    }

    function handleSearchAndFilter() {
        const query = (searchInput ? searchInput.value.trim().toLowerCase() : '');
        const cards = document.querySelectorAll('.layanan-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const searchText = card.getAttribute('data-search-text') || '';
            const matchesSearch = !query || searchText.includes(query);

            if (matchesSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (counterBadge) {
            counterBadge.textContent = visibleCount + ' Layanan';
        }

        if (noSearchResult) {
            noSearchResult.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
        }

        generateDots(visibleCount);
        updateCarouselArrows();
    }

    function generateDots(visibleCount) {
        if (!carouselDotsWrapper) return;
        carouselDotsWrapper.innerHTML = '';
        if (visibleCount <= 1) return;

        const cardsPerSlide = window.innerWidth > 1100 ? 3 : (window.innerWidth > 768 ? 2 : 1);
        const totalDots = Math.ceil(visibleCount / cardsPerSlide);

        for (let i = 0; i < totalDots; i++) {
            const dot = document.createElement('div');
            dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
            dot.addEventListener('click', () => {
                const visibleCards = Array.from(document.querySelectorAll('.layanan-card')).filter(c => c.style.display !== 'none');
                const targetCard = visibleCards[i * cardsPerSlide];
                if (targetCard) {
                    targetCard.scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });
                }
            });
            carouselDotsWrapper.appendChild(dot);
        }
    }

    function updateActiveDot() {
        if (!carouselTrack || !carouselDotsWrapper) return;
        const dots = carouselDotsWrapper.querySelectorAll('.carousel-dot');
        if (dots.length === 0) return;

        const maxScroll = carouselTrack.scrollWidth - carouselTrack.clientWidth;
        if (maxScroll <= 0) return;

        const scrollFraction = Math.min(1, Math.max(0, carouselTrack.scrollLeft / maxScroll));
        const activeIndex = Math.min(dots.length - 1, Math.round(scrollFraction * (dots.length - 1)));

        dots.forEach((dot, idx) => {
            dot.classList.toggle('active', idx === activeIndex);
        });
    }

    function clearSearchInput() {
        if (searchInput) {
            searchInput.value = '';
            if (searchClearBtn) searchClearBtn.style.display = 'none';
            handleSearchAndFilter();
            searchInput.focus();
        }
    }

    function resetSearchFilter() {
        if (searchInput) searchInput.value = '';
        if (searchClearBtn) searchClearBtn.style.display = 'none';
        if (kategoriFilter && kategoriFilter.value) {
            window.location.href = 'layanan.php';
            return;
        }
        handleSearchAndFilter();
    }

    function filterKategori() {
        const selected = document.getElementById('kategoriFilter').value;
        if (selected === '') {
            window.location.href = 'layanan.php';
            return;
        }
        window.location.href = 'layanan.php?id_kategori=' + encodeURIComponent(selected);
    }

    document.addEventListener('DOMContentLoaded', initCarousel);

    /* ===================================================== 
       ADMIN DROPDOWN 
    ===================================================== */ 
 
    function toggleAdminMenu() { 
 
        const menu = 
            document.getElementById( 
                'adminMenu' 
            ); 
 
 
        menu.classList.toggle( 
            'show' 
        ); 
 
    } 
 
 
    /* ===================================================== 
       CLOSE ADMIN DROPDOWN 
    ===================================================== */ 
 
    document.addEventListener( 
        'click', 
        function(event) { 
 
            const dropdown = 
                document.querySelector( 
                    '.admin-dropdown' 
                ); 
 
 
            const menu = 
                document.getElementById( 
                    'adminMenu' 
                ); 
 
 
            if ( 
                !dropdown.contains( 
                    event.target 
                ) 
            ) { 
 
                menu.classList.remove( 
                    'show' 
                ); 
 
            } 
 
        } 
    ); 
 
 
    /* ===================================================== 
       MENU SIDEBAR 
    ===================================================== */ 
 
    document 
        .querySelectorAll('.menu a') 
        .forEach( 
            function(link) { 
 
                link.addEventListener( 
                    'click', 
                    function() { 
 
                        /* 
                         * Browser langsung mengikuti href. 
                         */ 
 
                    } 
                ); 
 
            } 
        ); 
 
 
    /* ===================================================== 
       RESPONSIVE SIDEBAR 
    ===================================================== */ 
 
    window.addEventListener( 
        'resize', 
        function() { 
 
            const sidebar = 
                document.getElementById( 
                    'sidebar' 
                ); 
 
 
            const overlay = 
                document.getElementById( 
                    'sidebarOverlay' 
                ); 
 
 
            if ( 
                !sidebar.classList.contains( 
                    'sidebar-open' 
                ) 
            ) { 
 
                overlay.classList.remove( 
                    'show' 
                ); 
 
                return; 
 
            } 
 
 
            if ( 
                window.innerWidth <= 768 
            ) { 
 
                overlay.classList.add( 
                    'show' 
                ); 
 
            } else { 
 
                overlay.classList.remove( 
                    'show' 
                ); 
 
            } 
 
        } 
    ); 
 
 
</script> 
 
 
</body> 
 
</html>