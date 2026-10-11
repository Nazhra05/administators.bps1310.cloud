<?php
/**
 * Layanan Repositori Pegawai & Favorit (User Repository Service)
 * BPS Kabupaten Solok Selatan
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/auth.php';

/**
 * Mendapatkan ringkasan layanan portal yang dibuat oleh user
 */
if (!function_exists('user_get_portal_summary')) {
    function user_get_portal_summary(int $userId): array
    {
        global $conn;

        try {
            $stmtCount = $conn->prepare("SELECT COUNT(*) FROM layanan WHERE created_by = ?");
            $stmtCount->execute([$userId]);
            $total = (int) $stmtCount->fetchColumn();

            $stmtList = $conn->prepare("
                SELECT l.id_layanan, l.nama_layanan, l.url, l.akses, k.nama_kategori
                FROM layanan l
                LEFT JOIN kategori k ON l.id_kategori = k.id_kategori
                WHERE l.created_by = ?
                ORDER BY l.id_layanan DESC
                LIMIT 5
            ");
            $stmtList->execute([$userId]);
            $items = $stmtList->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'items' => $items
            ];
        } catch (PDOException $e) {
            error_log("user_get_portal_summary error: " . $e->getMessage());
            return ['total' => 0, 'items' => []];
        }
    }
}

/**
 * Mendapatkan ringkasan layanan favorit user
 */
if (!function_exists('user_get_favorites_summary')) {
    function user_get_favorites_summary(int $userId): array
    {
        global $conn;

        try {
            $stmtCount = $conn->prepare("SELECT COUNT(*) FROM user_favorites WHERE id_user = ?");
            $stmtCount->execute([$userId]);
            $total = (int) $stmtCount->fetchColumn();

            $stmtList = $conn->prepare("
                SELECT f.id_favorite, l.id_layanan, l.nama_layanan, l.url, l.logo, k.nama_kategori
                FROM user_favorites f
                JOIN layanan l ON f.id_layanan = l.id_layanan
                LEFT JOIN kategori k ON l.id_kategori = k.id_kategori
                WHERE f.id_user = ?
                ORDER BY f.id_favorite DESC
                LIMIT 5
            ");
            $stmtList->execute([$userId]);
            $items = $stmtList->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total' => $total,
                'items' => $items
            ];
        } catch (PDOException $e) {
            error_log("user_get_favorites_summary error: " . $e->getMessage());
            return ['total' => 0, 'items' => []];
        }
    }
}

/**
 * Mendapatkan seluruh layanan favorit user
 */
if (!function_exists('user_get_all_favorites')) {
    function user_get_all_favorites(int $userId): array
    {
        global $conn;

        try {
            $stmt = $conn->prepare("
                SELECT f.id_favorite, f.created_at AS favorited_at,
                       l.id_layanan, l.nama_layanan, l.deskripsi_layanan, l.url, l.logo, l.akses, l.vpn,
                       k.nama_kategori
                FROM user_favorites f
                JOIN layanan l ON f.id_layanan = l.id_layanan
                LEFT JOIN kategori k ON l.id_kategori = k.id_kategori
                WHERE f.id_user = ?
                ORDER BY f.id_favorite DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("user_get_all_favorites error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Ambil daftar ID layanan yang difavoritkan oleh user
 */
if (!function_exists('user_get_favorite_ids')) {
    function user_get_favorite_ids(int $userId): array
    {
        global $conn;

        try {
            $stmt = $conn->prepare("SELECT id_layanan FROM user_favorites WHERE id_user = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("user_get_favorite_ids error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Toggle status favorit layanan
 */
if (!function_exists('user_toggle_favorite')) {
    function user_toggle_favorite(int $userId, int $layananId): bool
    {
        global $conn;

        try {
            $stmtCheck = $conn->prepare("SELECT id_favorite FROM user_favorites WHERE id_user = ? AND id_layanan = ? LIMIT 1");
            $stmtCheck->execute([$userId, $layananId]);
            $existing = $stmtCheck->fetchColumn();

            if ($existing) {
                $stmtDel = $conn->prepare("DELETE FROM user_favorites WHERE id_user = ? AND id_layanan = ?");
                $stmtDel->execute([$userId, $layananId]);
                return false;
            } else {
                $stmtIns = $conn->prepare("INSERT INTO user_favorites (id_user, id_layanan) VALUES (?, ?)");
                $stmtIns->execute([$userId, $layananId]);
                return true;
            }
        } catch (PDOException $e) {
            error_log("user_toggle_favorite error: " . $e->getMessage());
            throw $e;
        }
    }
}

/**
 * Hapus layanan dari favorit berdasarkan ID favorit atau ID layanan
 */
if (!function_exists('user_remove_favorite')) {
    function user_remove_favorite(int $userId, int $layananId): bool
    {
        global $conn;

        try {
            $stmt = $conn->prepare("DELETE FROM user_favorites WHERE id_user = ? AND id_layanan = ?");
            return $stmt->execute([$userId, $layananId]);
        } catch (PDOException $e) {
            error_log("user_remove_favorite error: " . $e->getMessage());
            return false;
        }
    }
}

/* =====================================================
   KUMPULAN LINK WEBSITE
===================================================== */

if (!function_exists('user_get_links')) {
    function user_get_links(int $userId): array
    {
        global $conn;

        try {
            $stmt = $conn->prepare("SELECT * FROM user_links WHERE id_user = ? ORDER BY id_link DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("user_get_links error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('user_add_link')) {
    function user_add_link(int $userId, string $nama, string $url, ?string $deskripsi = ''): int
    {
        global $conn;

        $stmt = $conn->prepare("INSERT INTO user_links (id_user, nama_website, url_website, deskripsi) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $nama, $url, $deskripsi]);
        return (int) $conn->lastInsertId();
    }
}

if (!function_exists('user_update_link')) {
    function user_update_link(int $userId, int $linkId, string $nama, string $url, ?string $deskripsi = ''): bool
    {
        global $conn;

        $chk = $conn->prepare("SELECT id_link FROM user_links WHERE id_link = ? AND id_user = ?");
        $chk->execute([$linkId, $userId]);
        if (!$chk->fetchColumn()) {
            return false;
        }

        $stmt = $conn->prepare("UPDATE user_links SET nama_website = ?, url_website = ?, deskripsi = ? WHERE id_link = ? AND id_user = ?");
        $stmt->execute([$nama, $url, $deskripsi, $linkId, $userId]);
        return true;
    }
}

if (!function_exists('user_delete_link')) {
    function user_delete_link(int $userId, int $linkId): bool
    {
        global $conn;

        $stmt = $conn->prepare("DELETE FROM user_links WHERE id_link = ? AND id_user = ?");
        $stmt->execute([$linkId, $userId]);
        return $stmt->rowCount() > 0;
    }
}

/* =====================================================
   KUMPULAN LINK DATA / DOKUMEN
===================================================== */

if (!function_exists('user_get_data_links')) {
    function user_get_data_links(int $userId): array
    {
        global $conn;

        try {
            $stmt = $conn->prepare("SELECT * FROM user_data_links WHERE id_user = ? ORDER BY id_data_link DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("user_get_data_links error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('user_add_data_link')) {
    function user_add_data_link(int $userId, string $nama, string $url, string $jenis, ?string $deskripsi = ''): int
    {
        global $conn;

        $validJenis = ['Google Sheets', 'Google Docs', 'Excel', 'Word', 'PDF', 'Lainnya'];
        if (!in_array($jenis, $validJenis, true)) {
            $jenis = 'Lainnya';
        }

        $stmt = $conn->prepare("INSERT INTO user_data_links (id_user, nama_dokumen, url_dokumen, jenis_dokumen, deskripsi) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $nama, $url, $jenis, $deskripsi]);
        return (int) $conn->lastInsertId();
    }
}

if (!function_exists('user_update_data_link')) {
    function user_update_data_link(int $userId, int $dataLinkId, string $nama, string $url, string $jenis, ?string $deskripsi = ''): bool
    {
        global $conn;

        $chk = $conn->prepare("SELECT id_data_link FROM user_data_links WHERE id_data_link = ? AND id_user = ?");
        $chk->execute([$dataLinkId, $userId]);
        if (!$chk->fetchColumn()) {
            return false;
        }

        $validJenis = ['Google Sheets', 'Google Docs', 'Excel', 'Word', 'PDF', 'Lainnya'];
        if (!in_array($jenis, $validJenis, true)) {
            $jenis = 'Lainnya';
        }

        $stmt = $conn->prepare("UPDATE user_data_links SET nama_dokumen = ?, url_dokumen = ?, jenis_dokumen = ?, deskripsi = ? WHERE id_data_link = ? AND id_user = ?");
        $stmt->execute([$nama, $url, $jenis, $deskripsi, $dataLinkId, $userId]);
        return true;
    }
}

if (!function_exists('user_delete_data_link')) {
    function user_delete_data_link(int $userId, int $dataLinkId): bool
    {
        global $conn;

        $stmt = $conn->prepare("DELETE FROM user_data_links WHERE id_data_link = ? AND id_user = ?");
        $stmt->execute([$dataLinkId, $userId]);
        return $stmt->rowCount() > 0;
    }
}

/* =====================================================
   INISIALISASI TABEL KATEGORI BARU OTOMATIS
===================================================== */

if (!function_exists('user_repo_init_tables')) {
    function user_repo_init_tables(): void
    {
        global $conn;
        static $initialized = false;
        if ($initialized) return;

        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS `user_mobile_apps` (
              `id_app` int NOT NULL AUTO_INCREMENT,
              `id_user` int NOT NULL,
              `nama_aplikasi` varchar(200) NOT NULL,
              `url_aplikasi` varchar(500) NOT NULL,
              `platform` varchar(50) NOT NULL DEFAULT 'Android',
              `deskripsi` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_app`),
              KEY `idx_app_user` (`id_user`),
              CONSTRAINT `fk_app_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $conn->exec("CREATE TABLE IF NOT EXISTS `user_drive_links` (
              `id_drive` int NOT NULL AUTO_INCREMENT,
              `id_user` int NOT NULL,
              `nama_folder` varchar(200) NOT NULL,
              `url_folder` varchar(500) NOT NULL,
              `jenis_drive` varchar(50) NOT NULL DEFAULT 'Google Drive',
              `deskripsi` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_drive`),
              KEY `idx_drive_user` (`id_user`),
              CONSTRAINT `fk_drive_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $conn->exec("CREATE TABLE IF NOT EXISTS `user_photo_links` (
              `id_photo` int NOT NULL AUTO_INCREMENT,
              `id_user` int NOT NULL,
              `judul_foto` varchar(200) NOT NULL,
              `url_foto` varchar(500) NOT NULL,
              `deskripsi` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_photo`),
              KEY `idx_photo_user` (`id_user`),
              CONSTRAINT `fk_photo_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $conn->exec("CREATE TABLE IF NOT EXISTS `user_other_links` (
              `id_other` int NOT NULL AUTO_INCREMENT,
              `id_user` int NOT NULL,
              `nama_link` varchar(200) NOT NULL,
              `url_link` varchar(500) NOT NULL,
              `label` varchar(100) DEFAULT 'Referensi',
              `deskripsi` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id_other`),
              KEY `idx_other_user` (`id_user`),
              CONSTRAINT `fk_other_user` FOREIGN KEY (`id_user`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $initialized = true;
        } catch (Exception $e) {
            error_log("user_repo_init_tables error: " . $e->getMessage());
        }
    }
}

user_repo_init_tables();

/* =====================================================
   KUMPULAN APLIKASI MOBILE
===================================================== */

if (!function_exists('user_get_mobile_apps')) {
    function user_get_mobile_apps(int $userId): array
    {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM user_mobile_apps WHERE id_user = ? ORDER BY id_app DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("user_get_mobile_apps error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('user_add_mobile_app')) {
    function user_add_mobile_app(int $userId, string $nama, string $url, string $platform, ?string $deskripsi = ''): int
    {
        global $conn;
        $validPlatform = ['Android', 'iOS', 'Android & iOS'];
        if (!in_array($platform, $validPlatform, true)) {
            $platform = 'Android';
        }
        $stmt = $conn->prepare("INSERT INTO user_mobile_apps (id_user, nama_aplikasi, url_aplikasi, platform, deskripsi) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $nama, $url, $platform, $deskripsi]);
        return (int) $conn->lastInsertId();
    }
}

if (!function_exists('user_update_mobile_app')) {
    function user_update_mobile_app(int $userId, int $id, string $nama, string $url, string $platform, ?string $deskripsi = ''): bool
    {
        global $conn;
        $chk = $conn->prepare("SELECT id_app FROM user_mobile_apps WHERE id_app = ? AND id_user = ?");
        $chk->execute([$id, $userId]);
        if (!$chk->fetchColumn()) return false;

        $validPlatform = ['Android', 'iOS', 'Android & iOS'];
        if (!in_array($platform, $validPlatform, true)) {
            $platform = 'Android';
        }
        $stmt = $conn->prepare("UPDATE user_mobile_apps SET nama_aplikasi = ?, url_aplikasi = ?, platform = ?, deskripsi = ? WHERE id_app = ? AND id_user = ?");
        $stmt->execute([$nama, $url, $platform, $deskripsi, $id, $userId]);
        return true;
    }
}

if (!function_exists('user_delete_mobile_app')) {
    function user_delete_mobile_app(int $userId, int $id): bool
    {
        global $conn;
        $stmt = $conn->prepare("DELETE FROM user_mobile_apps WHERE id_app = ? AND id_user = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}

/* =====================================================
   LINK DRIVE (FOLDER)
===================================================== */

if (!function_exists('user_get_drive_links')) {
    function user_get_drive_links(int $userId): array
    {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM user_drive_links WHERE id_user = ? ORDER BY id_drive DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("user_get_drive_links error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('user_add_drive_link')) {
    function user_add_drive_link(int $userId, string $nama, string $url, string $jenisDrive, ?string $deskripsi = ''): int
    {
        global $conn;
        $validDrive = ['Google Drive', 'OneDrive', 'Dropbox', 'Lainnya'];
        if (!in_array($jenisDrive, $validDrive, true)) {
            $jenisDrive = 'Google Drive';
        }
        $stmt = $conn->prepare("INSERT INTO user_drive_links (id_user, nama_folder, url_folder, jenis_drive, deskripsi) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $nama, $url, $jenisDrive, $deskripsi]);
        return (int) $conn->lastInsertId();
    }
}

if (!function_exists('user_update_drive_link')) {
    function user_update_drive_link(int $userId, int $id, string $nama, string $url, string $jenisDrive, ?string $deskripsi = ''): bool
    {
        global $conn;
        $chk = $conn->prepare("SELECT id_drive FROM user_drive_links WHERE id_drive = ? AND id_user = ?");
        $chk->execute([$id, $userId]);
        if (!$chk->fetchColumn()) return false;

        $validDrive = ['Google Drive', 'OneDrive', 'Dropbox', 'Lainnya'];
        if (!in_array($jenisDrive, $validDrive, true)) {
            $jenisDrive = 'Google Drive';
        }
        $stmt = $conn->prepare("UPDATE user_drive_links SET nama_folder = ?, url_folder = ?, jenis_drive = ?, deskripsi = ? WHERE id_drive = ? AND id_user = ?");
        $stmt->execute([$nama, $url, $jenisDrive, $deskripsi, $id, $userId]);
        return true;
    }
}

if (!function_exists('user_delete_drive_link')) {
    function user_delete_drive_link(int $userId, int $id): bool
    {
        global $conn;
        $stmt = $conn->prepare("DELETE FROM user_drive_links WHERE id_drive = ? AND id_user = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}

/* =====================================================
   LINK FOTO
===================================================== */

if (!function_exists('user_get_photo_links')) {
    function user_get_photo_links(int $userId): array
    {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM user_photo_links WHERE id_user = ? ORDER BY id_photo DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("user_get_photo_links error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('user_add_photo_link')) {
    function user_add_photo_link(int $userId, string $judul, string $url, ?string $deskripsi = ''): int
    {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO user_photo_links (id_user, judul_foto, url_foto, deskripsi) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $judul, $url, $deskripsi]);
        return (int) $conn->lastInsertId();
    }
}

if (!function_exists('user_update_photo_link')) {
    function user_update_photo_link(int $userId, int $id, string $judul, string $url, ?string $deskripsi = ''): bool
    {
        global $conn;
        $chk = $conn->prepare("SELECT id_photo FROM user_photo_links WHERE id_photo = ? AND id_user = ?");
        $chk->execute([$id, $userId]);
        if (!$chk->fetchColumn()) return false;

        $stmt = $conn->prepare("UPDATE user_photo_links SET judul_foto = ?, url_foto = ?, deskripsi = ? WHERE id_photo = ? AND id_user = ?");
        $stmt->execute([$judul, $url, $deskripsi, $id, $userId]);
        return true;
    }
}

if (!function_exists('user_delete_photo_link')) {
    function user_delete_photo_link(int $userId, int $id): bool
    {
        global $conn;
        $stmt = $conn->prepare("DELETE FROM user_photo_links WHERE id_photo = ? AND id_user = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}

/* =====================================================
   LINK LAINNYA
===================================================== */

if (!function_exists('user_get_other_links')) {
    function user_get_other_links(int $userId): array
    {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM user_other_links WHERE id_user = ? ORDER BY id_other DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("user_get_other_links error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('user_add_other_link')) {
    function user_add_other_link(int $userId, string $nama, string $url, ?string $label = 'Referensi', ?string $deskripsi = ''): int
    {
        global $conn;
        if (empty($label)) $label = 'Referensi';
        $stmt = $conn->prepare("INSERT INTO user_other_links (id_user, nama_link, url_link, label, deskripsi) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $nama, $url, $label, $deskripsi]);
        return (int) $conn->lastInsertId();
    }
}

if (!function_exists('user_update_other_link')) {
    function user_update_other_link(int $userId, int $id, string $nama, string $url, ?string $label = 'Referensi', ?string $deskripsi = ''): bool
    {
        global $conn;
        $chk = $conn->prepare("SELECT id_other FROM user_other_links WHERE id_other = ? AND id_user = ?");
        $chk->execute([$id, $userId]);
        if (!$chk->fetchColumn()) return false;

        if (empty($label)) $label = 'Referensi';
        $stmt = $conn->prepare("UPDATE user_other_links SET nama_link = ?, url_link = ?, label = ?, deskripsi = ? WHERE id_other = ? AND id_user = ?");
        $stmt->execute([$nama, $url, $label, $deskripsi, $id, $userId]);
        return true;
    }
}

if (!function_exists('user_delete_other_link')) {
    function user_delete_other_link(int $userId, int $id): bool
    {
        global $conn;
        $stmt = $conn->prepare("DELETE FROM user_other_links WHERE id_other = ? AND id_user = ?");
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}

/* =====================================================
   RINGKASAN TOTAL KOLEKSI REPOSITORI PRIVATE
===================================================== */

if (!function_exists('user_get_all_private_counts')) {
    function user_get_all_private_counts(int $userId): array
    {
        return [
            'favorite'   => count(user_get_favorite_ids($userId)),
            'website'    => count(user_get_links($userId)),
            'data'       => count(user_get_data_links($userId)),
            'mobile_app' => count(user_get_mobile_apps($userId)),
            'drive'      => count(user_get_drive_links($userId)),
            'photo'      => count(user_get_photo_links($userId)),
            'other'      => count(user_get_other_links($userId)),
        ];
    }
}
