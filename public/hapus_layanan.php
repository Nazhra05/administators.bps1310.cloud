<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

/* =========================
   AMBIL ID LAYANAN
========================= */

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

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