<?php
/**
 * Partial: Navigasi Sidebar Bersama (Unified Collapsible Sidebar)
 * BPS Kabupaten Solok Selatan
 * 
 * Parameter yang didukung:
 * $active_nav: 'dashboard' | 'repositori_portal' | 'layanan' | 'repositori_private' | 'kategori' | 'kelola_admin' | 'kelola_user' | 'pengaturan'
 */

require_once __DIR__ . '/icons.php';

if (!isset($active_role)) {
    $active_role = function_exists('auth_active_role') ? auth_active_role() : 'USER';
}
if (!isset($is_real_admin)) {
    $is_real_admin = function_exists('is_admin_user') ? is_admin_user() : false;
}
$active_nav = $active_nav ?? '';
?>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="logo">
        <span class="logo-full"><?= ($active_role === 'ADMIN') ? 'Dashboard Admin' : 'Dashboard User'; ?></span>
        <button 
            type="button" 
            class="sidebar-header-toggle" 
            id="sidebarHeaderToggle" 
            onclick="toggleSidebar()" 
            aria-label="Beralih navigasi sidebar" 
            title="Toggle Sidebar"
        >
            <span class="toggle-icon-collapse" aria-hidden="true"><?= render_icon('chevron-left', 'toggle-svg', 20); ?></span>
            <span class="toggle-icon-expand" aria-hidden="true"><?= render_icon('chevron-right', 'toggle-svg', 20); ?></span>
        </button>
    </div>

    <ul class="menu">
        <li>
            <a href="index.php" class="<?= ($active_nav === 'dashboard') ? 'active' : ''; ?>" title="Dashboard">
                <?= render_icon('layout-dashboard', 'ui-icon', 18); ?>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>

        <li>
            <a href="repositori_portal.php" class="<?= ($active_nav === 'repositori_portal' || $active_nav === 'portal' || $active_nav === 'layanan') ? 'active' : ''; ?>" title="Kelola Repositori Portal">
                <?= render_icon('globe', 'ui-icon', 18); ?>
                <span class="nav-text">Kelola Repositori Portal</span>
                <span class="sr-only">Kelola Layanan</span>
            </a>
        </li>

        <li>
            <a href="repositori_private.php" class="<?= ($active_nav === 'repositori_private' || $active_nav === 'private') ? 'active' : ''; ?>" title="Kelola Repositori Private">
                <?= render_icon('folder-lock', 'ui-icon', 18); ?>
                <span class="nav-text">Kelola Repositori Private</span>
            </a>
        </li>

        <li>
            <a href="kategori.php" class="<?= ($active_nav === 'kategori') ? 'active' : ''; ?>" title="Kategori">
                <?= render_icon('tags', 'ui-icon', 18); ?>
                <span class="nav-text">Kategori</span>
            </a>
        </li>

        <?php if ($active_role === 'ADMIN'): ?>
            <li>
                <a href="kelola_user.php" class="<?= ($active_nav === 'kelola_user') ? 'active' : ''; ?>" title="Kelola User">
                    <?= render_icon('users', 'ui-icon', 18); ?>
                    <span class="nav-text">Kelola User</span>
                </a>
            </li>

            <li>
                <a href="kelola_admin.php" class="<?= ($active_nav === 'kelola_admin') ? 'active' : ''; ?>" title="Kelola Admin">
                    <?= render_icon('shield-check', 'ui-icon', 18); ?>
                    <span class="nav-text">Kelola Admin</span>
                </a>
            </li>
        <?php endif; ?>

        <li>
            <a href="pengaturan.php" class="<?= ($active_nav === 'pengaturan') ? 'active' : ''; ?>" title="Pengaturan">
                <?= render_icon('settings', 'ui-icon', 18); ?>
                <span class="nav-text">Pengaturan</span>
            </a>
        </li>

        <?php if ($is_real_admin && $active_role !== 'ADMIN'): ?>
            <li style="margin-top: 20px;">
                <a href="switch_role.php?to=admin" title="Switch ke Admin" class="nav-switch-btn" style="display: flex; align-items: center; gap: 10px; background: rgba(255, 255, 255, 0.12); border-radius: 8px;">
                    <?= render_icon('refresh-cw', 'ui-icon', 16); ?>
                    <span class="nav-text">Switch ke Admin</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</aside>

<script>
function updateSidebarAria(isCollapsed) {
    const btn = document.getElementById('sidebarHeaderToggle');
    if (btn) {
        btn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        btn.setAttribute('title', isCollapsed ? 'Buka Sidebar' : 'Tutup Sidebar');
    }
}

function toggleSidebar() {
    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (isCollapsed) {
        document.body.classList.remove('sidebar-collapsed');
        try { localStorage.setItem('sidebar_collapsed', 'false'); } catch (e) {}
        updateSidebarAria(false);
        if (overlay && window.innerWidth < 768) {
            overlay.classList.add('show');
        }
    } else {
        document.body.classList.add('sidebar-collapsed');
        try { localStorage.setItem('sidebar_collapsed', 'true'); } catch (e) {}
        updateSidebarAria(true);
        if (overlay) {
            overlay.classList.remove('show');
        }
    }
}

function openSidebar() {
    toggleSidebar();
}

function closeSidebar() {
    document.body.classList.add('sidebar-collapsed');
    try { localStorage.setItem('sidebar_collapsed', 'true'); } catch (e) {}
    updateSidebarAria(true);
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) overlay.classList.remove('show');
}

// Pulihkan preferensi pengguna saat halaman dimuat
(function() {
    try {
        const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
        if (isCollapsed) {
            document.body.classList.add('sidebar-collapsed');
        }
        updateSidebarAria(isCollapsed);
    } catch (e) {}
})();
</script>
