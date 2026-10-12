<?php

require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');


/* =====================================================
   FUNGSI RESPONSE JSON
===================================================== */

function responseJson(
    $status,
    $message,
    $data = null,
    $httpCode = 200
) {

    http_response_code($httpCode);

    $isSuccess = ($status === true || $status === 'success');

    echo json_encode(
        [
            'status'  => $status,
            'success' => $isSuccess,
            'message' => $message,
            'data'    => $data
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* =====================================================
   CORS
===================================================== */

$envOrigins = array_filter(
    array_map(
        'trim',
        explode(
            ',',
            env('CORS_ALLOWED_ORIGINS', '')
        )
    )
);


$allowedOrigins = array_unique(
    array_merge(
        [
            'http://localhost:3000',
            'http://127.0.0.1:3000'
        ],
        $envOrigins
    )
);


$origin = $_SERVER['HTTP_ORIGIN'] ?? '';


if (
    $origin !== '' &&
    in_array(
        $origin,
        $allowedOrigins,
        true
    )
) {

    header(
        "Access-Control-Allow-Origin: {$origin}"
    );

    header('Vary: Origin');
}


/* =====================================================
   HEADER YANG DIIZINKAN
===================================================== */

header(
    'Access-Control-Allow-Methods: GET, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, X-API-KEY'
);


/* =====================================================
   PREFLIGHT REQUEST
===================================================== */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS'
) {

    http_response_code(204);

    exit;
}


/* =====================================================
   CEK METHOD
===================================================== */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET'
) {

    responseJson(
        false,
        'Method tidak diperbolehkan',
        null,
        405
    );
}


/* =====================================================
   CEK API KEY
===================================================== */

$clientApiKey =
    $_SERVER['HTTP_X_API_KEY'] ?? '';


if ($clientApiKey === '') {

    responseJson(
        false,
        'API key wajib dikirim',
        null,
        401
    );
}


/* =====================================================
   CEK API KEY SERVER
===================================================== */

if (!defined('API_KEY') || API_KEY === '') {

    responseJson(
        false,
        'API key server belum dikonfigurasi',
        null,
        500
    );
}


/* =====================================================
   VALIDASI API KEY
===================================================== */

if (
    !hash_equals(
        API_KEY,
        $clientApiKey
    )
) {

    responseJson(
        false,
        'API key tidak valid',
        null,
        401
    );
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


    $kategori =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /* =================================================
       FORMAT DATA
    ================================================= */

    foreach ($kategori as &$item) {

        $item['id_kategori'] =
            (int) $item['id_kategori'];

        $item['nama_kategori'] =
            $item['nama_kategori'] ?? '';

        $item['deskripsi'] =
            $item['deskripsi'] ?? '';
    }

    unset($item);


    /* =================================================
       RESPONSE BERHASIL
    ================================================= */

    responseJson(
        true,
        'Data kategori berhasil diambil',
        $kategori,
        200
    );


} catch (PDOException $e) {

    /* =================================================
       RESPONSE ERROR DATABASE
    ================================================= */

    responseJson(
        false,
        'Terjadi kesalahan pada server',
        null,
        500
    );
}