<?php

require_once '../config/config.php';
require_once '../config/auth.php';
require_once '../config/user_repo.php';
require_once __DIR__ . '/partials/icons.php';

/* =========================
   CEK LOGIN ADMIN / USER
========================= */

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$is_real_admin = is_admin_user();
$active_role = auth_active_role();
$admin_role = $active_role;
$user_id = (int) ($_SESSION['admin_id'] ?? 0);

$filter_kategori = trim($_GET['kategori'] ?? '');

$stmt_layanan = $conn->query("
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
    ORDER BY l.id_kategori ASC, l.id_layanan DESC
");
$layanan_terbaru = $stmt_layanan->fetchAll(PDO::FETCH_ASSOC);

$stmt_kategori = $conn->query("
    SELECT
        k.id_kategori,
        k.nama_kategori,
        COUNT(l.id_layanan) AS jumlah_layanan
    FROM kategori k
    LEFT JOIN layanan l
        ON k.id_kategori = l.id_kategori
    GROUP BY
        k.id_kategori,
        k.nama_kategori
    ORDER BY k.id_kategori ASC
");
$data_kategori = $stmt_kategori->fetchAll(PDO::FETCH_ASSOC);

$total_layanan = count($layanan_terbaru);
$total_kategori = count($data_kategori);

if ($active_role === 'USER') {
    $portal_summary = user_get_portal_summary($user_id);
    $fav_summary = user_get_favorites_summary($user_id);
}


/* =========================
   DESKRIPSI KATEGORI
========================= */

$deskripsi_kategori = [

    'Distribusi' =>
        'Perdagangan, harga, transportasi, pariwisata, distribusi barang/jasa.',

    'Produksi' =>
        'Pertanian, industri, pertambangan, energi, konstruksi, peternakan, kehutanan, perikanan.',

    'Neraca' =>
        'PDRB, neraca wilayah, neraca produksi/pengeluaran, analisis ekonomi.',

    'Sosial' =>
        'Penduduk, ketenagakerjaan, pendidikan, kesehatan, kemiskinan, dan sosial.',

    'TI' =>
        'Teknologi informasi, sistem, aplikasi, dan layanan digital statistik.',

    'Umum' =>
        'Informasi umum, administrasi, layanan internal, dan kebutuhan pendukung.',

    'Diseminasi' =>
        'Layanan dan media untuk menyebarkan data statistik kepada pengguna.'
];


/* =========================
   WARNA KATEGORI
========================= */

$kelas_warna = [
    'blue',
    'orange',
    'green',
    'purple',
    'teal',
    'gray',
    'pink'
];


/* =========================
   KELOMPOKKAN LAYANAN
========================= */

$layanan_per_kategori = [];

foreach ($data_kategori as $kategori) {

    $layanan_per_kategori[
        (int) $kategori['id_kategori']
    ] = [];

}

foreach ($layanan_terbaru as $item) {

    $id_kategori = (int) $item['id_kategori'];

    if (!isset($layanan_per_kategori[$id_kategori])) {

        $layanan_per_kategori[$id_kategori] = [];

    }

    $layanan_per_kategori[$id_kategori][] = $item;
}

/* =========================
   TEMA VISUAL KATEGORI (REFERENSI DESAIN CAROUSEL)
========================= */
function get_category_theme($nama_kategori, $index) {
    $nama = strtolower(trim($nama_kategori));

    $themes = [
        'distribusi' => [
            'bg' => 'linear-gradient(145deg, #f0fdf4 0%, #dcfce7 100%)',
            'border' => '#bbf7d0',
            'tag' => '#15803d',
            'icon_bg' => '#bbf7d0',
            'icon_color' => '#166534',
            'title' => '#064e3b',
            'pill_bg' => '#bbf7d0',
            'pill_color' => '#14532d',
            'decor' => 'rgba(74, 222, 128, 0.45)',
            'svg' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>'
        ],
        'produksi' => [
            'bg' => 'linear-gradient(145deg, #fff1f2 0%, #fce7f3 100%)',
            'border' => '#fecdd3',
            'tag' => '#be185d',
            'icon_bg' => '#fbcfe8',
            'icon_color' => '#9d174d',
            'title' => '#831843',
            'pill_bg' => '#fce7f3',
            'pill_color' => '#831843',
            'decor' => 'rgba(244, 114, 182, 0.45)',
            'svg' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
        ],
        'neraca' => [
            'bg' => 'linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%)',
            'border' => '#bfdbfe',
            'tag' => '#1d4ed8',
            'icon_bg' => '#bfdbfe',
            'icon_color' => '#1e40af',
            'title' => '#1e3a8a',
            'pill_bg' => '#dbeafe',
            'pill_color' => '#1e3a8a',
            'decor' => 'rgba(96, 165, 250, 0.45)',
            'svg' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.563-2.512 5.563-5.563C22 6.5 17.5 2 12 2z"/></svg>'
        ],
        'sosial' => [
            'bg' => 'linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%)',
            'border' => '#fde68a',
            'tag' => '#b45309',
            'icon_bg' => '#fde68a',
            'icon_color' => '#92400e',
            'title' => '#78350f',
            'pill_bg' => '#fef3c7',
            'pill_color' => '#92400e',
            'decor' => 'rgba(251, 191, 36, 0.45)',
            'svg' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
        ],
        'ti' => [
            'bg' => 'linear-gradient(145deg, #f0fdfa 0%, #ccfbf1 100%)',
            'border' => '#99f6e4',
            'tag' => '#0f766e',
            'icon_bg' => '#99f6e4',
            'icon_color' => '#115e59',
            'title' => '#134e4a',
            'pill_bg' => '#ccfbf1',
            'pill_color' => '#115e59',
            'decor' => 'rgba(45, 212, 191, 0.45)',
            'svg' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>'
        ],
        'umum' => [
            'bg' => 'linear-gradient(145deg, #faf5ff 0%, #f3e8ff 100%)',
            'border' => '#e9d5ff',
            'tag' => '#7e22ce',
            'icon_bg' => '#e9d5ff',
            'icon_color' => '#6b21a8',
            'title' => '#581c87',
            'pill_bg' => '#f3e8ff',
            'pill_color' => '#6b21a8',
            'decor' => 'rgba(192, 132, 252, 0.45)',
            'svg' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 8 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>'
        ],
        'diseminasi' => [
            'bg' => 'linear-gradient(145deg, #fff7ed 0%, #ffedd5 100%)',
            'border' => '#fed7aa',
            'tag' => '#c2410c',
            'icon_bg' => '#fed7aa',
            'icon_color' => '#9a3412',
            'title' => '#7c2d12',
            'pill_bg' => '#ffedd5',
            'pill_color' => '#9a3412',
            'decor' => 'rgba(251, 146, 60, 0.45)',
            'svg' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>'
        ]
    ];

    if (isset($themes[$nama])) {
        return $themes[$nama];
    }

    $fallback_keys = array_keys($themes);
    return $themes[$fallback_keys[$index % count($fallback_keys)]];
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

<title><?= ($active_role === 'ADMIN') ? 'Dashboard Admin' : 'Dashboard User'; ?> BPS Solok Selatan</title>
<link rel="stylesheet" href="css/style.css">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


html {
    scroll-behavior: smooth;
}


body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f4f8fc;
    color: #1f2937;
    overflow-x: hidden;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {
    width: 240px;
    height: 100vh;

    position: fixed;
    left: 0;
    top: 0;

    background: #0066b3;
    color: white;

    padding: 25px 15px;

    z-index: 9999;

    box-shadow:
        2px 0 10px rgba(0, 0, 0, 0.08);

    transform: translateX(-100%);

    transition:
        transform 0.3s ease;
}


.sidebar.sidebar-open {
    transform: translateX(0);
}


/* =====================================================
   LOGO SIDEBAR
===================================================== */

.logo {
    font-size: 20px;
    font-weight: bold;

    margin-bottom: 40px;

    padding: 0 15px;
}


/* =====================================================
   MENU
===================================================== */

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

    font-family: inherit;

    font-size: 16px;

    font-weight: 400;
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




/* =====================================================
   OVERLAY
===================================================== */

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


/* =====================================================
   MAIN
===================================================== */

.main {
    margin-left: 0;

    padding: 30px;

    min-width: 0;

    transition:
        margin-left 0.3s ease;
}


.main.sidebar-open {
    margin-left: 240px;
}


/* =====================================================
   HEADER
===================================================== */

.header {
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: static;
    gap: 20px;
}

.header-left {
    padding-left: 0;
    flex: 1;
}


.header h1 {
    font-size: 28px;

    line-height: 1.25;

    font-weight: 700;

    color: #005a9c;

    margin-bottom: 6px;
}


.header p {
    color: #6b7280;

    margin-top: 0;

    font-size: 15px;

    line-height: 1.5;
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

    box-shadow:
        0 2px 10px rgba(0, 102, 179, 0.08);

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

    box-shadow:
        0 5px 18px rgba(0, 0, 0, 0.15);

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
/* =====================================================
   TOTAL CARDS / ADMIN OVERVIEW CARDS
===================================================== */

.cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.cat-overview-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 22px 24px;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    cursor: pointer;
}

.cat-overview-card:hover {
    border-color: #0066b3;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 102, 179, 0.08);
}

.cat-overview-card.active {
    border: 1.5px solid #0066b3;
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

.cat-overview-card:hover .cat-count-badge {
    background: #0066b3;
    color: white;
}

.cat-overview-title {
    font-size: 17px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
    line-height: 1.3;
    transition: color 0.2s ease;
}

.cat-overview-card.active .cat-overview-title {
    color: #005a9c;
}

.cat-overview-card:hover .cat-overview-title {
    color: #005a9c;
}

.cat-overview-subtitle {
    font-size: 13.5px;
    color: #6b7280;
    line-height: 1.45;
}

/* =====================================================
   USER DASHBOARD SUMMARY CARDS
===================================================== */

.user-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.user-summary-card {
    background: white;
    border-radius: 18px;
    border: 1px solid #e1edf7;
    box-shadow: 0 4px 18px rgba(0, 102, 179, 0.06);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.user-summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0, 102, 179, 0.12);
}

.user-card-header {
    padding: 22px 24px 18px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
}

.user-card-title-group {
    display: flex;
    align-items: center;
    gap: 14px;
}

.user-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #e6f0fa;
    color: #0066b3;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.user-card-icon.fav {
    background: #fef9c3;
    color: #ca8a04;
}

.user-card-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
}

.user-card-subtitle {
    font-size: 13px;
    color: #64748b;
    margin-top: 2px;
}

.user-card-stat {
    font-size: 32px;
    font-weight: 800;
    color: #0066b3;
}

.user-card-stat.fav {
    color: #d97706;
}

.user-card-body {
    padding: 20px 24px;
    flex: 1;
}

.user-card-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.user-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.user-list-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.user-item-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-item-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    background: #e2e8f0;
    color: #475569;
    white-space: nowrap;
}

.user-card-footer {
    padding: 16px 24px;
    background: #fafcff;
    border-top: 1px solid #f1f5f9;
}

.user-card-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px 18px;
    background: #0066b3;
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.2s ease;
}

.user-card-btn:hover {
    background: #004d84;
}

.user-card-btn.secondary {
    background: white;
    color: #0066b3;
    border: 1.5px solid #0066b3;
}

.user-card-btn.secondary:hover {
    background: #f0f7ff;
}

.user-empty-state {
    padding: 30px 16px;
    text-align: center;
    color: #64748b;
    font-size: 14px;
}


/* =====================================================
   SECTION
===================================================== */

.section-heading {
    display: flex;

    justify-content: space-between;

    align-items: end;

    gap: 20px;

    margin: 5px 0 20px;
}


.section-heading h2 {
    font-size: 20px;

    line-height: 1.35;

    font-weight: 700;

    color: #1f2937;
}


.section-heading p {
    font-size: 13px;

    color: #6b7280;

    margin-top: 4px;
}


/* =====================================================
   CAROUSEL KATEGORI LAYANAN (DESAIN REFERENSI)
===================================================== */

.cat-carousel-container {
    position: relative;
    width: 100%;
    margin: 20px 0 10px;
    display: flex;
    align-items: center;
}

.cat-arrow-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: white;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #1e293b;
    z-index: 10;
    transition: all 0.2s ease;
}

.cat-arrow-btn:hover {
    background: #f8fafc;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
    color: #0066b3;
    transform: translateY(-50%) scale(1.06);
}

.cat-arrow-btn.prev {
    left: -20px;
}

.cat-arrow-btn.next {
    right: -20px;
}

.cat-carousel-track {
    display: flex;
    gap: 22px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scroll-snap-type: x mandatory;
    padding: 10px 4px 16px;
    width: 100%;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.cat-carousel-track::-webkit-scrollbar {
    display: none;
}

.cat-ref-card {
    flex: 0 0 calc((100% - 44px) / 3);
    min-width: 290px;
    border-radius: 20px;
    padding: 24px 26px 22px;
    position: relative;
    overflow: hidden;
    scroll-snap-align: start;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 275px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.cat-ref-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
}

.cat-watermark {
    position: absolute;
    right: -30px;
    bottom: -30px;
    width: 170px;
    height: 170px;
    pointer-events: none;
    z-index: 1;
}

.cat-arc-outer {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 24px solid currentColor;
    opacity: 0.8;
}

.cat-arc-inner {
    position: absolute;
    inset: 22px;
    border-radius: 50%;
    border: 18px solid currentColor;
    opacity: 0.5;
}

.cat-card-top {
    position: relative;
    z-index: 2;
}

.cat-code-tag {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    display: inline-block;
}

.cat-icon-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    transition: transform 0.2s ease;
}

.cat-ref-card:hover .cat-icon-circle {
    transform: scale(1.08);
}

.cat-title {
    font-size: 21px;
    font-weight: 700;
    margin-bottom: 8px;
    line-height: 1.25;
}

.cat-desc {
    font-size: 13px;
    line-height: 1.55;
    color: #475569;
    margin-bottom: 20px;
    min-height: 58px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.cat-card-bottom {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.cat-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 18px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
}

.cat-pill-btn:hover {
    filter: brightness(0.94);
    transform: translateX(2px);
}

.cat-pill-btn .cat-arrow {
    transition: transform 0.2s ease;
}

.cat-pill-btn:hover .cat-arrow {
    transform: translateX(3px);
}

.cat-dots-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 8px 0 32px;
}

.cat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
    cursor: pointer;
    transition: all 0.25s ease;
    border: none;
    padding: 0;
}

.cat-dot.active {
    background: #0066b3;
    width: 22px;
    border-radius: 5px;
}

@media (max-width: 1024px) {
    .cat-ref-card {
        flex: 0 0 calc((100% - 22px) / 2);
    }
    .cat-arrow-btn.prev {
        left: -12px;
    }
    .cat-arrow-btn.next {
        right: -12px;
    }
}

@media (max-width: 680px) {
    .cat-ref-card {
        flex: 0 0 100%;
        min-width: 100%;
    }
    .cat-arrow-btn {
        display: none;
    }
}


/* =====================================================
   MODAL
===================================================== */

.modal-overlay {
    display: none;

    position: fixed;

    inset: 0;

    background:
        rgba(24, 35, 56, 0.62);

    backdrop-filter: blur(2px);

    z-index: 20000;

    padding: 34px;
}


.modal-overlay.show {
    display: flex;

    align-items: center;

    justify-content: center;
}


.modal {
    width: min(100%, 1180px);

    max-height:
        calc(100vh - 68px);

    background: white;

    border-radius: 28px;

    overflow: hidden;

    box-shadow:
        0 25px 70px rgba(0, 0, 0, 0.25);

    display: flex;

    flex-direction: column;
}


.modal-header {
    padding: 32px 40px 21px;

    border-bottom:
        1px solid #e7edf4;

    position: relative;
}


.modal-title-row {
    display: flex;

    align-items: center;

    gap: 11px;

    margin-bottom: 16px;
}


.modal-title-dot {
    width: 14px;
    height: 14px;

    border-radius: 50%;

    background: #183b7a;
}


.modal-title {
    font-size: 21px;

    line-height: 1.3;

    letter-spacing: 0.8px;

    color: #91a2ba;

    font-weight: 700;

    text-transform: uppercase;
}


.modal-focus {
    font-size: 18px;

    line-height: 1.6;

    color: #657894;

    padding-right: 50px;
}


.modal-close {
    position: absolute;

    right: 28px;

    top: 26px;

    border: none;

    background: transparent;

    color: #91a2ba;

    font-size: 31px;

    line-height: 1;

    cursor: pointer;

    font-family: inherit;
}


.modal-list {
    overflow-y: auto;

    padding: 0 40px;
}


.modal-item {
    display: flex;

    align-items: center;

    gap: 18px;

    min-height: 108px;

    border-bottom:
        1px solid #e7edf4;
}


.modal-item .service-icon {
    width: 54px;
    height: 54px;

    font-size: 23px;
}


.modal-item-main {
    min-width: 0;

    flex: 1;
}


.modal-item-name {
    font-size: 20px;

    line-height: 1.4;

    font-weight: 700;

    color: #17233c;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.modal-item-category {
    font-size: 15px;

    color: #93a5bd;

    margin-left: 7px;
}


.modal-open {
    display: flex;

    align-items: center;

    gap: 25px;

    color: #657894;

    text-decoration: none;

    font-size: 14px;

    font-weight: 600;

    white-space: nowrap;
}


.modal-open:hover {
    color: #183b7a;
}


.modal-arrow {
    font-size: 23px;

    color: #c2cfdd;
}


.modal-empty {
    text-align: center;

    padding: 50px;

    color: #7b8da8;

    font-size: 14px;
}


.modal-footer {
    padding: 15px 40px;

    color: #93a5bd;

    font-size: 12px;

    background: #fbfcfe;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

    .category-grid {
        grid-template-columns: 1fr;
    }

}


@media (max-width: 768px) {

    .sidebar {
        width: 250px;
    }


    .main {
        margin-left: 0;

        padding:
            75px 15px 25px;
    }


    .main.sidebar-open {
        margin-left: 0;
    }


    .header-top {
        flex-direction: column;

        gap: 15px;
    }


    .header-left {
        padding-left: 55px;
    }


    .header h1 {
        font-size: 23px;
    }


    .header p {
        font-size: 13px;
    }


    .admin-dropdown,
    .admin-status {
        width: 100%;
    }


    .admin-status {
        justify-content: flex-start;
    }


    .cards {
        grid-template-columns: 1fr;

        gap: 14px;
    }


    .category-grid {
        grid-template-columns: 1fr;

        gap: 18px;
    }


    .category-inner {
        min-height: auto;

        padding: 24px 20px;
    }


    .category-name {
        font-size: 25px;
    }


    .popular-item {
        gap: 12px;
    }


    .service-icon {
        width: 48px;
        height: 48px;

        border-radius: 12px;

        font-size: 20px;
    }


    .service-name {
        font-size: 14px;
    }


    .view-all {
        min-height: 52px;

        font-size: 14px;
    }


    .modal-overlay {
        padding: 12px;
    }


    .modal {
        max-height:
            calc(100vh - 24px);

        border-radius: 20px;
    }


    .modal-header {
        padding:
            24px 21px 17px;
    }


    .modal-focus {
        font-size: 15px;

        padding-right: 25px;
    }


    .modal-title {
        font-size: 17px;
    }


    .modal-list {
        padding: 0 21px;
    }


    .modal-item {
        min-height: 86px;

        gap: 11px;
    }


    .modal-item .service-icon {
        width: 43px;
        height: 43px;

        font-size: 18px;
    }


    .modal-item-name {
        font-size: 15px;
    }


    .modal-item-category {
        display: block;

        margin: 3px 0 0;

        font-size: 12px;
    }


    .modal-open {
        gap: 7px;

        font-size: 13px;
    }


    .modal-footer {
        padding:
            11px 21px;
    }

}


@media (max-width: 480px) {

    .main {
        padding-left: 12px;

        padding-right: 12px;
    }


    .header h1 {
        font-size: 21px;
    }


    .cards {
        margin-bottom: 22px;
    }


    .category-card {
        padding: 10px;

        border-radius: 17px;
    }


    .category-inner {
        padding:
            22px 16px;

        border-radius: 14px;
    }


    .category-name {
        font-size: 23px;
    }


    .category-focus {
        font-size: 13px;
    }


    .category-dot {
        width: 23px;
        height: 23px;
    }


    .popular-item {
        gap: 12px;
    }


    .service-icon {
        width: 48px;
        height: 48px;
    }


    .service-name {
        font-size: 14px;
    }


    .modal-close {
        right: 17px;

        top: 18px;

        font-size: 28px;
    }

}

</style>

</head>


<body>




<?php
$active_nav = 'dashboard';
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
    $page_title = ($active_role === 'ADMIN') ? 'Dashboard Admin' : 'Dashboard User';
    $page_subtitle = 'Selamat datang di Dashboard BPS Kabupaten Solok Selatan.';
    include __DIR__ . '/partials/topbar.php';
    ?>

    <!-- =================================================
         TOTAL CARD / ADMIN OVERVIEW CARDS
    ================================================== -->

    <div class="cards">

        <a href="layanan.php" class="card cat-overview-card active" title="Lihat Kelola Layanan">
            <div class="cat-card-header">
                <svg class="cat-icon-svg" style="color: #0066b3;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="14" x="2" y="3" rx="2"/>
                    <line x1="8" x2="16" y1="21" y2="21"/>
                    <line x1="12" x2="12" y1="17" y2="21"/>
                </svg>
                <span class="cat-count-badge"><?= (int) $total_layanan; ?> item</span>
            </div>
            <div class="cat-overview-title">Total Layanan</div>
            <div class="cat-overview-subtitle">Layanan portal aktif terdaftar</div>
        </a>

        <a href="kategori.php" class="card cat-overview-card" title="Lihat Kategori Layanan">
            <div class="cat-card-header">
                <svg class="cat-icon-svg" style="color: #16a34a;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2"/>
                    <path d="M3 12h18"/>
                    <path d="M12 3v18"/>
                </svg>
                <span class="cat-count-badge"><?= (int) $total_kategori; ?> item</span>
            </div>
            <div class="cat-overview-title">Kategori Layanan</div>
            <div class="cat-overview-subtitle">Total Kategori bidang layanan</div>
        </a>

    </div>


    <!-- =================================================
         JUDUL KATEGORI
    ================================================== -->

    <div class="section-heading">


        <div>

            <h2>
                Layanan Berdasarkan Kategori
            </h2>

            <p>
                Jelajahi layanan BPS Kabupaten Solok Selatan berdasarkan bidangnya.
            </p>

        </div>


    </div>


    <?php if ($total_layanan === 0): ?>

        <div style="background: white; border-radius: 16px; border: 1px dashed #cbd5e1; padding: 50px 24px; text-align: center; margin-top: 20px;">
            <div style="width: 64px; height: 64px; background: #e6f0fa; color: #0066b3; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <?= render_icon('folder', '', 32); ?>
            </div>
            <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">
                Belum Ada Layanan
            </h3>
            <p style="color: #64748b; font-size: 14px; max-width: 440px; margin: 0 auto 24px; line-height: 1.5;">
                <?= ($active_role === 'USER') ? 'Anda belum menambahkan layanan apapun. Mulai tambahkan dan kelola link layanan Anda sendiri.' : 'Belum ada data layanan di sistem.'; ?>
            </p>
            <a href="tambah_layanan.php" style="display: inline-flex; align-items: center; gap: 8px; background: #0066b3; color: white; padding: 11px 22px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; box-shadow: 0 2px 6px rgba(0, 102, 179, 0.25);">
                <?= render_icon('plus', '', 16); ?> Tambah Layanan
            </a>
        </div>

    <?php else: ?>

    <!-- =================================================
         CAROUSEL KATEGORI LAYANAN (DESAIN REFERENSI)
    ================================================== -->
    <div class="cat-carousel-container">
        <button type="button" class="cat-arrow-btn prev" id="catPrevBtn" aria-label="Kategori Sebelumnya">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>

        <div class="cat-carousel-track" id="catCarouselTrack">
            <?php foreach ($data_kategori as $index => $kategori): ?>
                <?php
                $id = (int) $kategori['id_kategori'];
                $nama = $kategori['nama_kategori'];
                $items = $layanan_per_kategori[$id] ?? [];
                $theme = get_category_theme($nama, $index);
                $deskripsi = $deskripsi_kategori[$nama] ?? 'Kumpulan layanan statistik dan informasi BPS berdasarkan kategori ini.';

                // Format teks fokus
                $fokus_text = (stripos($deskripsi, 'fokus') === 0) ? $deskripsi : ('Fokus pada ' . lcfirst($deskripsi));
                ?>

                <article
                    class="cat-ref-card"
                    style="background: <?= $theme['bg']; ?>; border: 1px solid <?= $theme['border']; ?>;"
                    onclick="openCategoryModal(<?= $id; ?>)"
                >
                    <!-- Watermark lingkaran dekoratif di sudut kanan bawah -->
                    <div class="cat-watermark" style="color: <?= $theme['decor']; ?>;">
                        <div class="cat-arc-outer"></div>
                        <div class="cat-arc-inner"></div>
                    </div>

                    <div class="cat-card-top">
                        <!-- Kode ID kategori ( 001 ), ( 003 ), dll -->
                        <div class="cat-code-tag" style="color: <?= $theme['tag']; ?>;">
                            ( <?= sprintf('%03d', $id); ?> )
                        </div>

                        <!-- Ikon lingkaran -->
                        <div class="cat-icon-circle" style="background: <?= $theme['icon_bg']; ?>; color: <?= $theme['icon_color']; ?>;">
                            <?= $theme['svg']; ?>
                        </div>

                        <!-- Judul kategori -->
                        <h3 class="cat-title" style="color: <?= $theme['title']; ?>;">
                            <?= htmlspecialchars($nama); ?>
                        </h3>

                        <!-- Deskripsi fokus -->
                        <p class="cat-desc">
                            <?= htmlspecialchars($fokus_text); ?>
                        </p>
                    </div>

                    <div class="cat-card-bottom">
                        <button
                            type="button"
                            class="cat-pill-btn"
                            style="background: <?= $theme['pill_bg']; ?>; color: <?= $theme['pill_color']; ?>;"
                            onclick="event.stopPropagation(); openCategoryModal(<?= $id; ?>);"
                        >
                            <span><?= count($items); ?> Layanan</span>
                            <span class="cat-arrow">→</span>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <button type="button" class="cat-arrow-btn next" id="catNextBtn" aria-label="Kategori Selanjutnya">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>
    </div>

    <!-- Indicator Dots -->
    <div class="cat-dots-container" id="catDotsContainer"></div>

    <?php endif; // Selesai if ($total_layanan === 0) ?>

    <!-- =====================================================
         TABLE DAFTAR LAYANAN
    ===================================================== -->
    <div class="table-card" style="background: white; border-radius: 12px; padding: 24px; margin-top: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">

        <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <h2 style="font-size: 18px; font-weight: 700; color: #1e293b; margin: 0;">Daftar Layanan</h2>

            <div class="filter-container" style="display: flex; align-items: center; gap: 10px;">
                <label for="kategori" style="font-size: 14px; color: #64748b; font-weight: 600;">
                    Filter Kategori:
                </label>

                <select
                    id="kategori"
                    class="filter-select"
                    style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; background: white;"
                    onchange="if (this.value) { window.location.href = 'index.php?kategori=' + this.value; } else { window.location.href = 'index.php'; }"
                >
                    <option value="">Semua Kategori</option>
                    <?php foreach ($data_kategori as $kat): ?>
                        <option
                            value="<?= $kat['id_kategori']; ?>"
                            <?= ($filter_kategori == $kat['id_kategori']) ? 'selected' : ''; ?>
                        >
                            <?= htmlspecialchars($kat['nama_kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($filter_kategori != ''): ?>
                    <a href="index.php" class="reset-filter" style="font-size: 13px; color: #0066b3; text-decoration: none; padding: 6px 10px; border-radius: 6px; background: #e0f2fe;">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-wrapper" style="overflow-x: auto; width: 100%;">
            <table style="width: 100%; border-collapse: collapse; min-width: 650px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px 14px; text-align: left; font-size: 14px; font-weight: 700; color: #1e293b;">No</th>
                        <th style="padding: 12px 14px; text-align: left; font-size: 14px; font-weight: 700; color: #1e293b;">Nama Layanan</th>
                        <th style="padding: 12px 14px; text-align: left; font-size: 14px; font-weight: 700; color: #1e293b;">Kategori</th>
                        <th style="padding: 12px 14px; text-align: left; font-size: 14px; font-weight: 700; color: #1e293b;">Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $shown = 0;
                    foreach ($layanan_terbaru as $item):
                        if ($filter_kategori != '' && (string)$item['id_kategori'] !== (string)$filter_kategori) {
                            continue;
                        }
                        $shown++;
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 14px; font-size: 14px; color: #64748b;"><?= $no++; ?></td>
                        <td style="padding: 12px 14px; font-size: 14px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($item['nama_layanan']); ?></td>
                        <td style="padding: 12px 14px; font-size: 14px; color: #64748b;"><?= htmlspecialchars($item['nama_kategori'] ?? '-'); ?></td>
                        <td style="padding: 12px 14px; font-size: 14px;">
                            <a href="<?= htmlspecialchars($item['url']); ?>" target="_blank" rel="noopener" style="color: #0066b3; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; font-weight: 500;">
                                <span>Buka</span> 🔗
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($shown === 0): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 24px; color: #64748b; font-size: 14px;">
                            Tidak ada layanan yang sesuai dengan filter.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>


</main>


<!-- =====================================================
     MODAL SEMUA LAYANAN (KHUSUS ADMIN)
===================================================== -->

<?php if ($active_role === 'ADMIN'): ?>
<?php foreach ($data_kategori as $kategori): ?>


    <?php

    $id =
        (int) $kategori['id_kategori'];

    $nama =
        $kategori['nama_kategori'];

    $items =
        $layanan_per_kategori[$id]
        ?? [];

    $deskripsi =
        $deskripsi_kategori[$nama]
        ??
        'Kumpulan layanan statistik dan informasi BPS berdasarkan kategori ini.';

    ?>


    <div
        class="modal-overlay"
        id="categoryModal<?= $id; ?>"
        onclick="
            closeCategoryModal(
                event,
                <?= $id; ?>
            )
        "
    >


        <div
            class="modal"
            role="dialog"
            aria-modal="true"
            onclick="event.stopPropagation()"
        >


            <div class="modal-header">


                <div class="modal-title-row">


                    <span class="modal-title-dot">
                    </span>


                    <div class="modal-title">

                        <?= htmlspecialchars($nama); ?>

                    </div>


                </div>


                <div class="modal-focus">

                    Fokus:
                    <?= htmlspecialchars($deskripsi); ?>

                </div>


                <button
                    type="button"
                    class="modal-close"
                    onclick="
                        closeCategoryModal(
                            null,
                            <?= $id; ?>
                        )
                    "
                    aria-label="Tutup"
                >
                    ×
                </button>


            </div>


            <div class="modal-list">


                <?php if (!$items): ?>


                    <div class="modal-empty">

                        Belum ada layanan pada kategori ini.

                    </div>


                <?php else: ?>


                    <?php foreach ($items as $item): ?>


                        <div class="modal-item">


                            <span class="service-icon">
                                🌐
                            </span>


                            <div class="modal-item-main">


                                <div class="modal-item-name">

                                    <?= htmlspecialchars(
                                        $item['nama_layanan']
                                    ); ?>


                                    <span
                                        class="modal-item-category"
                                    >

                                        <?= htmlspecialchars(
                                            $nama
                                        ); ?>

                                    </span>


                                </div>


                            </div>


                            <?php if (
                                !empty(
                                    trim(
                                        $item['url']
                                    )
                                )
                            ): ?>


                                <a
                                    class="modal-open"
                                    href="<?= htmlspecialchars(
                                        trim($item['url']),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >

                                    Buka

                                    <span class="modal-arrow">
                                        →
                                    </span>

                                </a>


                            <?php else: ?>


                                <span class="modal-open">
                                    URL belum tersedia
                                </span>


                            <?php endif; ?>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


            <div class="modal-footer">

                <?= count($items); ?>

                layanan tersedia pada kategori

                <?= htmlspecialchars($nama); ?>.

            </div>


        </div>


    </div>


<?php endforeach; ?>
<?php endif; ?>


<script>

/* =====================================================
   KLIK MENU
===================================================== */

document
    .querySelectorAll('.menu a')
    .forEach(function(link) {

        link.addEventListener(
            'click',
            function() {

                if (window.innerWidth <= 768) {

                    closeSidebar();

                }

            }
        );

    });


/* =====================================================
   OVERLAY HP
===================================================== */

const sidebarOverlay =
    document.getElementById(
        'sidebarOverlay'
    );


if (sidebarOverlay) {

    sidebarOverlay.addEventListener(
        'click',
        function() {

            closeSidebar();

        }
    );

}


/* =====================================================
   ADMIN DROPDOWN
===================================================== */

function toggleAdminMenu() {

    const menu =
        document.getElementById(
            'adminMenu'
        );


    if (!menu) {

        return;

    }


    menu.classList.toggle(
        'show'
    );

}


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
            dropdown &&
            menu &&
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
   MODAL KATEGORI
===================================================== */

function openCategoryModal(id) {

    const modal =
        document.getElementById(
            'categoryModal' + id
        );


    if (modal) {

        modal.classList.add(
            'show'
        );


        document.body.style.overflow =
            'hidden';

    }

}


/* =====================================================
   TUTUP MODAL
===================================================== */

function closeCategoryModal(
    event,
    id
) {

    if (
        event &&
        event.target !==
        event.currentTarget
    ) {

        return;

    }


    const modal =
        document.getElementById(
            'categoryModal' + id
        );


    if (modal) {

        modal.classList.remove(
            'show'
        );


        document.body.style.overflow =
            '';

    }

}


/* =====================================================
   ESC UNTUK MODAL DAN SIDEBAR
===================================================== */

document.addEventListener(
    'keydown',
    function(event) {

        if (
            event.key === 'Escape'
        ) {

            document
                .querySelectorAll(
                    '.modal-overlay.show'
                )
                .forEach(
                    function(modal) {

                        modal.classList.remove(
                            'show'
                        );

                    }
                );


            closeSidebar();


            document.body.style.overflow =
                '';

        }
    }
);

/* =====================================================
   CAROUSEL KATEGORI NAVIGATION & DOTS
===================================================== */
(function initCategoryCarousel() {
    function setupCarousel() {
        const track = document.getElementById('catCarouselTrack');
        const prevBtn = document.getElementById('catPrevBtn');
        const nextBtn = document.getElementById('catNextBtn');
        const dotsContainer = document.getElementById('catDotsContainer');

        if (!track) return;

        const cards = track.querySelectorAll('.cat-ref-card');
        if (!cards.length) return;

        // Generate pagination dots
        if (dotsContainer) {
            dotsContainer.innerHTML = '';
            cards.forEach(function(card, idx) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'cat-dot' + (idx === 0 ? ' active' : '');
                dot.setAttribute('aria-label', 'Lihat kategori ' + (idx + 1));
                dot.addEventListener('click', function() {
                    const scrollTarget = card.offsetLeft - track.offsetLeft;
                    track.scrollTo({ left: scrollTarget, behavior: 'smooth' });
                });
                dotsContainer.appendChild(dot);
            });
        }

        const dots = dotsContainer ? dotsContainer.querySelectorAll('.cat-dot') : [];

        // Scroll Next & Prev
        function getScrollStep() {
            if (cards.length > 0) {
                return cards[0].offsetWidth + 22;
            }
            return 320;
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                track.scrollBy({ left: -getScrollStep(), behavior: 'smooth' });
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                track.scrollBy({ left: getScrollStep(), behavior: 'smooth' });
            });
        }

        // Active dot updater on scroll
        let scrollTimer = null;
        track.addEventListener('scroll', function() {
            if (scrollTimer) clearTimeout(scrollTimer);
            scrollTimer = setTimeout(function() {
                const scrollLeft = track.scrollLeft;
                let closestIdx = 0;
                let minDiff = Infinity;

                cards.forEach(function(card, idx) {
                    const cardPos = card.offsetLeft - track.offsetLeft;
                    const diff = Math.abs(cardPos - scrollLeft);
                    if (diff < minDiff) {
                        minDiff = diff;
                        closestIdx = idx;
                    }
                });

                dots.forEach(function(dot, idx) {
                    if (idx === closestIdx) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
            }, 50);
        }, { passive: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupCarousel);
    } else {
        setupCarousel();
    }
})();

</script>


</body>

</html>