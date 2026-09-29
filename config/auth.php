<?php

require_once __DIR__ . '/config.php';

/* =====================================================
   AUTHENTICATION CHECK
===================================================== */

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

function auth_id(): ?int
{
    return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
}

function auth_username(): string
{
    return (string) ($_SESSION['admin_username'] ?? 'Admin');
}