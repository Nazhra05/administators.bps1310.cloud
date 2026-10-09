<?php
declare(strict_types=1);

$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$basePath = rtrim((string)env('APP_BASE_PATH', ''), '/');
if (!empty($basePath) && str_starts_with($currentUri, $basePath)) {
    $currentUri = substr($currentUri, strlen($basePath)) ?: '/';
}

$userRole = \App\Support\Session::get('admin_role', '');
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= e(asset('assets/logo_bps.jpg')) ?>" alt="Logo BPS" class="sidebar-logo">
        <span class="sidebar-title">Dashboard Admin</span>
    </div>

    <ul class="menu">
        <li>
            <a href="<?= e(url('/')) ?>" class="<?= ($currentUri === '/' || $currentUri === '/dashboard') ? 'active' : '' ?>">
                <span class="menu-icon">📊</span>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?= e(url('/layanan')) ?>" class="<?= str_starts_with($currentUri, '/layanan') ? 'active' : '' ?>">
                <span class="menu-icon">🛠️</span>
                <span>Kelola Layanan</span>
            </a>
        </li>
        <li>
            <a href="<?= e(url('/kategori')) ?>" class="<?= str_starts_with($currentUri, '/kategori') ? 'active' : '' ?>">
                <span class="menu-icon">📁</span>
                <span>Kategori</span>
            </a>
        </li>

        <?php if ($userRole === 'ADMIN IT'): ?>
            <li>
                <a href="<?= e(url('/admin/kelola')) ?>" class="<?= str_starts_with($currentUri, '/admin/kelola') ? 'active' : '' ?>">
                    <span class="menu-icon">👥</span>
                    <span>Kelola Admin</span>
                </a>
            </li>
            <li>
                <a href="<?= e(url('/admin/user')) ?>" class="<?= str_starts_with($currentUri, '/admin/user') ? 'active' : '' ?>">
                    <span class="menu-icon">👤</span>
                    <span>Kelola User</span>
                </a>
            </li>
            <li>
                <a href="<?= e(url('/admin/audit')) ?>" class="<?= str_starts_with($currentUri, '/admin/audit') ? 'active' : '' ?>">
                    <span class="menu-icon">📜</span>
                    <span>Riwayat Audit</span>
                </a>
            </li>
        <?php endif; ?>

        <li>
            <a href="<?= e(url('/pengaturan')) ?>" class="<?= str_starts_with($currentUri, '/pengaturan') ? 'active' : '' ?>">
                <span class="menu-icon">⚙️</span>
                <span>Pengaturan</span>
            </a>
        </li>
    </ul>
</aside>
