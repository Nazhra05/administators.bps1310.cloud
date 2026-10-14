<?php

require_once __DIR__ . '/../config/config.php';

$logoutId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($logoutId && isset($_SESSION['accounts'][$logoutId])) {
    unset($_SESSION['accounts'][$logoutId]);

    // Jika masih ada akun lain yang tersimpan di sesi
    if (!empty($_SESSION['accounts'])) {
        $nextAccount = reset($_SESSION['accounts']);
        $_SESSION['active_account_id'] = $nextAccount['id'];
        $_SESSION['admin_id'] = $nextAccount['id'];
        $_SESSION['admin_username'] = $nextAccount['username'];
        $_SESSION['admin_role'] = $nextAccount['raw_role'];
        $_SESSION['admin_email'] = $nextAccount['email'];
        $_SESSION['active_role'] = $nextAccount['role'];

        header("Location: index.php");
        exit;
    }
}

// Full logout
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Bersihkan cookie portal SSO
clear_portal_session_cookies();

session_destroy();

header("Location: login.php");
exit;