<?php

/* =====================================================
   DATABASE
===================================================== */

define('DB_HOST', '127.0.0.1');

define('DB_USER', 'root');

define('DB_PASS', '');

define('DB_NAME', 'bps_solsel');


/* =====================================================
   API
===================================================== */

define(
    'API_KEY',
    'BPS_SOLSEL_API_2026_GANTI_DENGAN_KEY_RAHASIA'
);


/*
|--------------------------------------------------------------------------
| URL API
|--------------------------------------------------------------------------
|
| Lokal:
| http://localhost/dashboard_bps/public/api/layanan.php
|
| Nanti saat hosting tinggal ubah nilai ini menjadi URL hosting.
|
*/

define(
    'API_URL',
    'http://localhost/dashboard_bps/public/api/layanan.php'
);


/* =====================================================
   KONEKSI DATABASE
===================================================== */

try {

    $conn = new PDO(
        "mysql:host=" . DB_HOST .
        ";dbname=" . DB_NAME .
        ";charset=utf8mb4",

        DB_USER,
        DB_PASS
    );

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    die(
        "Koneksi database gagal: " .
        $e->getMessage()
    );

}


/* =====================================================
   SESSION
===================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}