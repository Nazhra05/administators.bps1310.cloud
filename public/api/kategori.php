<?php

require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');


/* =====================================================
   CORS
===================================================== */

$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {

    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
}


/* =====================================================
   ALLOW HEADER
===================================================== */

header('Access-Control-Allow-Methods: GET, OPTIONS');

header(
    'Access-Control-Allow-Headers: Content-Type, X-API-KEY'
);


/* =====================================================
   PREFLIGHT REQUEST
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

    http_response_code(204);

    exit;
}


/* =====================================================
   CEK METHOD
===================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode([
        'status' => false,
        'message' => 'Method tidak diperbolehkan',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/* =====================================================
   CEK API KEY
===================================================== */

$clientApiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (
    !defined('API_KEY') ||
    $clientApiKey === '' ||
    !hash_equals(API_KEY, $clientApiKey)
) {

    http_response_code(401);

    echo json_encode([
        'status' => false,
        'message' => 'Akses API tidak diizinkan',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/* =====================================================
   AMBIL DATA KATEGORI
===================================================== */

try {

    $sql = "
        SELECT
            id_kategori,
            nama_kategori,
            deskripsi
        FROM kategori
        ORDER BY id_kategori ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);


    /* =================================================
       RESPONSE
    ================================================= */

    echo json_encode([
        'status' => true,
        'message' => 'Data kategori berhasil diambil',
        'data' => $kategori
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'status' => false,
        'message' => 'Terjadi kesalahan pada server',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}