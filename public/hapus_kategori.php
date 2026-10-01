<?php

require_once '../config/config.php';

/* Ambil ID kategori */
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: kategori.php');
    exit;
}

/* Cek apakah kategori ada */
$cek_kategori = $conn->prepare("
    SELECT *
    FROM kategori
    WHERE id_kategori = ?
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

$jumlah_layanan = $cek_layanan->fetchColumn();


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

?>