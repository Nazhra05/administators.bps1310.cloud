<?php
/**
 * API Handler for User Repository (Favorites, Links, Data Links)
 * BPS Kabupaten Solok Selatan
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/user_repo.php';

// Cek autentikasi
$userId = (int) ($_SESSION['admin_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sesi login tidak valid atau telah berakhir. Silakan login kembali.'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle JSON input or form POST
$input = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    if (!empty($raw) && str_starts_with(trim($raw), '{')) {
        $input = json_decode($raw, true) ?: [];
    } else {
        $input = $_POST;
    }
} else {
    $input = $_GET;
}

$action = trim($input['action'] ?? '');

try {
    switch ($action) {
        /* =====================================================
           FAVORITE TOGGLE / REMOVE
        ===================================================== */
        case 'toggle_favorite':
            $layananId = (int) ($input['id_layanan'] ?? 0);
            if ($layananId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID layanan tidak valid.']);
                exit;
            }

            // Pastikan layanan ada di database
            $chk = $conn->prepare("SELECT id_layanan, nama_layanan FROM layanan WHERE id_layanan = ? LIMIT 1");
            $chk->execute([$layananId]);
            $layanan = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$layanan) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Layanan tidak ditemukan.']);
                exit;
            }

            $isFav = user_toggle_favorite($userId, $layananId);
            echo json_encode([
                'success' => true,
                'is_favorite' => $isFav,
                'message' => $isFav ? 'Layanan berhasil ditambahkan ke Favorite!' : 'Layanan dihapus dari Favorite.',
                'layanan' => $layanan
            ]);
            exit;

        case 'remove_favorite':
            $layananId = (int) ($input['id_layanan'] ?? 0);
            if ($layananId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID layanan tidak valid.']);
                exit;
            }
            user_remove_favorite($userId, $layananId);
            echo json_encode(['success' => true, 'message' => 'Layanan berhasil dihapus dari Favorite.']);
            exit;

        /* =====================================================
           KUMPULAN LINK WEBSITE
        ===================================================== */
        case 'add_link':
            $nama = trim($input['nama_website'] ?? '');
            $url = trim($input['url_website'] ?? '');
            $desc = trim($input['deskripsi'] ?? '');

            if ($nama === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama website dan URL wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $id = user_add_link($userId, $nama, $url, $desc);
            echo json_encode([
                'success' => true,
                'id_link' => $id,
                'data' => [
                    'id_link' => $id,
                    'nama_website' => $nama,
                    'url_website' => $url,
                    'deskripsi' => $desc
                ],
                'message' => 'Link website berhasil disimpan.'
            ]);
            exit;

        case 'edit_link':
            $id = (int) ($input['id_link'] ?? 0);
            $nama = trim($input['nama_website'] ?? '');
            $url = trim($input['url_website'] ?? '');
            $desc = trim($input['deskripsi'] ?? '');

            if ($id <= 0 || $nama === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID, Nama website, dan URL wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $ok = user_update_link($userId, $id, $nama, $url, $desc);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Link website berhasil diperbarui.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui link atau akses ditolak.']);
            }
            exit;

        case 'delete_link':
            $id = (int) ($input['id_link'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID link tidak valid.']);
                exit;
            }

            $ok = user_delete_link($userId, $id);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Link website berhasil dihapus.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus link atau link tidak ditemukan.']);
            }
            exit;

        /* =====================================================
           KUMPULAN LINK DATA / DOKUMEN
        ===================================================== */
        case 'add_data_link':
            $nama = trim($input['nama_dokumen'] ?? '');
            $url = trim($input['url_dokumen'] ?? '');
            $jenis = trim($input['jenis_dokumen'] ?? 'Lainnya');
            $desc = trim($input['deskripsi'] ?? '');

            if ($nama === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama dokumen dan URL wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $id = user_add_data_link($userId, $nama, $url, $jenis, $desc);
            echo json_encode([
                'success' => true,
                'id_data_link' => $id,
                'data' => [
                    'id_data_link' => $id,
                    'nama_dokumen' => $nama,
                    'url_dokumen' => $url,
                    'jenis_dokumen' => $jenis,
                    'deskripsi' => $desc
                ],
                'message' => 'Link data/dokumen berhasil disimpan.'
            ]);
            exit;

        case 'edit_data_link':
            $id = (int) ($input['id_data_link'] ?? 0);
            $nama = trim($input['nama_dokumen'] ?? '');
            $url = trim($input['url_dokumen'] ?? '');
            $jenis = trim($input['jenis_dokumen'] ?? 'Lainnya');
            $desc = trim($input['deskripsi'] ?? '');

            if ($id <= 0 || $nama === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID, Nama dokumen, dan URL wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $ok = user_update_data_link($userId, $id, $nama, $url, $jenis, $desc);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Link data/dokumen berhasil diperbarui.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui link data atau akses ditolak.']);
            }
            exit;

        case 'delete_data_link':
            $id = (int) ($input['id_data_link'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID link data tidak valid.']);
                exit;
            }

            $ok = user_delete_data_link($userId, $id);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Link data/dokumen berhasil dihapus.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus link data atau data tidak ditemukan.']);
            }
            exit;

        /* =====================================================
           APLIKASI MOBILE
        ===================================================== */
        case 'add_mobile_app':
            $nama = trim($input['nama_aplikasi'] ?? '');
            $url = trim($input['url_aplikasi'] ?? '');
            $platform = trim($input['platform'] ?? 'Android');
            $desc = trim($input['deskripsi'] ?? '');

            if ($nama === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama aplikasi dan link aplikasi/download wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $id = user_add_mobile_app($userId, $nama, $url, $platform, $desc);
            echo json_encode([
                'success' => true,
                'id_app' => $id,
                'data' => [
                    'id_app' => $id,
                    'nama_aplikasi' => $nama,
                    'url_aplikasi' => $url,
                    'platform' => $platform,
                    'deskripsi' => $desc
                ],
                'message' => 'Aplikasi mobile berhasil disimpan.'
            ]);
            exit;

        case 'edit_mobile_app':
            $id = (int) ($input['id_app'] ?? 0);
            $nama = trim($input['nama_aplikasi'] ?? '');
            $url = trim($input['url_aplikasi'] ?? '');
            $platform = trim($input['platform'] ?? 'Android');
            $desc = trim($input['deskripsi'] ?? '');

            if ($id <= 0 || $nama === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID, Nama aplikasi, dan URL aplikasi wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $ok = user_update_mobile_app($userId, $id, $nama, $url, $platform, $desc);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Data aplikasi mobile berhasil diperbarui.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui aplikasi atau akses ditolak.']);
            }
            exit;

        case 'delete_mobile_app':
            $id = (int) ($input['id_app'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID aplikasi tidak valid.']);
                exit;
            }

            $ok = user_delete_mobile_app($userId, $id);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Aplikasi mobile berhasil dihapus.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus aplikasi atau data tidak ditemukan.']);
            }
            exit;

        /* =====================================================
           LINK DRIVE (FOLDER)
        ===================================================== */
        case 'add_drive_link':
            $nama = trim($input['nama_folder'] ?? '');
            $url = trim($input['url_folder'] ?? '');
            $jenisDrive = trim($input['jenis_drive'] ?? 'Google Drive');
            $desc = trim($input['deskripsi'] ?? '');

            if ($nama === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama folder dan URL folder wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $id = user_add_drive_link($userId, $nama, $url, $jenisDrive, $desc);
            echo json_encode([
                'success' => true,
                'id_drive' => $id,
                'data' => [
                    'id_drive' => $id,
                    'nama_folder' => $nama,
                    'url_folder' => $url,
                    'jenis_drive' => $jenisDrive,
                    'deskripsi' => $desc
                ],
                'message' => 'Link folder Drive berhasil disimpan.'
            ]);
            exit;

        case 'edit_drive_link':
            $id = (int) ($input['id_drive'] ?? 0);
            $nama = trim($input['nama_folder'] ?? '');
            $url = trim($input['url_folder'] ?? '');
            $jenisDrive = trim($input['jenis_drive'] ?? 'Google Drive');
            $desc = trim($input['deskripsi'] ?? '');

            if ($id <= 0 || $nama === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID, Nama folder, dan URL folder wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $ok = user_update_drive_link($userId, $id, $nama, $url, $jenisDrive, $desc);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Link folder Drive berhasil diperbarui.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui link folder atau akses ditolak.']);
            }
            exit;

        case 'delete_drive_link':
            $id = (int) ($input['id_drive'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID folder tidak valid.']);
                exit;
            }

            $ok = user_delete_drive_link($userId, $id);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Link folder Drive berhasil dihapus.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus folder atau data tidak ditemukan.']);
            }
            exit;

        /* =====================================================
           LINK FOTO
        ===================================================== */
        case 'add_photo_link':
            $judul = trim($input['judul_foto'] ?? '');
            $url = trim($input['url_foto'] ?? '');
            $desc = trim($input['deskripsi'] ?? '');

            if ($judul === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Judul foto dan URL foto/album wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $id = user_add_photo_link($userId, $judul, $url, $desc);
            echo json_encode([
                'success' => true,
                'id_photo' => $id,
                'data' => [
                    'id_photo' => $id,
                    'judul_foto' => $judul,
                    'url_foto' => $url,
                    'deskripsi' => $desc
                ],
                'message' => 'Link foto berhasil disimpan.'
            ]);
            exit;

        case 'edit_photo_link':
            $id = (int) ($input['id_photo'] ?? 0);
            $judul = trim($input['judul_foto'] ?? '');
            $url = trim($input['url_foto'] ?? '');
            $desc = trim($input['deskripsi'] ?? '');

            if ($id <= 0 || $judul === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID, Judul foto, dan URL foto wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $ok = user_update_photo_link($userId, $id, $judul, $url, $desc);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Link foto berhasil diperbarui.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui link foto atau akses ditolak.']);
            }
            exit;

        case 'delete_photo_link':
            $id = (int) ($input['id_photo'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID foto tidak valid.']);
                exit;
            }

            $ok = user_delete_photo_link($userId, $id);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Link foto berhasil dihapus.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus link foto atau data tidak ditemukan.']);
            }
            exit;

        /* =====================================================
           LINK LAINNYA
        ===================================================== */
        case 'add_other_link':
            $nama = trim($input['nama_link'] ?? '');
            $url = trim($input['url_link'] ?? '');
            $label = trim($input['label'] ?? 'Referensi');
            $desc = trim($input['deskripsi'] ?? '');

            if ($nama === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama link dan URL wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $id = user_add_other_link($userId, $nama, $url, $label, $desc);
            echo json_encode([
                'success' => true,
                'id_other' => $id,
                'data' => [
                    'id_other' => $id,
                    'nama_link' => $nama,
                    'url_link' => $url,
                    'label' => $label,
                    'deskripsi' => $desc
                ],
                'message' => 'Link lainnya berhasil disimpan.'
            ]);
            exit;

        case 'edit_other_link':
            $id = (int) ($input['id_other'] ?? 0);
            $nama = trim($input['nama_link'] ?? '');
            $url = trim($input['url_link'] ?? '');
            $label = trim($input['label'] ?? 'Referensi');
            $desc = trim($input['deskripsi'] ?? '');

            if ($id <= 0 || $nama === '' || $url === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID, Nama link, dan URL wajib diisi.']);
                exit;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL) && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                $url = 'https://' . $url;
            }

            $ok = user_update_other_link($userId, $id, $nama, $url, $label, $desc);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Link lainnya berhasil diperbarui.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui link atau akses ditolak.']);
            }
            exit;

        case 'delete_other_link':
            $id = (int) ($input['id_other'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID link tidak valid.']);
                exit;
            }

            $ok = user_delete_other_link($userId, $id);
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Link lainnya berhasil dihapus.']);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus link atau data tidak ditemukan.']);
            }
            exit;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    exit;
}
