<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

/* Hanya izinkan metode POST (Cegah CSRF via GET) */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo "Metode tidak diizinkan. Penghapusan harus melalui POST.";
    exit;
}

/* Verifikasi token CSRF */
if (!csrf_verify()) {
    http_response_code(403);
    echo "Token keamanan CSRF tidak valid atau telah kedaluwarsa.";
    exit;
}

/* =========================
   AMBIL ID LAYANAN DARI POST
========================= */

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: layanan.php');
    exit;
}

/* =========================
   HAPUS DARI DATABASE & UPLOADS
========================= */

try {
    // Ambil info logo sebelum dihapus
    $stmtLogo = $conn->prepare("
        SELECT logo
        FROM layanan
        WHERE id_layanan = ?
        LIMIT 1
    ");
    $stmtLogo->execute([$id]);
    $layanan = $stmtLogo->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("
        DELETE FROM layanan
        WHERE id_layanan = ?
    ");

    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0 && !empty($layanan['logo'])) {
        $logoName = basename($layanan['logo']);
        // Hindari menghapus logo default
        if ($logoName !== 'logo_bps.jpg') {
            $filePath = __DIR__ . '/uploads/' . $logoName;
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }

    header('Location: layanan.php');
    exit;

} catch (PDOException $e) {
    error_log('Gagal menghapus layanan: ' . $e->getMessage());
    header('Location: layanan.php');
    exit;
}