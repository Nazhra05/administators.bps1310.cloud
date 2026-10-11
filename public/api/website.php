<?php

require_once __DIR__ . '/../../config/config.php';

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

    echo json_encode(
        [
            'status'  => $status,
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
            'http://127.0.0.1:3000',
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


header(
    'Access-Control-Allow-Methods: GET, OPTIONS'
);

header(
    'Access-Control-Allow-Headers: Content-Type, X-API-KEY'
);


/* =====================================================
   PREFLIGHT
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

if (
    !defined('API_KEY') ||
    API_KEY === ''
) {

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
   NORMALISASI URL
===================================================== */

function normalizeWebsiteUrl($url)
{
    $url = trim((string) $url);


    if ($url === '') {
        return '';
    }


    /*
     * Contoh:
     *
     * [https://contoh.com](https://contoh.com)
     *
     * menjadi:
     *
     * https://contoh.com
     */

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
   DESKRIPSI KATEGORI
===================================================== */

$deskripsiKategori = [

    'Distribusi' =>
        'Perdagangan, harga, transportasi, pariwisata, dan distribusi barang/jasa.',

    'Produksi' =>
        'Pertanian, industri, pertambangan, energi, konstruksi, peternakan, kehutanan, perikanan.',

    'Neraca' =>
        'PDRB, neraca wilayah, neraca produksi/pengeluaran, dan analisis ekonomi makro.',

    'Sosial' =>
        'Penduduk, ketenagakerjaan, kemiskinan, pendidikan, kesehatan, sosial, dan kesejahteraan.',

    'TI' =>
        'Sistem dan infrastruktur/alat BPS — bukan data statistik bidang tertentu, melainkan sistem nasional.',

    'Umum' =>
        'Administrasi, kepegawaian, pengadaan, regulasi, dan urusan internal.',

    'Diseminasi' =>
        'Kategori dengan isi paling banyak — tugasnya menyebarkan data ke pengguna.',
];


/* =====================================================
   URUTAN KATEGORI
===================================================== */

$urutanKategori = [
    'Distribusi',
    'Produksi',
    'Neraca',
    'Sosial',
    'TI',
    'Umum',
    'Diseminasi'
];


try {

    /* =================================================
       AMBIL DATA LAYANAN
    ================================================= */

    $sql = "
        SELECT
            l.id_layanan,
            l.id_kategori,
            l.akses,
            l.vpn,
            l.nama_layanan,
            l.url,
            l.logo,
            k.nama_kategori
        FROM layanan l
        LEFT JOIN kategori k
            ON l.id_kategori = k.id_kategori
        ORDER BY
            k.id_kategori ASC,
            l.id_layanan ASC
    ";


    $stmt = $conn->prepare($sql);

    $stmt->execute();


    $layanan =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /* =================================================
       SIAPKAN 7 KATEGORI
    ================================================= */

    $kategoriData = [];


    foreach (
        $urutanKategori
        as $namaKategori
    ) {

        $kategoriData[$namaKategori] = [

            'title' =>
                $namaKategori,

            'category' =>
                $namaKategori,

            'description' =>
                $deskripsiKategori[$namaKategori] ?? '',

            'links' =>
                []
        ];
    }


    /* =================================================
       MASUKKAN LAYANAN KE KATEGORI
    ================================================= */

    foreach (
        $layanan
        as $item
    ) {

        $namaKategori =
            $item['nama_kategori'] ?? '';


        /*
         * Abaikan kategori di luar
         * 7 kategori utama.
         */

        if (
            !isset(
                $kategoriData[$namaKategori]
            )
        ) {

            continue;
        }


        /* =============================================
           NORMALISASI URL
        ============================================= */

        $href =
            normalizeWebsiteUrl(
                $item['url'] ?? ''
            );


        /* =============================================
           TAMBAHKAN LINK
        ============================================= */

        $kategoriData[$namaKategori]['links'][] = [

            'label' =>
                $item['nama_layanan'] ?? '',

            'href' =>
                $href,

            'logo' =>
                $item['logo'] ?? '',

            'id_layanan' =>
                (int) (
                    $item['id_layanan'] ?? 0
                ),

            'akses' =>
                $item['akses'] ?? 'Public',

            'vpn' =>
                $item['vpn'] ?? 'No'
        ];
    }


    /* =================================================
       UBAH ASSOCIATIVE ARRAY KE ARRAY BIASA
    ================================================= */

    $data =
        array_values(
            $kategoriData
        );


    /* =================================================
       RESPONSE
    ================================================= */

    responseJson(
        true,
        'Data website berhasil diambil',
        $data
    );


} catch (PDOException $e) {

    responseJson(
        false,
        'Terjadi kesalahan pada server',
        null,
        500
    );
}