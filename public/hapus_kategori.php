<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

/* Ambil ID kategori */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: kategori.php');
    exit;
}

try {
    /* Cek apakah kategori ada */
    $cek_kategori = $conn->prepare("
        SELECT id_kategori
        FROM kategori
        WHERE id_kategori = ?
        LIMIT 1
    ");

    $cek_kategori->execute([$id]);
    $kategori = $cek_kategori->fetch(PDO::FETCH_ASSOC);

    if (!$kategori) {
        echo "
            <script>
                alert('Kategori tidak ditemukan.');
                window.location.href = 'kategori.php';
            </script>
        ";
        exit;
    }

    /* Cek apakah kategori masih memiliki layanan */
    $cek_layanan = $conn->prepare("
        SELECT COUNT(*)
        FROM layanan
        WHERE id_kategori = ?
    ");

    $cek_layanan->execute([$id]);
    $jumlah_layanan = (int) $cek_layanan->fetchColumn();

    /* Jika masih memiliki layanan */
    if ($jumlah_layanan > 0) {
        echo "
            <script>
                alert('Kategori tidak dapat dihapus karena masih memiliki layanan.');
                window.location.href = 'kategori.php';
            </script>
        ";
        exit;
    }

    /* Hapus kategori */
    $hapus = $conn->prepare("
        DELETE FROM kategori
        WHERE id_kategori = ?
    ");

    $hapus->execute([$id]);

    /* Kembali ke halaman kategori */
    header('Location: kategori.php');
    exit;

} catch (PDOException $e) {
    error_log('Gagal menghapus kategori: ' . $e->getMessage());
    echo "
        <script>
            alert('Terjadi kesalahan saat menghapus kategori.');
            window.location.href = 'kategori.php';
        </script>
    ";
    exit;
}