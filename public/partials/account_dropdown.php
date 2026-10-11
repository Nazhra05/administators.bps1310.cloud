<?php
/**
 * Partial: User / Admin Account Dropdown
 */

if (!function_exists('auth_real_role')) {
    require_once __DIR__ . '/../../config/auth.php';
}

require_once __DIR__ . '/icons.php';

$currentId = (int) ($_SESSION['admin_id'] ?? 0);
$currentUsername = (string) ($_SESSION['admin_username'] ?? 'User');
$currentName = (string) ($_SESSION['admin_name'] ?? $currentUsername);
$currentEmail = (string) ($_SESSION['admin_email'] ?? '');

$isRealAdmin = function_exists('is_admin_user') ? is_admin_user() : (($_SESSION['admin_real_role'] ?? '') === 'ADMIN');
$activeRole = function_exists('auth_active_role') ? auth_active_role() : ($_SESSION['active_role'] ?? ($isRealAdmin ? 'ADMIN' : 'USER'));

$isActiveAdmin = ($activeRole === 'ADMIN');
$badgeColor = $isActiveAdmin ? '#0066b3' : '#059669';
$badgeBg = $isActiveAdmin ? '#e6f0fa' : '#dcfce7';
$initial = strtoupper(substr($currentUsername, 0, 1) ?: 'U');
?>

<div class="admin-dropdown account-switcher-dropdown">
    <!-- TRIGGER BUTTON -->
    <button
        type="button"
        class="admin-status"
        onclick="toggleAccountMenu(event)"
        aria-label="Menu akun"
    >
        <div class="admin-icon" style="background: <?= $badgeBg; ?>; color: <?= $badgeColor; ?>;">
            <?= htmlspecialchars($initial); ?>
        </div>

        <div class="admin-info">
            <span class="admin-label">
                Login sebagai
            </span>
            <span class="admin-name" style="color: <?= $badgeColor; ?>;">
                <?= htmlspecialchars($currentUsername); ?> (<?= $isActiveAdmin ? 'Admin' : 'User'; ?>)
            </span>
        </div>

        <span class="dropdown-arrow" style="color: <?= $badgeColor; ?>;">
            ▾
        </span>
    </button>

    <!-- DROPDOWN PANEL -->
    <div class="account-menu" id="accountMenu">
        <!-- HEADER AKUN AKTIF -->
        <div class="account-header-card">
            <div class="account-avatar-large" style="background: <?= $badgeBg; ?>; color: <?= $badgeColor; ?>;">
                <?= htmlspecialchars($initial); ?>
            </div>
            <div class="account-details">
                <div class="account-name-title">
                    <?= htmlspecialchars($currentName); ?>
                </div>
                <div class="account-email-sub">
                    <?= htmlspecialchars($currentEmail ?: $currentUsername); ?>
                </div>
                <div style="margin-top: 5px;">
                    <span class="account-role-pill" style="background: <?= $badgeBg; ?>; color: <?= $badgeColor; ?>;">
                        ● <?= $isActiveAdmin ? 'ADMINISTRATOR' : ($isRealAdmin ? 'USER (PREVIEW)' : 'USER'); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- FITUR SWITCH ROLE (KHUSUS ADMIN ASLI, BUKAN USER BIASA) -->
        <?php if ($isRealAdmin && !$isActiveAdmin): ?>
            <div class="account-menu-divider"></div>
            <a href="switch_role.php?to=admin" class="account-action-link" style="color: #059669; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <?= render_icon('refresh-cw', 'ui-icon', 16); ?>
                <span>Switch ke Admin</span>
            </a>
        <?php endif; ?>

        <!-- LOGOUT -->
        <div class="account-menu-divider"></div>
        <a href="logout.php" class="account-action-link" style="color: #dc2626; display: flex; align-items: center; gap: 8px;">
            <?= render_icon('log-out', 'ui-icon', 16); ?>
            <span>Keluar</span>
        </a>
    </div>
</div>

<style>
.admin-dropdown,
.account-switcher-dropdown {
    position: relative;
    display: inline-block;
    flex-shrink: 0;
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
    text-align: left;
    transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.admin-status:hover {
    background: #f8fbfe;
    border-color: #bee3f8;
    box-shadow: 0 4px 14px rgba(0, 102, 179, 0.12);
}

.admin-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: bold;
    flex-shrink: 0;
}

.admin-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    text-align: left;
}

.admin-label {
    font-size: 11px;
    color: #6b7280;
    line-height: 1.2;
}

.admin-name {
    font-size: 13.5px;
    font-weight: bold;
    color: #005a9c;
    line-height: 1.2;
}

.dropdown-arrow {
    color: #005a9c;
    font-size: 16px;
    margin-left: 3px;
    line-height: 1;
}

.account-menu {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    width: 310px;
    background: white;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0, 50, 100, 0.16);
    border: 1px solid #e1edf7;
    padding: 14px;
    z-index: 100000;
}

.account-menu.show {
    display: block;
}

.account-header-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 4px 12px 4px;
}

.account-avatar-large {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
    flex-shrink: 0;
}

.account-details {
    overflow: hidden;
}

.account-name-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.account-email-sub {
    font-size: 12px;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.account-role-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.account-section-title {
    font-size: 11px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 8px 4px 6px 4px;
}

.account-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 6px;
}

.account-switch-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 10px;
    text-decoration: none;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.account-switch-item:hover {
    background: #edf6fd;
    border-color: #bee3f8;
    transform: translateY(-1px);
}

.account-avatar-small {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
    flex-shrink: 0;
}

.account-switch-info {
    flex: 1;
    overflow: hidden;
}

.account-switch-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.account-role-mini {
    font-size: 9.5px;
    padding: 1px 6px;
    border-radius: 8px;
    font-weight: 600;
}

.account-switch-email {
    font-size: 11px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.account-switch-arrow {
    font-size: 14px;
    color: #94a3b8;
    font-weight: bold;
}

.account-menu-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 8px 0;
}

.account-action-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    color: #0066b3;
    transition: background 0.15s ease;
}

.account-action-link:hover {
    background: #f1f5f9;
}
</style>

<script>
function toggleAccountMenu(event) {
    if (event && event.stopPropagation) event.stopPropagation();
    const menu = document.getElementById('accountMenu') || document.getElementById('adminMenu');
    if (menu) menu.classList.toggle('show');
}
function toggleAdminMenu(event) {
    toggleAccountMenu(event);
}

document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.account-switcher-dropdown') || document.querySelector('.admin-dropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        const menu = document.getElementById('accountMenu') || document.getElementById('adminMenu');
        if (menu) menu.classList.remove('show');
    }
});
</script>
