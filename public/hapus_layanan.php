<?php

require_once '../config/config.php';

/* =========================
   AMBIL ID LAYANAN
========================= */

$id = $_GET['id'] ?? null;

if (!$id) {
    die('ID layanan tidak ditemukan.');
}

/* =========================
   HAPUS LANGSUNG DARI DATABASE
========================= */

try {

    $stmt = $conn->prepare("
        DELETE FROM layanan
        WHERE id_layanan = ?
    ");

    $stmt->execute([
        (int) $id
    ]);

    /* =========================
       CEK HASIL
    ========================= */

    if ($stmt->rowCount() > 0) {

        header('Location: layanan.php');
        exit;

    } else {

        die('Data layanan tidak ditemukan atau sudah dihapus.');

    }

} catch (PDOException $e) {

    die('Gagal menghapus layanan: ' . htmlspecialchars($e->getMessage()));

}