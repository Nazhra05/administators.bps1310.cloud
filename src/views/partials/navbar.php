<?php
declare(strict_types=1);

$adminUsername = \App\Support\Session::get('admin_username', 'Admin');
$adminRole = \App\Support\Session::get('admin_role', 'Administrator');
?>
<button type="button" class="mobile-menu-button" id="mobileMenuBtn" aria-label="Toggle Navigation">
    ☰
</button>

<div class="header">
    <div>
        <h1><?= e($title ?? 'Dashboard') ?></h1>
        <?php if (!empty($subtitle)): ?>
            <p><?= e($subtitle) ?></p>
        <?php endif; ?>
    </div>

    <div class="admin-dropdown">
        <button type="button" class="admin-status" id="adminStatusBtn" aria-haspopup="true" aria-expanded="false" aria-label="Menu Pengguna Administrator">
            <div class="admin-icon">👤</div>
            <div class="admin-info">
                <span class="admin-label"><?= e($adminRole) ?></span>
                <span class="admin-name"><?= e($adminUsername) ?></span>
            </div>
            <span class="dropdown-arrow">▾</span>
        </button>

        <div class="admin-menu" id="adminMenu">
            <a href="<?= e(url('/pengaturan')) ?>" class="admin-menu-item">
                <span>⚙️</span>
                <span>Pengaturan Profil</span>
            </a>
            <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 4px 0;">
            <a href="<?= e(url('/logout')) ?>" class="admin-menu-item logout">
                <span>🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>
