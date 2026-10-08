<?php

require_once __DIR__ . '/config.php';

/* =====================================================
   AUTHENTICATION & ROLE CHECK
===================================================== */

// 1. Cek sesi login dasar
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Cek otorisasi peran (Role-Based Access Control)
$currentRole = $_SESSION['admin_role'] ?? '';
if ($currentRole !== 'ADMIN IT' && $currentRole !== 'ADMIN DASHBOARD') {
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
    session_destroy();
    header('Location: login.php');
    exit;
}

/* =====================================================
   HELPER FUNCTIONS
===================================================== */

function auth_id(): ?int
{
    return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
}

function auth_username(): string
{
    return (string) ($_SESSION['admin_username'] ?? 'Admin');
}

function auth_role(): string
{
    return (string) ($_SESSION['admin_role'] ?? '');
}

function auth_is_it(): bool
{
    return auth_role() === 'ADMIN IT';
}

function auth_is_dashboard(): bool
{
    return auth_role() === 'ADMIN DASHBOARD' || auth_role() === 'ADMIN IT';
}

function auth_require_it(): void
{
    if (!auth_is_it()) {
        header('Location: index.php');
        exit;
    }
}