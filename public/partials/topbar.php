<?php
/**
 * Partial: Top Navbar Bersama (Unified Static Header)
 * BPS Kabupaten Solok Selatan
 * 
 * Parameter yang didukung:
 * $page_title: Judul halaman (string)
 * $page_subtitle: Subjudul / deskripsi singkat halaman (string)
 */

require_once __DIR__ . '/icons.php';

$page_title = $page_title ?? 'Dashboard';
$page_subtitle = $page_subtitle ?? '';
?>

<header class="header topbar top-navbar" id="topNavbar">
    <div class="header-left top-navbar-left">
        <button 
            type="button" 
            class="sidebar-toggle-btn mobile-menu-button menu-toggle" 
            id="mobileMenuButton" 
            onclick="toggleSidebar()" 
            title="Toggle Sidebar"
            aria-label="Toggle menu navigasi"
        >
            <?= render_icon('menu', 'mobile-icon', 20); ?>
        </button>

        <div class="top-navbar-heading">
            <h1><?= $page_title; ?></h1>
            <?php if (!empty($page_subtitle)): ?>
                <p><?= $page_subtitle; ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="header-right top-navbar-right">
        <?php include __DIR__ . '/account_dropdown.php'; ?>
    </div>
</header>
