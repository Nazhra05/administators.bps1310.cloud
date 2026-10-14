<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';


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
    'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'
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
   FUNGSI RESPONSE JSON
===================================================== */

function responseJson(
    $status,
    $message,
    $data = null,
    $httpCode = 200,
    $meta = null
) {

    http_response_code($httpCode);

    $isSuccess = ($status === true || $status === 'success');
    $payload = [
        'status'  => $status,
        'success' => $isSuccess,
        'message' => $message,
        'data'    => $data
    ];

    if ($meta !== null) {
        $payload['meta'] = $meta;
    }

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* =====================================================
   NORMALISASI URL
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
   CEK KATEGORI
===================================================== */

function kategoriExists(
    $conn,
    $id_kategori
) {

    $stmt = $conn->prepare("
        SELECT id_kategori
        FROM kategori
        WHERE id_kategori = ?
        LIMIT 1
    ");


    $stmt->execute([
        $id_kategori
    ]);


    return (bool) $stmt->fetch(
        PDO::FETCH_ASSOC
    );
}


/* =====================================================
   VALIDASI AKSES
===================================================== */

function validateAkses($akses)
{
    return in_array(
        $akses,
        [
            'Internal',
            'Public',
            'Private'
        ],
        true
    );
}


/* =====================================================
   VALIDASI VPN
===================================================== */

function validateVpn($vpn)
{
    return in_array(
        $vpn,
        [
            'Yes',
            'No'
        ],
        true
    );
}


/* =====================================================
   CEK API KEY
===================================================== */

function checkApiKey()
{

    $apiKey =
        $_SERVER['HTTP_X_API_KEY'] ?? '';


    if ($apiKey === '') {

        responseJson(
            false,
            'API key wajib dikirim',
            null,
            401
        );
    }


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


    if (
        !hash_equals(
            API_KEY,
            $apiKey
        )
    ) {

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

$method =
    $_SERVER['REQUEST_METHOD'] ?? '';


/* =====================================================
   SEMUA REQUEST WAJIB API KEY
===================================================== */

checkApiKey();


/* =====================================================
   GET
===================================================== */

if ($method === 'GET') {

    $id =
        isset($_GET['id'])
            ? (int) $_GET['id']
            : (isset($_GET['id_layanan']) ? (int) $_GET['id_layanan'] : 0);


    try {

        /* =============================================
           GET SATU DATA
        ============================================= */

        if ($id > 0) {

            $stmt = $conn->prepare("
                SELECT
                    l.id_layanan,
                    l.id_kategori,
                    l.created_by,
                    l.akses,
                    l.vpn,
                    l.nama_layanan,
                    l.deskripsi_layanan,
                    l.url,
                    l.logo,
                    k.nama_kategori
                FROM layanan l
                LEFT JOIN kategori k
                    ON l.id_kategori = k.id_kategori
                WHERE l.id_layanan = ?
                  AND l.akses IN ('Public', 'Internal')
                LIMIT 1
            ");


            $stmt->execute([
                $id
            ]);


            $data =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$data) {

                responseJson(
                    false,
                    'Data layanan tidak ditemukan',
                    null,
                    404
                );
            }


            $data['id_layanan'] =
                (int) $data['id_layanan'];

            $data['id_kategori'] =
                (int) $data['id_kategori'];

            $data['created_by'] =
                (int) ($data['created_by'] ?? 1);


            $data['url'] =
                normalizeUrl(
                    $data['url']
                );


            responseJson(
                true,
                'Data layanan berhasil diambil',
                $data
            );
        }


        /* =============================================
           GET SEMUA DATA (HANYA PUBLIC & INTERNAL)
        ============================================= */

        $filterKategori = isset($_GET['id_kategori']) ? (int) $_GET['id_kategori'] : 0;
        $createdByParam = isset($_GET['created_by']) ? (int) $_GET['created_by'] : 0;
        $pageParam = isset($_GET['page']) ? (int) $_GET['page'] : null;
        $limitParam = isset($_GET['limit']) ? (int) $_GET['limit'] : null;
        $meta = null;

        $whereClauses = [];
        $whereParams = [];

        if ($createdByParam > 0) {
            $whereClauses[] = "l.created_by = ?";
            $whereParams[] = $createdByParam;
        } else {
            $whereClauses[] = "l.akses IN ('Public', 'Internal')";
        }

        if ($filterKategori > 0) {
            $whereClauses[] = "l.id_kategori = ?";
            $whereParams[] = $filterKategori;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        if ($pageParam !== null && $pageParam > 0) {
            $page = $pageParam;
            $limit = ($limitParam !== null && $limitParam > 0) ? min(100, $limitParam) : 20;
            $offset = ($page - 1) * $limit;

            $countStmt = $conn->prepare("SELECT COUNT(*) FROM layanan l {$whereSql}");
            $countStmt->execute($whereParams);

            $total = (int) $countStmt->fetchColumn();
            $totalPages = $total > 0 ? (int) ceil($total / $limit) : 0;

            $meta = [
                'page'        => $page,
                'limit'       => $limit,
                'total'       => $total,
                'total_pages' => $totalPages
            ];

            $sql = "
                SELECT
                    l.id_layanan,
                    l.id_kategori,
                    l.created_by,
                    l.akses,
                    l.vpn,
                    l.nama_layanan,
                    l.deskripsi_layanan,
                    l.url,
                    l.logo,
                    k.nama_kategori
                FROM layanan l
                LEFT JOIN kategori k
                    ON l.id_kategori = k.id_kategori
                {$whereSql}
                ORDER BY l.id_layanan DESC
                LIMIT ? OFFSET ?
            ";
            $stmt = $conn->prepare($sql);
            $bindIdx = 1;
            foreach ($whereParams as $paramVal) {
                $stmt->bindValue($bindIdx++, $paramVal, PDO::PARAM_INT);
            }
            $stmt->bindValue($bindIdx++, $limit, PDO::PARAM_INT);
            $stmt->bindValue($bindIdx++, $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $sql = "
                SELECT
                    l.id_layanan,
                    l.id_kategori,
                    l.created_by,
                    l.akses,
                    l.vpn,
                    l.nama_layanan,
                    l.deskripsi_layanan,
                    l.url,
                    l.logo,
                    k.nama_kategori
                FROM layanan l
                LEFT JOIN kategori k
                    ON l.id_kategori = k.id_kategori
                {$whereSql}
                ORDER BY l.id_layanan DESC
            ";
            $stmt = $conn->prepare($sql);
            $stmt->execute($whereParams);
        }

        $data =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach ($data as &$item) {

            $item['id_layanan'] =
                (int) $item['id_layanan'];

            $item['id_kategori'] =
                (int) $item['id_kategori'];

            $item['created_by'] =
                (int) ($item['created_by'] ?? 1);


            $item['url'] =
                normalizeUrl(
                    $item['url']
                );


            $item['akses'] =
                $item['akses'] ?? 'Public';

            $item['vpn'] =
                $item['vpn'] ?? 'No';

            $item['nama_layanan'] =
                $item['nama_layanan'] ?? '';

            $item['deskripsi_layanan'] =
                $item['deskripsi_layanan'] ?? '';

            $item['logo'] =
                $item['logo'] ?? '';

            $item['nama_kategori'] =
                $item['nama_kategori'] ?? '';
        }


        unset($item);


        responseJson(
            true,
            'Data layanan berhasil diambil',
            $data,
            200,
            $meta
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
   POST
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


    $id_kategori =
        $input['id_kategori'] ?? null;

    $akses =
        trim(
            $input['akses'] ?? 'Public'
        );

    $vpn =
        trim(
            $input['vpn'] ?? 'No'
        );

    $nama_layanan =
        trim(
            $input['nama_layanan'] ?? ''
        );

    $deskripsi_layanan =
        trim(
            $input['deskripsi_layanan'] ?? ''
        );

    $url =
        normalizeUrl(
            $input['url'] ?? ''
        );

    $logo =
        trim(
            $input['logo'] ?? ''
        );


    /* =============================================
       VALIDASI KATEGORI
    ============================================= */

    if (
        $id_kategori === null ||
        filter_var(
            $id_kategori,
            FILTER_VALIDATE_INT
        ) === false ||
        (int) $id_kategori <= 0
    ) {

        responseJson(
            false,
            'id_kategori harus berupa angka yang valid',
            null,
            400
        );
    }


    $id_kategori =
        (int) $id_kategori;


    /* =============================================
       VALIDASI AKSES
    ============================================= */

    if (!validateAkses($akses)) {

        responseJson(
            false,
            'akses harus berupa Internal atau Public',
            null,
            400
        );
    }


    /* =============================================
       VALIDASI VPN
    ============================================= */

    if (!validateVpn($vpn)) {

        responseJson(
            false,
            'vpn harus berupa Yes atau No',
            null,
            400
        );
    }


    /* =============================================
       VALIDASI NAMA
    ============================================= */

    if ($nama_layanan === '') {

        responseJson(
            false,
            'nama_layanan wajib diisi',
            null,
            400
        );
    }


    /* =============================================
       VALIDASI DESKRIPSI
    ============================================= */

    if ($deskripsi_layanan === '') {

        responseJson(
            false,
            'deskripsi_layanan wajib diisi',
            null,
            400
        );
    }


    try {

        /* =============================================
           CEK KATEGORI
        ============================================= */

        if (
            !kategoriExists(
                $conn,
                $id_kategori
            )
        ) {

            responseJson(
                false,
                'Kategori tidak ditemukan',
                null,
                404
            );
        }


        $created_by =
            isset($input['created_by']) ? (int) $input['created_by'] : 1;

        if ($created_by <= 0) {
            $created_by = 1;
        }

        /* =============================================
           CEK KUOTA LAYANAN (MAKSIMAL 3 UNTUK USER BIASA)
        ============================================= */
        if ($created_by > 1) {
            $userRoleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $userRoleStmt->execute([$created_by]);
            $userRole = strtoupper(trim((string) $userRoleStmt->fetchColumn()));

            if ($userRole !== 'ADMIN') {
                $countStmt = $conn->prepare("SELECT COUNT(*) FROM layanan WHERE created_by = ?");
                $countStmt->execute([$created_by]);
                $userLayananCount = (int) $countStmt->fetchColumn();

                if ($userLayananCount >= 3) {
                    responseJson(
                        false,
                        'Batas maksimal penambahan layanan portal telah tercapai (maksimal 3 layanan). Silakan hapus salah satu layanan untuk menambahkan yang baru.',
                        null,
                        400
                    );
                }
            }
        }


        /* =============================================
           INSERT
        ============================================= */

        $stmt = $conn->prepare("
            INSERT INTO layanan
            (
                id_kategori,
                created_by,
                akses,
                vpn,
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
                ?,
                ?,
                ?,
                ?
            )
        ");


        $stmt->execute([
            $id_kategori,
            $created_by,
            $akses,
            $vpn,
            $nama_layanan,
            $deskripsi_layanan,
            $url,
            $logo
        ]);


        $idBaru =
            $conn->lastInsertId();


        responseJson(
            true,
            'Layanan berhasil ditambahkan',
            [
                'id_layanan' =>
                    (int) $idBaru
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
   PUT
===================================================== */

if ($method === 'PUT') {

    $id =
        isset($_GET['id'])
            ? (int) $_GET['id']
            : (isset($_GET['id_layanan']) ? (int) $_GET['id_layanan'] : 0);


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


    $id_kategori =
        $input['id_kategori'] ?? null;

    $akses =
        trim(
            $input['akses'] ?? 'Public'
        );

    $vpn =
        trim(
            $input['vpn'] ?? 'No'
        );

    $nama_layanan =
        trim(
            $input['nama_layanan'] ?? ''
        );

    $deskripsi_layanan =
        trim(
            $input['deskripsi_layanan'] ?? ''
        );

    $url =
        normalizeUrl(
            $input['url'] ?? ''
        );

    $logo =
        trim(
            $input['logo'] ?? ''
        );


    /* =============================================
       VALIDASI KATEGORI
    ============================================= */

    if (
        $id_kategori === null ||
        filter_var(
            $id_kategori,
            FILTER_VALIDATE_INT
        ) === false ||
        (int) $id_kategori <= 0
    ) {

        responseJson(
            false,
            'id_kategori harus berupa angka yang valid',
            null,
            400
        );
    }


    $id_kategori =
        (int) $id_kategori;


    /* =============================================
       VALIDASI AKSES
    ============================================= */

    if (!validateAkses($akses)) {

        responseJson(
            false,
            'akses harus berupa Internal atau Public',
            null,
            400
        );
    }


    /* =============================================
       VALIDASI VPN
    ============================================= */

    if (!validateVpn($vpn)) {

        responseJson(
            false,
            'vpn harus berupa Yes atau No',
            null,
            400
        );
    }


    /* =============================================
       VALIDASI NAMA
    ============================================= */

    if ($nama_layanan === '') {

        responseJson(
            false,
            'nama_layanan wajib diisi',
            null,
            400
        );
    }


    /* =============================================
       VALIDASI DESKRIPSI
    ============================================= */

    if ($deskripsi_layanan === '') {

        responseJson(
            false,
            'deskripsi_layanan wajib diisi',
            null,
            400
        );
    }


    try {

        /* =============================================
           CEK KATEGORI
        ============================================= */

        if (
            !kategoriExists(
                $conn,
                $id_kategori
            )
        ) {

            responseJson(
                false,
                'Kategori tidak ditemukan',
                null,
                404
            );
        }


        $callerUserId = isset($input['user_id']) ? (int) $input['user_id'] : (isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0);

        /* =============================================
           CEK LAYANAN
        ============================================= */

        $cek = $conn->prepare("
            SELECT id_layanan, created_by
            FROM layanan
            WHERE id_layanan = ?
            LIMIT 1
        ");


        $cek->execute([
            $id
        ]);

        $existing = $cek->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$existing) {

            responseJson(
                false,
                'Data layanan tidak ditemukan',
                null,
                404
            );
        }

        if ($callerUserId > 0 && (int) ($existing['created_by'] ?? 1) !== $callerUserId) {
            responseJson(
                false,
                'Anda tidak memiliki izin untuk mengubah layanan ini',
                null,
                403
            );
        }


        /* =============================================
           UPDATE
        ============================================= */

        $stmt = $conn->prepare("
            UPDATE layanan
            SET
                id_kategori = ?,
                akses = ?,
                vpn = ?,
                nama_layanan = ?,
                deskripsi_layanan = ?,
                url = ?,
                logo = ?
            WHERE id_layanan = ?
        ");


        $stmt->execute([
            $id_kategori,
            $akses,
            $vpn,
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
   DELETE
===================================================== */

if ($method === 'DELETE') {

    $id =
        isset($_GET['id'])
            ? (int) $_GET['id']
            : (isset($_GET['id_layanan']) ? (int) $_GET['id_layanan'] : 0);


    if ($id <= 0) {

        responseJson(
            false,
            'ID layanan tidak valid',
            null,
            400
        );
    }


    try {

        $callerUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

        /* =============================================
           CEK DATA
        ============================================= */

        $cek = $conn->prepare("
            SELECT id_layanan, created_by
            FROM layanan
            WHERE id_layanan = ?
            LIMIT 1
        ");


        $cek->execute([
            $id
        ]);

        $existing = $cek->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$existing) {

            responseJson(
                false,
                'Data layanan tidak ditemukan',
                null,
                404
            );
        }

        if ($callerUserId > 0 && (int) ($existing['created_by'] ?? 1) !== $callerUserId) {
            responseJson(
                false,
                'Anda tidak memiliki izin untuk menghapus layanan ini',
                null,
                403
            );
        }


        /* =============================================
           DELETE
        ============================================= */

        $stmt = $conn->prepare("
            DELETE FROM layanan
            WHERE id_layanan = ?
        ");


        $stmt->execute([
            $id
        ]);


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