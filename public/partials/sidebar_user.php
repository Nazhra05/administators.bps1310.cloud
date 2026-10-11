<?php
/**
 * Partial: Sidebar Dashboard User (Proxy Kompatibilitas)
 * Seluruh navigasi sidebar bersama telah dipusatkan di public/partials/navbar.php
 */

$active_page = $active_page ?? 'dashboard';
$active_nav = ($active_page === 'portal') ? 'repositori_portal' : (($active_page === 'private') ? 'repositori_private' : $active_page);

include __DIR__ . '/navbar.php';
