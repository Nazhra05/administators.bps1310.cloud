<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';


/* =====================================================
   FUNGSI RESPONSE
===================================================== */

function responseJson($status, $message, $data = null, $httpCode = 200)
{
    http_response_code($httpCode);

    echo json_encode([
        'status'  => $status,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}


/* =====================================================
   FUNGSI NORMALISASI URL
===================================================== */

function normalizeUrl($url)
{
    $url = trim((string) $url);

    if ($url === '') {
        return '';
    }

    if (
        preg_match(
            '/^\[([^\]]+)\]\((https?:\/\/[^)]+)\)$/i',
            $url,
            $matches
        )
    ) {
        return trim($matches[2]);
    }

    return $url;
}


/* =====================================================
   FUNGSI VALIDASI KATEGORI
===================================================== */

function kategoriExists($conn, $id_kategori)
{
    $stmt = $conn->prepare("
        SELECT id_kategori
        FROM kategori
        WHERE id_kategori = ?
        LIMIT 1
    ");

    $stmt->execute([$id_kategori]);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}


/* =====================================================
   FUNGSI CEK API KEY
===================================================== */

function checkApiKey()
{
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

    if ($apiKey === '') {

        responseJson(
            false,
            'API key wajib dikirim',
            null,
            401
        );
    }

    if (!defined('API_KEY')) {

        responseJson(
            false,
            'API key server belum dikonfigurasi',
            null,
            500
        );
    }

    if (!hash_equals(API_KEY, $apiKey)) {

        responseJson(
            false,
            'API key tidak valid',
            null,
            401
        );
    }
}


/* =====================================================
   METHOD REQUEST
===================================================== */

$method = $_SERVER['REQUEST_METHOD'];


/* =====================================================
   SEMUA REQUEST WAJIB API KEY
===================================================== */

checkApiKey();


/* =====================================================
   GET - AMBIL DATA
===================================================== */

if ($method === 'GET') {

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    try {

        /* ---------------------------------------------
           GET SATU DATA
        --------------------------------------------- */

        if ($id > 0) {

            $stmt = $conn->prepare("
                SELECT
                    l.id_layanan,
                    l.id_kategori,
                    l.nama_layanan,
                    l.deskripsi_layanan,
                    l.url,
                    l.logo,
                    k.nama_kategori
                FROM layanan l
                LEFT JOIN kategori k
                    ON l.id_kategori = k.id_kategori
                WHERE l.id_layanan = ?
                LIMIT 1
            ");

            $stmt->execute([$id]);

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {

                responseJson(
                    false,
                    'Data layanan tidak ditemukan',
                    null,
                    404
                );
            }

            $data['url'] = normalizeUrl($data['url']);

            responseJson(
                true,
                'Data layanan berhasil diambil',
                $data
            );
        }


        /* ---------------------------------------------
           GET SEMUA DATA
        --------------------------------------------- */

        $stmt = $conn->query("
            SELECT
                l.id_layanan,
                l.id_kategori,
                l.nama_layanan,
                l.deskripsi_layanan,
                l.url,
                l.logo,
                k.nama_kategori
            FROM layanan l
            LEFT JOIN kategori k
                ON l.id_kategori = k.id_kategori
            ORDER BY l.id_layanan DESC
        ");

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);


        foreach ($data as &$item) {
            $item['url'] = normalizeUrl($item['url']);
        }

        unset($item);


        responseJson(
            true,
            'Data layanan berhasil diambil',
            $data
        );


    } catch (PDOException $e) {

        responseJson(
            false,
            'Terjadi kesalahan database',
            null,
            500
        );
    }
}


/* =====================================================
   POST - TAMBAH DATA
===================================================== */

if ($method === 'POST') {

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {

        responseJson(
            false,
            'Data JSON tidak valid',
            null,
            400
        );
    }


    $id_kategori       = $input['id_kategori'] ?? null;
    $nama_layanan      = trim($input['nama_layanan'] ?? '');
    $deskripsi_layanan = trim($input['deskripsi_layanan'] ?? '');
    $url               = normalizeUrl($input['url'] ?? '');
    $logo              = trim($input['logo'] ?? '');


    /* ---------------------------------------------
       VALIDASI ID KATEGORI
    --------------------------------------------- */

    if (
        $id_kategori === null ||
        filter_var($id_kategori, FILTER_VALIDATE_INT) === false ||
        (int) $id_kategori <= 0
    ) {

        responseJson(
            false,
            'id_kategori harus berupa angka yang valid',
            null,
            400
        );
    }

    $id_kategori = (int) $id_kategori;


    /* ---------------------------------------------
       VALIDASI NAMA
    --------------------------------------------- */

    if ($nama_layanan === '') {

        responseJson(
            false,
            'nama_layanan wajib diisi',
            null,
            400
        );
    }


    /* ---------------------------------------------
       VALIDASI DESKRIPSI
    --------------------------------------------- */

    if ($deskripsi_layanan === '') {

        responseJson(
            false,
            'deskripsi_layanan wajib diisi',
            null,
            400
        );
    }


    try {

        /* ---------------------------------------------
           CEK KATEGORI
        --------------------------------------------- */

        if (!kategoriExists($conn, $id_kategori)) {

            responseJson(
                false,
                'Kategori tidak ditemukan',
                null,
                404
            );
        }


        /* ---------------------------------------------
           INSERT
        --------------------------------------------- */

        $stmt = $conn->prepare("
            INSERT INTO layanan
            (
                id_kategori,
                nama_layanan,
                deskripsi_layanan,
                url,
                logo
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");

        $stmt->execute([
            $id_kategori,
            $nama_layanan,
            $deskripsi_layanan,
            $url,
            $logo
        ]);

        $idBaru = $conn->lastInsertId();


        responseJson(
            true,
            'Layanan berhasil ditambahkan',
            [
                'id_layanan' => (int) $idBaru
            ],
            201
        );


    } catch (PDOException $e) {

        responseJson(
            false,
            'Gagal menambahkan layanan',
            null,
            500
        );
    }
}


/* =====================================================
   PUT - EDIT DATA
===================================================== */

if ($method === 'PUT') {

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;


    if ($id <= 0) {

        responseJson(
            false,
            'ID layanan tidak valid',
            null,
            400
        );
    }


    $input = json_decode(
        file_get_contents('php://input'),
        true
    );


    if (!is_array($input)) {

        responseJson(
            false,
            'Data JSON tidak valid',
            null,
            400
        );
    }


    $id_kategori       = $input['id_kategori'] ?? null;
    $nama_layanan      = trim($input['nama_layanan'] ?? '');
    $deskripsi_layanan = trim($input['deskripsi_layanan'] ?? '');
    $url               = normalizeUrl($input['url'] ?? '');
    $logo              = trim($input['logo'] ?? '');


    /* ---------------------------------------------
       VALIDASI KATEGORI
    --------------------------------------------- */

    if (
        $id_kategori === null ||
        filter_var($id_kategori, FILTER_VALIDATE_INT) === false ||
        (int) $id_kategori <= 0
    ) {

        responseJson(
            false,
            'id_kategori harus berupa angka yang valid',
            null,
            400
        );
    }

    $id_kategori = (int) $id_kategori;


    /* ---------------------------------------------
       VALIDASI NAMA
    --------------------------------------------- */

    if ($nama_layanan === '') {

        responseJson(
            false,
            'nama_layanan wajib diisi',
            null,
            400
        );
    }


    /* ---------------------------------------------
       VALIDASI DESKRIPSI
    --------------------------------------------- */

    if ($deskripsi_layanan === '') {

        responseJson(
            false,
            'deskripsi_layanan wajib diisi',
            null,
            400
        );
    }


    try {

        /* ---------------------------------------------
           CEK KATEGORI
        --------------------------------------------- */

        if (!kategoriExists($conn, $id_kategori)) {

            responseJson(
                false,
                'Kategori tidak ditemukan',
                null,
                404
            );
        }


        /* ---------------------------------------------
           CEK LAYANAN
        --------------------------------------------- */

        $cek = $conn->prepare("
            SELECT id_layanan
            FROM layanan
            WHERE id_layanan = ?
            LIMIT 1
        ");

        $cek->execute([$id]);

        if (!$cek->fetch(PDO::FETCH_ASSOC)) {

            responseJson(
                false,
                'Data layanan tidak ditemukan',
                null,
                404
            );
        }


        /* ---------------------------------------------
           UPDATE
        --------------------------------------------- */

        $stmt = $conn->prepare("
            UPDATE layanan
            SET
                id_kategori = ?,
                nama_layanan = ?,
                deskripsi_layanan = ?,
                url = ?,
                logo = ?
            WHERE id_layanan = ?
        ");

        $stmt->execute([
            $id_kategori,
            $nama_layanan,
            $deskripsi_layanan,
            $url,
            $logo,
            $id
        ]);


        responseJson(
            true,
            'Layanan berhasil diperbarui'
        );


    } catch (PDOException $e) {

        responseJson(
            false,
            'Gagal memperbarui layanan',
            null,
            500
        );
    }
}


/* =====================================================
   DELETE - HAPUS DATA
===================================================== */

if ($method === 'DELETE') {

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;


    if ($id <= 0) {

        responseJson(
            false,
            'ID layanan tidak valid',
            null,
            400
        );
    }


    try {

        /* ---------------------------------------------
           CEK LAYANAN
        --------------------------------------------- */

        $cek = $conn->prepare("
            SELECT id_layanan
            FROM layanan
            WHERE id_layanan = ?
            LIMIT 1
        ");

        $cek->execute([$id]);

        if (!$cek->fetch(PDO::FETCH_ASSOC)) {

            responseJson(
                false,
                'Data layanan tidak ditemukan',
                null,
                404
            );
        }


        /* ---------------------------------------------
           DELETE
        --------------------------------------------- */

        $stmt = $conn->prepare("
            DELETE FROM layanan
            WHERE id_layanan = ?
        ");

        $stmt->execute([$id]);


        responseJson(
            true,
            'Layanan berhasil dihapus'
        );


    } catch (PDOException $e) {

        responseJson(
            false,
            'Gagal menghapus layanan',
            null,
            500
        );
    }
}


/* =====================================================
   METHOD TIDAK DIDUKUNG
===================================================== */

responseJson(
    false,
    'Method tidak didukung',
    null,
    405
);