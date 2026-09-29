<?php

/* =====================================================
   DATABASE
===================================================== */

define('DB_HOST', '127.0.0.1');

define('DB_USER', 'admin-bps-solsel');

define('DB_PASS', 'Dk0rKQNy1OYl3Fz32Oqs');

define('DB_NAME', 'bps-solsel');


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
| Hosting:
| https://administators.bps1310.cloud/api/layanan.php
|
*/

define(
    'API_URL',
    'https://administators.bps1310.cloud/api/layanan.php'
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


/* =====================================================
   GMAIL SMTP
===================================================== */

define('SMTP_HOST', 'smtp.gmail.com');

define('SMTP_PORT', 587);

define('SMTP_USERNAME', 'nadivasalsabilla0705@gmail.com');

define('SMTP_PASSWORD', 'hxfl nnzl xwrw gvzw');

define('SMTP_FROM_EMAIL', 'nadivasalsabilla0705@gmail.com');

define('SMTP_FROM_NAME', 'BPS Kabupaten Solok Selatan');