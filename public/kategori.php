<?php

require_once '../config/config.php';
require_once '../config/auth.php';
require_once __DIR__ . '/partials/icons.php';

/* =========================
   CEK LOGIN ADMIN
========================= */

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$is_real_admin = is_admin_user();
$active_role = auth_active_role();

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $active_role;

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $pesan_error = 'Sesi keamanan tidak valid atau telah kedaluwarsa.';
    } else {
        $aksi = trim($_POST['aksi'] ?? '');
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');

        if ($aksi === 'tambah') {
            if ($nama_kategori === '') {
                $pesan_error = 'Nama kategori wajib diisi.';
            } else {
                $cek = $conn->prepare("SELECT COUNT(*) FROM kategori WHERE nama_kategori = ?");
                $cek->execute([$nama_kategori]);
                if ($cek->fetchColumn() > 0) {
                    $pesan_error = 'Kategori tersebut sudah ada.';
                } else {
                    $ins = $conn->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
                    $ins->execute([$nama_kategori]);
                    header('Location: kategori.php?status=tambah_sukses');
                    exit;
                }
            }
        } elseif ($aksi === 'edit') {
            $id_kategori = (int)($_POST['id_kategori'] ?? 0);
            if ($id_kategori <= 0 || $nama_kategori === '') {
                $pesan_error = 'Data kategori tidak lengkap.';
            } else {
                $cek = $conn->prepare("SELECT COUNT(*) FROM kategori WHERE nama_kategori = ? AND id_kategori != ?");
                $cek->execute([$nama_kategori, $id_kategori]);
                if ($cek->fetchColumn() > 0) {
                    $pesan_error = 'Nama kategori sudah digunakan oleh kategori lain.';
                } else {
                    $upd = $conn->prepare("UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?");
                    $upd->execute([$nama_kategori, $id_kategori]);
                    header('Location: kategori.php?status=edit_sukses');
                    exit;
                }
            }
        }
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'tambah_sukses') {
        $pesan_sukses = 'Kategori baru berhasil ditambahkan!';
    } elseif ($_GET['status'] === 'edit_sukses') {
        $pesan_sukses = 'Kategori berhasil diperbarui!';
    }
}

/* =====================================================
   AMBIL SEMUA KATEGORI + JUMLAH LAYANAN
===================================================== */

$stmt = $conn->query("
    SELECT 
        k.id_kategori,
        k.nama_kategori,
        COUNT(l.id_layanan) AS jumlah_layanan
    FROM kategori k
    LEFT JOIN layanan l
        ON k.id_kategori = l.id_kategori
    GROUP BY
        k.id_kategori,
        k.nama_kategori
    ORDER BY
        k.id_kategori ASC
");

$kategori = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/* =====================================================
   AMBIL PEEK / PREVIEW LAYANAN
   MAKSIMAL 3 LAYANAN PER KATEGORI
===================================================== */

$stmt_layanan = $conn->query("
    SELECT
        id_layanan,
        id_kategori,
        nama_layanan,
        url
    FROM layanan
    ORDER BY
        id_kategori ASC,
        id_layanan ASC
");

$semua_layanan = $stmt_layanan->fetchAll(
    PDO::FETCH_ASSOC
);


/* =====================================================
   GROUP LAYANAN BERDASARKAN KATEGORI
===================================================== */

$layanan_per_kategori = [];


foreach ($semua_layanan as $layanan) {

    $id_kategori =
        $layanan['id_kategori'];


    if (
        !isset(
            $layanan_per_kategori[$id_kategori]
        )
    ) {

        $layanan_per_kategori[$id_kategori] = [];

    }


    $layanan_per_kategori[$id_kategori][] =
        $layanan;

}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Kategori - BPS Solok Selatan
</title>
<link rel="stylesheet" href="css/style.css">

<style>

/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


body {
    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background: #f4f8fc;

    color: #1f2937;

    overflow-x: hidden;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {
    width: 240px;

    height: 100vh;

    background: #0066b3;

    color: white;

    position: fixed;

    left: 0;
    top: 0;

    padding: 25px 15px;

    z-index: 9999;

    box-shadow:
        2px 0 10px
        rgba(0, 0, 0, 0.08);

    transform:
        translateX(-100%);

    transition:
        transform 0.3s ease;
}


.sidebar.sidebar-open {
    transform:
        translateX(0);
}


.logo {
    font-size: 20px;

    font-weight: bold;

    margin-bottom: 40px;

    padding: 0 15px;
}


.menu {
    list-style: none;
}


.menu li {
    margin-bottom: 8px;

    width: 100%;
}


.menu a {
    display: block;

    width: 100%;

    color: #e8f3fb;

    text-decoration: none;

    padding: 12px 15px;

    border-radius: 8px;

    transition: 0.2s ease;

    font-size: 16px;
}


.menu a:hover {
    background:
        rgba(255, 255, 255, 0.15);

    color: white;
}


.menu .active {
    background: white;

    color: #0066b3;

    font-weight: bold;
}


.main {
    margin-left: 0;
    padding: 30px;
    min-width: 0;
    transition: margin-left 0.3s ease;
}


.main.sidebar-open {
    margin-left: 240px;
}


/* =====================================================
   HEADER
===================================================== */

.header {
    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;

    margin-bottom: 25px;
}


.header h1 {
    font-size: 28px;

    margin-bottom: 8px;

    color: #005a9c;
}


.header p {
    color: #6b7280;

    line-height: 1.5;

    font-size: 16px;
}


/* =====================================================
   ADMIN DROPDOWN
===================================================== */

.admin-dropdown {
    position: relative;

    flex-shrink: 0;
}


.admin-status {
    display: flex;

    align-items: center;

    gap: 10px;

    background: white;

    padding:
        10px 15px;

    border-radius: 10px;

    box-shadow:
        0 2px 10px
        rgba(0, 102, 179, 0.08);

    border:
        1px solid #e1edf7;

    white-space: nowrap;

    cursor: pointer;

    font-family: inherit;
}


.admin-status:hover {
    background: #f8fbfe;
}


.admin-icon {
    width: 36px;

    height: 36px;

    background: #eaf4fb;

    color: #0066b3;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;

    flex-shrink: 0;
}


.admin-info {
    display: flex;

    flex-direction: column;

    gap: 3px;

    text-align: left;
}


.admin-label {
    font-size: 12px;

    color: #6b7280;
}


.admin-name {
    font-size: 14px;

    font-weight: bold;

    color: #005a9c;
}


.dropdown-arrow {
    font-size: 18px;

    color: #005a9c;

    margin-left: 5px;
}


/* =====================================================
   MENU LOGOUT
===================================================== */

.admin-menu {
    display: none;

    position: absolute;

    right: 0;

    top:
        calc(100% + 8px);

    min-width: 185px;

    background: white;

    border-radius: 10px;

    box-shadow:
        0 5px 18px
        rgba(0, 0, 0, 0.15);

    border:
        1px solid #e1edf7;

    padding: 8px;

    z-index: 10000;
}


.admin-menu.show {
    display: block;
}


.switch-button {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 12px;
    color: #0066b3;
    text-decoration: none;
    border-radius: 7px;
    font-size: 13.5px;
    font-weight: 600;
    transition: background 0.2s ease, color 0.2s ease;
}

.switch-button:hover {
    background: #e6f0fa;
    color: #004d84;
}

.menu-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 6px 0;
}


.logout-button {
    display: flex;
    align-items: center;
    gap: 8px;

    width: 100%;

    padding:
        10px 12px;

    color: #c62828;

    text-decoration: none;

    border-radius: 7px;

    font-size: 13.5px;

    font-weight: 600;
}


.logout-button:hover {
    background: #fff1f1;
}


/* =====================================================
   BUTTON TAMBAH KATEGORI
===================================================== */

.action-bar {
    margin-bottom: 20px;
}


.btn-tambah {
    display: inline-block;

    padding:
        11px 18px;

    background: #0066b3;

    color: white;

    text-decoration: none;

    border-radius: 7px;

    font-size: 14px;

    font-weight: 600;

    transition: 0.2s ease;
}


.btn-tambah:hover {
    background: #00589c;
}


/* =====================================================
   TABLE
===================================================== */

.table-container {
    background: white;

    padding: 25px;

    border-radius: 10px;

    box-shadow:
        0 2px 10px
        rgba(0, 102, 179, 0.08);

    overflow-x: auto;

    -webkit-overflow-scrolling: touch;
}


table {
    width: 100%;

    min-width: 850px;

    border-collapse: collapse;
}


th {
    background: #eaf4fb;

    text-align: left;

    padding: 14px;

    color: #005a9c;

    font-size: 18px;

    font-weight: bold;

    white-space: nowrap;
}


td {
    padding: 14px;

    border-bottom:
        1px solid #e5e7eb;

    vertical-align: middle;

    font-size: 16px;
}


tr:hover td {
    background: #f7fbfe;
}


/* =====================================================
   JUMLAH LAYANAN
===================================================== */

.jumlah-layanan {
    color: #0066b3;

    font-weight: 600;

    white-space: nowrap;

    font-size: 16px;
}


/* =====================================================
   PEEK LAYANAN
===================================================== */

.peek-layanan {
    display: flex;

    flex-direction: column;

    gap: 7px;

    min-width: 250px;

    max-width: 400px;
}


.peek-item {
    display: flex;

    align-items: center;

    gap: 7px;

    min-width: 0;
}


.peek-icon {
    color: #0066b3;

    font-size: 15px;

    flex-shrink: 0;
}


.peek-link {
    color: #304b72;

    text-decoration: none;

    font-size: 15px;

    line-height: 1.4;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.peek-link:hover {
    color: #0066b3;

    text-decoration: underline;
}


.peek-name {
    color: #304b72;

    font-size: 15px;

    line-height: 1.4;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.peek-more {
    color: #91a4c0;

    font-size: 13px;

    font-style: italic;

    margin-top: 2px;
}


.peek-empty {
    color: #9ca3af;

    font-size: 15px;

    font-style: italic;
}


/* =====================================================
   ACTION
===================================================== */

.action {
    display: flex;

    gap: 8px;

    align-items: center;

    white-space: nowrap;
}


.btn-action {
    display: inline-block;

    padding:
        7px 12px;

    border-radius: 6px;

    text-decoration: none;

    font-size: 14px;

    font-weight: 600;

    transition: 0.2s ease;
}


.btn-edit {
    background: #eaf4fb;

    color: #0066b3;
}


.btn-edit:hover {
    background: #d8ebf8;
}


.btn-delete {
    background: #fff1f1;

    color: #ff7e42;
}


.btn-delete:hover {
    background: #ffe0e0;
}


/* =====================================================
   EMPTY
===================================================== */

.empty {
    text-align: center;

    color: #777;

    padding: 30px;

    font-size: 16px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 768px) {


    /* SIDEBAR */

    .sidebar {
        width: 250px;

        height: 100vh;

        transform:
            translateX(-100%);

        padding:
            25px 15px;
    }


    /* MAIN */

    .main {
        margin-left: 0;

        padding:
            75px
            15px
            25px;

        width: 100%;
    }


    .main.sidebar-open {
        margin-left: 0;
    }


    /* TOMBOL MENU */

    .mobile-menu-button {
        display: flex;
    }


    /* HEADER */

    .header {
        flex-direction: column;

        gap: 15px;

        margin-bottom: 20px;
    }


    .header h1 {
        font-size: 24px;
    }


    .header p {
        font-size: 14px;
    }


    /* ADMIN */

    .admin-dropdown {
        width: 100%;
    }


    .admin-status {
        width: 100%;

        justify-content: flex-start;
    }


    .admin-menu {
        left: 0;

        right: auto;

        width: 100%;
    }


    /* BUTTON TAMBAH */

    .action-bar {
        width: 100%;

        margin-bottom: 15px;
    }


    .btn-tambah {
        display: block;

        width: 100%;

        text-align: center;

        padding: 12px;
    }


    /* TABLE */

    .table-container {
        padding: 15px;

        width: 100%;

        overflow-x: auto;
    }


    table {
        min-width: 850px;
    }


    th {
        padding: 12px;

        font-size: 16px;
    }


    td {
        padding: 12px;

        font-size: 15px;
    }


    .peek-layanan {
        min-width: 230px;
    }

}


/* =====================================================
   HP SANGAT KECIL
===================================================== */

@media (max-width: 480px) {

    .main {
        padding-left: 12px;

        padding-right: 12px;
    }


    .header h1 {
        font-size: 22px;
    }


    .header p {
        font-size: 13px;
    }


    .table-container {
        padding: 10px;
    }


    table {
        min-width: 820px;
    }


    th {
        font-size: 15px;

        padding: 10px;
    }


    td {
        font-size: 14px;

        padding: 10px;
    }


    .btn-action {
        padding:
            7px 10px;

        font-size: 13px;
    }


    .peek-layanan {
        min-width: 220px;
    }

}

</style>

</head>


<body>




<!-- =====================================================
     OVERLAY
===================================================== -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<?php
$active_nav = 'kategori';
include __DIR__ . '/partials/navbar.php';
?>


<!-- =====================================================
     MAIN
===================================================== -->

<main
    class="main"
    id="mainContent"
>


    <!-- =================================================
         HEADER
    ================================================= -->

    <?php
    $page_title = 'Kategori';
    $page_subtitle = 'Daftar kategori layanan BPS Kabupaten Solok Selatan.';
    include __DIR__ . '/partials/topbar.php';
    ?>

    <!-- =================================================
         ACTION BAR & NOTIFIKASI
    ================================================= -->

    <?php if ($pesan_sukses): ?>
        <div style="background: #dcfce7; color: #166534; padding: 12px 18px; border-radius: 8px; margin: 0 0 16px; font-weight: 600; border: 1px solid #bbf7d0;">
            <?= htmlspecialchars($pesan_sukses); ?>
        </div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px 18px; border-radius: 8px; margin: 0 0 16px; font-weight: 600; border: 1px solid #fecaca;">
            <?= htmlspecialchars($pesan_error); ?>
        </div>
    <?php endif; ?>

    <div class="action-bar">
        <a href="tambah_kategori.php" class="btn-tambah" onclick="openModal('modalTambahKategori'); return false;" style="display: inline-flex; align-items: center; gap: 8px;">
            <?= render_icon('plus', 'btn-icon', 16); ?> Tambah Kategori
        </a>
    </div>

    <!-- =================================================
         TABLE
    ================================================= -->

    <div class="table-container">


        <table>


            <thead>

                <tr>


                    <th>
                        No
                    </th>


                    <th>
                        Nama Kategori
                    </th>


                    <th>
                        Peek Layanan
                    </th>


                    <th>
                        Jumlah Layanan
                    </th>


                    <th>
                        Aksi
                    </th>


                </tr>

            </thead>


            <tbody>


            <?php if (empty($kategori)): ?>


                <tr>

                    <td
                        colspan="5"
                        class="empty"
                    >
                        Belum ada kategori.
                    </td>

                </tr>


            <?php else: ?>


                <?php $no = 1; ?>


                <?php foreach (
                    $kategori
                    as $item
                ): ?>


                    <?php

                        $id_kategori =
                            $item['id_kategori'];


                        $layanan_kategori =
                            $layanan_per_kategori[
                                $id_kategori
                            ] ?? [];


                        /*
                         * Maksimal 3 layanan
                         * untuk preview.
                         */

                        $peek_layanan =
                            array_slice(
                                $layanan_kategori,
                                0,
                                3
                            );


                        $jumlah_peek =
                            count(
                                $peek_layanan
                            );


                        $jumlah_lainnya =
                            max(
                                0,
                                count(
                                    $layanan_kategori
                                ) - 3
                            );

                    ?>


                    <tr>


                        <!-- =================================
                             NOMOR
                        ================================== -->

                        <td>
                            <?= $no++; ?>
                        </td>


                        <!-- =================================
                             NAMA KATEGORI
                        ================================== -->

                        <td>


                            <span
                                style="
                                    font-size: 18px;
                                    font-weight: 600;
                                "
                            >

                                <?= htmlspecialchars(
                                    $item['nama_kategori']
                                ); ?>

                            </span>


                        </td>


                        <!-- =================================
                             PEEK LAYANAN
                        ================================== -->

                        <td>


                            <div
                                class="peek-layanan"
                            >


                                <?php if (
                                    empty(
                                        $peek_layanan
                                    )
                                ): ?>


                                    <div
                                        class="peek-empty"
                                    >
                                        Belum ada layanan
                                    </div>


                                <?php else: ?>


                                    <?php foreach (
                                        $peek_layanan
                                        as $layanan
                                    ): ?>


                                        <div
                                            class="peek-item"
                                        >


                                            <span
                                                class="peek-icon"
                                            >
                                                ↗
                                            </span>


                                            <?php if (
                                                !empty(
                                                    trim(
                                                        $layanan['url']
                                                    )
                                                )
                                            ): ?>


                                                <a
                                                    href="<?= htmlspecialchars(
                                                        trim(
                                                            $layanan['url']
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="peek-link"
                                                    title="<?= htmlspecialchars(
                                                        $layanan['nama_layanan']
                                                    ); ?>"
                                                >

                                                    <?= htmlspecialchars(
                                                        $layanan['nama_layanan']
                                                    ); ?>

                                                </a>


                                            <?php else: ?>


                                                <span
                                                    class="peek-name"
                                                    title="<?= htmlspecialchars(
                                                        $layanan['nama_layanan']
                                                    ); ?>"
                                                >

                                                    <?= htmlspecialchars(
                                                        $layanan['nama_layanan']
                                                    ); ?>

                                                </span>


                                            <?php endif; ?>


                                        </div>


                                    <?php endforeach; ?>


                                    <?php if (
                                        $jumlah_lainnya > 0
                                    ): ?>


                                        <div
                                            class="peek-more"
                                        >

                                            +
                                            <?= $jumlah_lainnya; ?>
                                            layanan lainnya

                                        </div>


                                    <?php endif; ?>


                                <?php endif; ?>


                            </div>


                        </td>


                        <!-- =================================
                             JUMLAH LAYANAN
                        ================================== -->

                        <td
                            class="jumlah-layanan"
                        >

                            <?= $item['jumlah_layanan']; ?>
                            layanan

                        </td>


                        <!-- =================================
                             AKSI
                        ================================== -->

                        <td>


                            <div
                                class="action"
                            >


                                <a
                                    href="edit_kategori.php?id=<?= $item['id_kategori']; ?>"
                                    class="btn-action btn-edit"
                                    onclick="openEditKategori(<?= (int)$item['id_kategori']; ?>, '<?= htmlspecialchars(addslashes($item['nama_kategori']), ENT_QUOTES, 'UTF-8'); ?>'); return false;"
                                    style="display: inline-flex; align-items: center; gap: 6px;"
                                >
                                    <?= render_icon('pencil', 'btn-icon', 14); ?> Edit
                                </a>


                                <form method="POST" action="hapus_kategori.php" onsubmit="return confirm('Yakin ingin menghapus kategori ini?');" style="display: inline-flex; margin: 0;">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id_kategori']; ?>">
                                    <button type="submit" class="btn-action btn-delete" title="Hapus Kategori" style="display: inline-flex; align-items: center; gap: 6px;">
                                        <?= render_icon('trash-2', 'btn-icon', 14); ?> Hapus
                                    </button>
                                </form>
                            </div>


                        </td>


                    </tr>


                <?php endforeach; ?>


            <?php endif; ?>


            </tbody>


        </table>


    </div>


</main>


<script>

/* =====================================================
   MENU NAVIGASI
   TIDAK ADA FUNGSI CLOSE
===================================================== */

document
    .querySelectorAll('.menu a')
    .forEach(
        function(link) {

            link.addEventListener(
                'click',
                function() {

                    /*
                     * Tidak melakukan apa-apa.
                     *
                     * Browser langsung menuju
                     * halaman yang dipilih.
                     */

                }
            );

        }
    );


/* =====================================================
   RESPONSIVE OVERLAY
===================================================== */

window.addEventListener(
    'resize',
    function() {

        const sidebar =
            document.getElementById(
                'sidebar'
            );


        const overlay =
            document.getElementById(
                'sidebarOverlay'
            );


        if (
            window.innerWidth > 768
        ) {

            overlay.classList.remove(
                'show'
            );

        } else if (
            sidebar.classList.contains(
                'sidebar-open'
            )
        ) {

            overlay.classList.add(
                'show'
            );

        }

    }
);


/* =====================================================
   ADMIN DROPDOWN
===================================================== */

function toggleAdminMenu() {

    const menu =
        document.getElementById(
            'adminMenu'
        );


    menu.classList.toggle(
        'show'
    );

}


/* =====================================================
   TUTUP DROPDOWN JIKA KLIK DI LUAR
===================================================== */

document.addEventListener(
    'click',
    function(event) {

        const dropdown =
            document.querySelector(
                '.admin-dropdown'
            );


        const menu =
            document.getElementById(
                'adminMenu'
            );


        if (
            !dropdown.contains(
                event.target
            )
        ) {

            menu.classList.remove(
                'show'
            );

        }

    }
);

</script>

<!-- =====================================================
     MODAL TAMBAH KATEGORI
===================================================== -->
<div class="modal-overlay" id="modalTambahKategori" onclick="closeModalOnBackdrop(event, 'modalTambahKategori')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><?= render_icon('plus', '', 18); ?> Tambah Kategori Baru</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalTambahKategori')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="kategori.php" id="formTambahKategori">
            <?= csrf_field(); ?>
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-group">
                <label for="tambah_nama_kategori">Nama Kategori <span style="color:#ef4444">*</span></label>
                <input type="text" id="tambah_nama_kategori" name="nama_kategori" required placeholder="Contoh: Pariwisata & Budaya" class="form-control">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-action" onclick="closeModal('modalTambahKategori')">Batal</button>
                <button type="submit" class="btn-tambah">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- =====================================================
     MODAL EDIT KATEGORI
===================================================== -->
<div class="modal-overlay" id="modalEditKategori" onclick="closeModalOnBackdrop(event, 'modalEditKategori')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3><?= render_icon('pencil', '', 18); ?> Edit Kategori</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalEditKategori')" aria-label="Tutup"><?= render_icon('x', '', 18); ?></button>
        </div>
        <form method="POST" action="kategori.php" id="formEditKategori">
            <?= csrf_field(); ?>
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" id="edit_id_kategori" name="id_kategori" value="">
            <div class="form-group">
                <label for="edit_nama_kategori">Nama Kategori <span style="color:#ef4444">*</span></label>
                <input type="text" id="edit_nama_kategori" name="nama_kategori" required class="form-control">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-action" onclick="closeModal('modalEditKategori')">Batal</button>
                <button type="submit" class="btn-tambah">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function closeModalOnBackdrop(event, id) {
    if (event.target && event.target.id === id) {
        closeModal(id);
    }
}

function openEditKategori(id, nama) {
    document.getElementById('edit_id_kategori').value = id;
    document.getElementById('edit_nama_kategori').value = nama;
    openModal('modalEditKategori');
}
</script>

</body>

</html>