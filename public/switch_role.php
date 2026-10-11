<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Khusus akun ADMIN asli yang boleh menggunakan switch role
if (!is_admin_user()) {
    header('Location: index.php');
    exit;
}

$to = strtolower(trim($_GET['to'] ?? ''));
$targetLocation = 'index.php';

if ($to === 'user') {
    toggle_active_role('USER');
    $targetLocation = 'index.php';
} elseif ($to === 'admin') {
    toggle_active_role('ADMIN');
    $targetLocation = 'index.php';
} else {
    // Toggle otomatis antara ADMIN dan USER
    $current = auth_active_role();
    if ($current === 'ADMIN') {
        toggle_active_role('USER');
        $targetLocation = 'index.php';
    } else {
        toggle_active_role('ADMIN');
        $targetLocation = 'index.php';
    }
}

header("Location: $targetLocation");
exit;
