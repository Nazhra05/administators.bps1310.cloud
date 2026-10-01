<?php

require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');


// =====================================================
// CORS
// =====================================================

$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');


// =====================================================
// HANDLE OPTIONS / CORS PREFLIGHT
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


// =====================================================
// CEK API KEY
// =====================================================

$clientApiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if ($clientApiKey === '') {

    http_response_code(401);

    echo json_encode([
        'status' => false,
        'message' => 'API key wajib dikirim',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


if (!defined('API_KEY')) {

    http_response_code(500);

    echo json_encode([
        'status' => false,
        'message' => 'API key server belum dikonfigurasi',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


if (!hash_equals(API_KEY, $clientApiKey)) {

    http_response_code(401);

    echo json_encode([
        'status' => false,
        'message' => 'API key tidak valid',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =====================================================
// HANYA GET
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    echo json_encode([
        'status' => false,
        'message' => 'Method tidak diperbolehkan',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =====================================================
// FUNGSI NORMALISASI URL
// =====================================================

function normalizeUrl($url)
{
    $url = trim((string) $url);

    // Kalau URL kosong, tetap kosong
    if ($url === '') {
        return '';
    }

    /*
     * Mengubah URL Markdown:
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


// =====================================================
// DESKRIPSI KATEGORI
// =====================================================

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


// =====================================================
// URUTAN 7 KATEGORI
// =====================================================

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

    // =================================================
    // AMBIL DATA LAYANAN
    // =================================================

    $sql = "
        SELECT
            l.id_layanan,
            l.id_kategori,
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

    $layanan = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // =================================================
    // SIAPKAN 7 KATEGORI
    // =================================================

    $kategoriData = [];

    foreach ($urutanKategori as $namaKategori) {

        $kategoriData[$namaKategori] = [
            'title' => $namaKategori,
            'category' => $namaKategori,
            'description' => $deskripsiKategori[$namaKategori] ?? '',
            'links' => []
        ];
    }


    // =================================================
    // MASUKKAN LAYANAN KE KATEGORI
    // =================================================

    foreach ($layanan as $item) {

        $namaKategori = $item['nama_kategori'];

        // Abaikan kategori yang tidak termasuk 7 kategori utama
        if (!isset($kategoriData[$namaKategori])) {
            continue;
        }


        // ---------------------------------------------
        // NORMALISASI URL
        // ---------------------------------------------

        $href = normalizeUrl($item['url'] ?? '');


        // ---------------------------------------------
        // MASUKKAN DATA
        // ---------------------------------------------

        $kategoriData[$namaKategori]['links'][] = [
            'label' => $item['nama_layanan'],
            'href' => $href,
            'logo' => $item['logo'],
            'id_layanan' => (int) $item['id_layanan']
        ];
    }


    // =================================================
    // UBAH ASSOCIATIVE ARRAY MENJADI ARRAY BIASA
    // =================================================

    $data = array_values($kategoriData);


    // =================================================
    // RESPONSE
    // =================================================

    echo json_encode([
        'status' => true,
        'message' => 'Data website berhasil diambil',
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'status' => false,
        'message' => 'Terjadi kesalahan pada server',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);

}