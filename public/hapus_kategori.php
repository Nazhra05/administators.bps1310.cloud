<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

$active_role = auth_active_role();

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

/* =====================================================
   VALIDASI METODE & TOKEN CSRF
===================================================== */

$is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');
$is_json = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
           (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

$id = $is_post
    ? (filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id_kategori', FILTER_VALIDATE_INT))
    : filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    if ($is_json) {
        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'ID kategori tidak valid.']);
        exit;
    }
    header('Location: kategori.php');
    exit;
}

if ($is_post) {
    if (!csrf_verify()) {
        http_response_code(403);
        if ($is_json) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid atau telah kedaluwarsa.']);
            exit;
        }
        die('Akses ditolak: Token CSRF tidak valid atau sesi telah kedaluwarsa.');
    }
} else {
    // Akses via HTTP GET: Cek apakah token CSRF disertakan pada URL
    $getToken = filter_input(INPUT_GET, 'csrf_token', FILTER_DEFAULT);
    if (!$getToken || !csrf_verify($getToken)) {
        // Tampilkan halaman konfirmasi aman dengan form POST dan token CSRF
        $stmtInfo = $conn->prepare("SELECT nama_kategori FROM kategori WHERE id_kategori = ? LIMIT 1");
        $stmtInfo->execute([$id]);
        $namaKat = $stmtInfo->fetchColumn() ?: 'Kategori #' . $id;
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Konfirmasi Hapus Kategori</title>
            <link rel="stylesheet" href="css/style.css">
            <style>
                body {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    background: var(--bg-main, #f1f5f9);
                    font-family: system-ui, -apple-system, sans-serif;
                    margin: 0;
                    padding: 1rem;
                }
                .confirm-card {
                    background: #ffffff;
                    padding: 2rem;
                    border-radius: 12px;
                    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
                    max-width: 440px;
                    width: 100%;
                    text-align: center;
                }
                .confirm-card h2 {
                    margin-top: 0;
                    color: #0f172a;
                    font-size: 1.25rem;
                }
                .confirm-card p {
                    color: #475569;
                    margin: 1rem 0 1.5rem;
                    line-height: 1.5;
                }
                .confirm-card .btn-group {
                    display: flex;
                    gap: 0.75rem;
                    justify-content: center;
                }
                .btn-cancel {
                    padding: 0.625rem 1.25rem;
                    border-radius: 8px;
                    background: #e2e8f0;
                    color: #334155;
                    text-decoration: none;
                    font-weight: 500;
                }
                .btn-confirm-delete {
                    padding: 0.625rem 1.25rem;
                    border-radius: 8px;
                    background: #ef4444;
                    color: #ffffff;
                    border: none;
                    font-weight: 500;
                    cursor: pointer;
                }
                .btn-confirm-delete:hover {
                    background: #dc2626;
                }
            </style>
        </head>
        <body>
            <div class="confirm-card">
                <h2>Konfirmasi Hapus Kategori</h2>
                <p>Apakah Anda yakin ingin menghapus kategori <strong><?= htmlspecialchars((string)$namaKat, ENT_QUOTES, 'UTF-8'); ?></strong>?</p>
                <form method="POST" action="hapus_kategori.php">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="id" value="<?= (int) $id; ?>">
                    <div class="btn-group">
                        <a href="kategori.php" class="btn-cancel">Batal</a>
                        <button type="submit" class="btn-confirm-delete">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
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
        if ($is_json) {
            http_response_code(404);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan.']);
            exit;
        }
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
        if ($is_json) {
            http_response_code(409);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Kategori tidak dapat dihapus karena masih memiliki layanan.']);
            exit;
        }
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

    if ($is_json) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true, 'message' => 'Kategori berhasil dihapus.']);
        exit;
    }

    /* Kembali ke halaman kategori */
    header('Location: kategori.php?status=deleted');
    exit;

} catch (PDOException $e) {
    error_log('Gagal menghapus kategori: ' . $e->getMessage());
    if ($is_json) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem saat menghapus kategori.']);
        exit;
    }
    echo "
        <script>
            alert('Terjadi kesalahan saat menghapus kategori.');
            window.location.href = 'kategori.php';
        </script>
    ";
    exit;
}