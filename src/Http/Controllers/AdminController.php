<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LoginThrottleService;
use App\Services\Mailer;
use App\Services\PasswordService;
use App\Support\Session;
use PDO;

class AdminController extends Controller
{
    /**
     * Pastikan hanya ADMIN IT yang bisa mengakses modul admin/user
     */
    private function authorizeAdminIt(): void
    {
        $user = $this->user();
        if (!$user || ($user['role'] ?? '') !== 'ADMIN IT') {
            $this->redirect('index.php');
        }
    }

    /**
     * Halaman Kelola Administrator & Pengguna dengan pencarian, filter, sorting, dan paginasi
     */
    public function manageAdmin(): string
    {
        $this->authorizeAdminIt();
        $db = $this->db();

        $search = trim((string)($_GET['search'] ?? ''));
        $roleFilter = trim((string)($_GET['role'] ?? ''));
        $statusFilter = trim((string)($_GET['status'] ?? ''));

        $allowedSorts = ['username', 'name', 'role', 'status', 'id'];
        $sort = in_array($_GET['sort'] ?? '', $allowedSorts, true) ? $_GET['sort'] : 'id';
        $order = (strtolower((string)($_GET['order'] ?? '')) === 'desc') ? 'desc' : 'asc';

        $allowedPerPage = [10, 25, 50];
        $rawPerPage = (int)($_GET['per_page'] ?? 10);
        $perPage = in_array($rawPerPage, $allowedPerPage, true) ? $rawPerPage : 10;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $where = ["1=1"];
        $params = [];

        if ($search !== '') {
            $where[] = "(a.username LIKE ? OR a.email LIKE ? OR a.first_name LIKE ? OR a.last_name LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if ($roleFilter !== '' && in_array($roleFilter, ['ADMIN IT', 'ADMIN DASHBOARD', 'USER APP PORTAL'], true)) {
            $where[] = "a.role = ?";
            $params[] = $roleFilter;
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['approved', 'pending'], true)) {
            $where[] = "a.status = ?";
            $params[] = $statusFilter;
        }

        $whereSql = implode(" AND ", $where);

        $stmtCount = $db->prepare("SELECT COUNT(*) FROM admin a WHERE {$whereSql}");
        $stmtCount->execute($params);
        $totalItems = (int)$stmtCount->fetchColumn();

        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $sortColumn = match ($sort) {
            'username' => 'a.username',
            'name'     => 'a.first_name',
            'role'     => 'a.role',
            'status'   => 'a.status',
            default    => 'a.id_admin',
        };
        $orderDir = ($order === 'desc') ? 'DESC' : 'ASC';

        $dataSql = "
            SELECT a.id_admin, a.first_name, a.last_name, a.username, a.email, a.role, a.unit_id,
                   a.status, a.is_active, a.must_change_password, a.created_at, a.last_login_at,
                   u.nama AS unit_name
            FROM admin a
            LEFT JOIN units u ON a.unit_id = u.id
            WHERE {$whereSql}
            ORDER BY {$sortColumn} {$orderDir}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $db->prepare($dataSql);
        $stmt->execute($params);
        $admins = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmtUnits = $db->query("SELECT id, nama FROM units ORDER BY id ASC");
        $units = $stmtUnits ? $stmtUnits->fetchAll(PDO::FETCH_ASSOC) : [];

        $hasFilters = ($search !== '' || $roleFilter !== '' || $statusFilter !== '');

        return $this->render('admin/manage_admin', [
            'title'        => 'Kelola Admin',
            'admins'       => $admins,
            'units'        => $units,
            'search'       => $search,
            'roleFilter'   => $roleFilter,
            'statusFilter' => $statusFilter,
            'sort'         => $sort,
            'order'        => $order,
            'hasFilters'   => $hasFilters,
            'pagination'   => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'from'        => $totalItems > 0 ? $offset + 1 : 0,
                'to'          => min($offset + $perPage, $totalItems),
            ],
            'user'         => $this->user(),
        ]);
    }

    /**
     * Tambah Pengguna / Admin Baru secara langsung oleh Admin IT
     */
    public function storeUser(): void
    {
        $this->authorizeAdminIt();

        if (!csrf_verify()) {
            flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa.');
            $this->redirect('kelola_admin.php');
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $role = (string)($_POST['role'] ?? 'ADMIN DASHBOARD');
        $unitId = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;
        $passwordRaw = (string)($_POST['password'] ?? '');

        $allowedRoles = ['ADMIN IT', 'ADMIN DASHBOARD', 'USER APP PORTAL'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'ADMIN DASHBOARD';
        }

        if ($username === '' || !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            flash('error', 'Username wajib diisi 3-50 karakter alfanumerik/underscore.');
            $this->redirect('kelola_admin.php');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Format email tidak valid.');
            $this->redirect('kelola_admin.php');
        }

        $db = $this->db();

        $stmtCheck = $db->prepare("SELECT id_admin FROM admin WHERE username = ? OR email = ? LIMIT 1");
        $stmtCheck->execute([$username, $email]);
        if ($stmtCheck->fetch()) {
            flash('error', 'Username atau email sudah digunakan oleh akun lain.');
            $this->redirect('kelola_admin.php');
        }

        $isTempPassword = false;
        if ($passwordRaw === '') {
            $tempPassword = 'Bps!' . bin2hex(random_bytes(4));
            $passwordToHash = $tempPassword;
            $mustChangePassword = 1;
            $isTempPassword = true;
        } else {
            $policyErr = PasswordService::validatePolicy($passwordRaw, $username);
            if ($policyErr !== null) {
                flash('error', $policyErr);
                $this->redirect('kelola_admin.php');
            }
            $passwordToHash = $passwordRaw;
            $mustChangePassword = 0;
            $tempPassword = '';
        }

        $hash = PasswordService::hash($passwordToHash);

        $stmtInsert = $db->prepare("
            INSERT INTO admin (
                first_name, last_name, username, email, password,
                status, role, unit_id, is_active, must_change_password, email_verified
            ) VALUES (?, ?, ?, ?, ?, 'approved', ?, ?, 1, ?, 1)
        ");
        $stmtInsert->execute([
            $firstName ?: null,
            $lastName ?: null,
            $username,
            $email,
            $hash,
            $role,
            $unitId,
            $mustChangePassword,
        ]);
        $newId = (int)$db->lastInsertId();

        audit_log('create', 'admin', $newId, [
            'username' => $username,
            'email'    => $email,
            'role'     => $role,
            'unit_id'  => $unitId,
        ]);

        if ($isTempPassword) {
            flash('success', "Akun '{$username}' berhasil dibuat. Password sementara: <strong>{$tempPassword}</strong> (Wajib diganti saat login pertama).");
        } else {
            flash('success', "Akun '{$username}' berhasil dibuat dengan role '{$role}'.");
        }

        $this->redirect('kelola_admin.php');
    }

    /**
     * Hitung jumlah ADMIN IT yang sedang aktif
     */
    private function countActiveAdminIt(PDO $db): int
    {
        $stmt = $db->query("
            SELECT COUNT(*) 
            FROM admin 
            WHERE role = 'ADMIN IT' 
              AND status = 'approved' 
              AND (is_active = 1 OR is_active IS NULL)
        ");
        return (int)($stmt ? $stmt->fetchColumn() : 0);
    }

    /**
     * Proses aksi status, role, reset password, dan aktivasi admin
     */
    public function actionAdmin(): void
    {
        $this->authorizeAdminIt();

        if (!csrf_verify()) {
            flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa.');
            $this->redirect('kelola_admin.php');
        }

        $idAdmin = (int)($_POST['id_admin'] ?? 0);
        $action = (string)($_POST['action'] ?? '');
        $currentAdminId = (int)($this->user()['id'] ?? 0);

        if ($idAdmin <= 0) {
            flash('error', 'ID admin tidak valid.');
            $this->redirect('kelola_admin.php');
        }

        $db = $this->db();

        switch ($action) {
            case 'approve':
                $stmt = $db->prepare("
                    UPDATE admin
                    SET status = 'approved', role = 'ADMIN DASHBOARD', is_active = 1
                    WHERE id_admin = ? AND status = 'pending'
                ");
                $stmt->execute([$idAdmin]);
                if ($stmt->rowCount() > 0) {
                    audit_log('approve', 'admin', $idAdmin);
                    flash('success', 'Akun berhasil disetujui sebagai ADMIN DASHBOARD.');
                } else {
                    flash('error', 'Akun tidak ditemukan atau sudah diproses.');
                }
                break;

            case 'reject':
                $stmt = $db->prepare("
                    UPDATE admin
                    SET status = 'rejected', is_active = 0
                    WHERE id_admin = ? AND status = 'pending'
                ");
                $stmt->execute([$idAdmin]);
                if ($stmt->rowCount() > 0) {
                    audit_log('reject', 'admin', $idAdmin);
                    flash('success', 'Akun berhasil ditolak.');
                } else {
                    flash('error', 'Akun tidak ditemukan atau sudah diproses.');
                }
                break;

            case 'update_role_unit':
                $newRole = (string)($_POST['new_role'] ?? '');
                $newUnitId = !empty($_POST['new_unit_id']) ? (int)$_POST['new_unit_id'] : null;

                if (!in_array($newRole, ['ADMIN IT', 'ADMIN DASHBOARD', 'USER APP PORTAL'], true)) {
                    flash('error', 'Role yang dipilih tidak valid.');
                    break;
                }

                if ($idAdmin === $currentAdminId && $newRole !== 'ADMIN IT') {
                    if ($this->countActiveAdminIt($db) <= 1) {
                        flash('error', 'Tidak dapat menurunkan role akun Anda sendiri karena Anda adalah ADMIN IT terakhir yang aktif.');
                        break;
                    }
                }

                $stmtUpdate = $db->prepare("UPDATE admin SET role = ?, unit_id = ? WHERE id_admin = ?");
                $stmtUpdate->execute([$newRole, $newUnitId, $idAdmin]);
                audit_log('update_role_unit', 'admin', $idAdmin, ['role' => $newRole, 'unit_id' => $newUnitId]);
                flash('success', 'Role dan Unit kerja admin berhasil diperbarui.');
                break;

            case 'toggle_active':
                $targetActive = (int)($_POST['is_active'] ?? 0);

                if ($idAdmin === $currentAdminId && $targetActive === 0) {
                    if ($this->countActiveAdminIt($db) <= 1) {
                        flash('error', 'Tidak dapat menonaktifkan akun Anda sendiri karena Anda adalah ADMIN IT terakhir yang aktif.');
                        break;
                    }
                }

                $stmt = $db->prepare("UPDATE admin SET is_active = ? WHERE id_admin = ?");
                $stmt->execute([$targetActive, $idAdmin]);

                if ($targetActive === 0) {
                    try {
                        $db->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$idAdmin]);
                    } catch (\Throwable) {}
                    audit_log('deactivate', 'admin', $idAdmin);
                    flash('success', 'Akun berhasil dinonaktifkan.');
                } else {
                    audit_log('activate', 'admin', $idAdmin);
                    flash('success', 'Akun berhasil diaktifkan kembali.');
                }
                break;

            case 'reset_password':
                $tempPassword = 'Bps!' . bin2hex(random_bytes(4));
                $hash = PasswordService::hash($tempPassword);

                $stmt = $db->prepare("
                    UPDATE admin
                    SET password = ?, must_change_password = 1, failed_attempts = 0, locked_until = NULL
                    WHERE id_admin = ?
                ");
                $stmt->execute([$hash, $idAdmin]);

                try {
                    $db->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$idAdmin]);
                } catch (\Throwable) {}

                $stmtUser = $db->prepare("SELECT username FROM admin WHERE id_admin = ?");
                $stmtUser->execute([$idAdmin]);
                $uName = $stmtUser->fetchColumn() ?: "ID #{$idAdmin}";

                audit_log('reset_password', 'admin', $idAdmin);
                flash('success', "Password sementara untuk pengguna <strong>" . e((string)$uName) . "</strong> berhasil dibuat: <strong>{$tempPassword}</strong>. Pengguna wajib mengganti password saat login.");
                break;

            case 'make_main':
                if ($idAdmin === $currentAdminId) {
                    flash('error', 'Akun Anda sudah menjadi ADMIN IT.');
                    break;
                }
                $stmt = $db->prepare("SELECT role FROM admin WHERE id_admin = ? AND status = 'approved' LIMIT 1");
                $stmt->execute([$idAdmin]);
                $targetRole = $stmt->fetchColumn();

                if (!$targetRole || $targetRole !== 'ADMIN DASHBOARD') {
                    flash('error', 'Hanya ADMIN DASHBOARD yang dapat dijadikan ADMIN IT.');
                    break;
                }

                if ($this->countActiveAdminIt($db) >= 3) {
                    flash('error', 'Maksimal 3 ADMIN IT. Tidak dapat menambahkan ADMIN IT lagi.');
                    break;
                }

                $stmtUpdate = $db->prepare("
                    UPDATE admin SET role = 'ADMIN IT'
                    WHERE id_admin = ? AND status = 'approved' AND role = 'ADMIN DASHBOARD'
                ");
                $stmtUpdate->execute([$idAdmin]);
                audit_log('make_main', 'admin', $idAdmin);
                flash('success', 'Admin berhasil dijadikan ADMIN IT.');
                break;

            case 'make_regular':
                if ($idAdmin === $currentAdminId) {
                    if ($this->countActiveAdminIt($db) <= 1) {
                        flash('error', 'Tidak dapat menurunkan role akun Anda sendiri karena Anda adalah ADMIN IT terakhir yang aktif.');
                        break;
                    }
                }
                $stmt = $db->prepare("
                    UPDATE admin SET role = 'ADMIN DASHBOARD'
                    WHERE id_admin = ? AND status = 'approved' AND role = 'ADMIN IT'
                ");
                $stmt->execute([$idAdmin]);
                if ($stmt->rowCount() > 0) {
                    audit_log('make_regular', 'admin', $idAdmin);
                    flash('success', 'Admin berhasil dijadikan ADMIN DASHBOARD.');
                } else {
                    flash('error', 'Admin tidak ditemukan atau role tidak dapat diubah.');
                }
                break;

            case 'delete':
                if ($idAdmin === $currentAdminId) {
                    flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
                    break;
                }

                $stmtRole = $db->prepare("SELECT role, is_active FROM admin WHERE id_admin = ?");
                $stmtRole->execute([$idAdmin]);
                $targetAdmin = $stmtRole->fetch(PDO::FETCH_ASSOC);

                if ($targetAdmin && $targetAdmin['role'] === 'ADMIN IT' && (int)($targetAdmin['is_active'] ?? 1) === 1) {
                    if ($this->countActiveAdminIt($db) <= 1) {
                        flash('error', 'Tidak dapat menghapus ADMIN IT terakhir yang aktif.');
                        break;
                    }
                }

                try {
                    $db->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$idAdmin]);
                } catch (\Throwable) {}

                $stmtDelete = $db->prepare("DELETE FROM admin WHERE id_admin = ?");
                $stmtDelete->execute([$idAdmin]);
                if ($stmtDelete->rowCount() > 0) {
                    audit_log('delete', 'admin', $idAdmin);
                    flash('success', 'Admin berhasil dihapus.');
                } else {
                    flash('error', 'Admin gagal dihapus.');
                }
                break;

            default:
                flash('error', 'Aksi tidak valid.');
                break;
        }

        $this->redirect('kelola_admin.php');
    }

    /**
     * Halaman Kelola Pengguna Portal dengan pencarian, filter, sorting, dan paginasi
     */
    public function manageUser(): string
    {
        $this->authorizeAdminIt();

        if (($_GET['action'] ?? '') === 'template') {
            $this->downloadTemplateCsv();
        }

        $db = $this->db();

        $search = trim((string)($_GET['search'] ?? ''));
        $unitFilter = isset($_GET['unit_id']) && is_numeric($_GET['unit_id']) && (int)$_GET['unit_id'] > 0 ? (int)$_GET['unit_id'] : null;
        $statusFilter = trim((string)($_GET['status'] ?? ''));

        $allowedSorts = ['username', 'name', 'unit', 'status', 'created_at', 'id'];
        $sort = in_array($_GET['sort'] ?? '', $allowedSorts, true) ? $_GET['sort'] : 'id';
        $order = (strtolower((string)($_GET['order'] ?? '')) === 'asc') ? 'asc' : 'desc';

        $allowedPerPage = [10, 25, 50];
        $rawPerPage = (int)($_GET['per_page'] ?? 10);
        $perPage = in_array($rawPerPage, $allowedPerPage, true) ? $rawPerPage : 10;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $where = ["a.role = 'USER APP PORTAL'"];
        $params = [];

        if ($search !== '') {
            $where[] = "(a.username LIKE ? OR a.email LIKE ? OR a.first_name LIKE ? OR a.last_name LIKE ? OR u.nama LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if ($unitFilter !== null) {
            $where[] = "a.unit_id = ?";
            $params[] = $unitFilter;
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['approved', 'pending'], true)) {
            $where[] = "a.status = ?";
            $params[] = $statusFilter;
        }

        $whereSql = implode(" AND ", $where);

        $stmtCount = $db->prepare("SELECT COUNT(*) FROM admin a LEFT JOIN units u ON a.unit_id = u.id WHERE {$whereSql}");
        $stmtCount->execute($params);
        $totalItems = (int)$stmtCount->fetchColumn();

        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $sortColumn = match ($sort) {
            'username'   => 'a.username',
            'name'       => 'a.first_name',
            'unit'       => 'u.nama',
            'status'     => 'a.status',
            'created_at' => 'a.created_at',
            default      => 'a.id_admin',
        };
        $orderDir = ($order === 'asc') ? 'ASC' : 'DESC';

        $dataSql = "
            SELECT a.id_admin, a.username, a.first_name, a.last_name, a.email, a.status, a.role,
                   a.email_verified, a.must_change_password, a.created_at, u.nama AS unit_name
            FROM admin a
            LEFT JOIN units u ON a.unit_id = u.id
            WHERE {$whereSql}
            ORDER BY {$sortColumn} {$orderDir}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $db->prepare($dataSql);
        $stmt->execute($params);
        $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmtUnits = $db->query("SELECT id, nama FROM units ORDER BY id ASC");
        $units = $stmtUnits ? $stmtUnits->fetchAll(PDO::FETCH_ASSOC) : [];

        $importedCredentials = Session::get('imported_credentials') ?? [];
        Session::remove('imported_credentials');

        $hasFilters = ($search !== '' || $unitFilter !== null || $statusFilter !== '');

        return $this->render('admin/manage_user', [
            'title'               => 'Kelola User Portal',
            'users'               => $users,
            'units'               => $units,
            'search'              => $search,
            'unitFilter'          => $unitFilter,
            'statusFilter'        => $statusFilter,
            'sort'                => $sort,
            'order'               => $order,
            'hasFilters'          => $hasFilters,
            'pagination'          => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'from'        => $totalItems > 0 ? $offset + 1 : 0,
                'to'          => min($offset + $perPage, $totalItems),
            ],
            'importedCredentials' => $importedCredentials,
            'error'               => '',
            'email_value'         => '',
            'user'                => $this->user(),
        ]);
    }

    /**
     * Unduh template CSV untuk impor akun pegawai
     */
    public function downloadTemplateCsv(): void
    {
        $this->authorizeAdminIt();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_impor_pegawai.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output !== false) {
            fputcsv($output, ['username', 'nama', 'unit', 'email']);
            fputcsv($output, ['budi.santoso', 'Budi Santoso', 'Fungsi Statistik Sosial', 'budi.santoso@bps.go.id']);
            fputcsv($output, ['siti.aminah', 'Siti Aminah', 'Subbagian Umum', 'siti.aminah@bps.go.id']);
            fputcsv($output, ['ahmad.dahlan', 'Ahmad Dahlan', 'Fungsi Statistik Produksi', 'ahmad.dahlan@bps.go.id']);
            fclose($output);
        }
        exit;
    }

    /**
     * Impor Akun Pegawai (Role USER APP PORTAL) melalui CSV (Task 7.7)
     */
    public function importUsersCsv(): void
    {
        $this->authorizeAdminIt();

        if (!csrf_verify()) {
            flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa.');
            $this->redirect('kelola_user.php');
        }

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Silakan pilih file CSV yang valid untuk diunggah.');
            $this->redirect('kelola_user.php');
        }

        $file = $_FILES['csv_file'];
        $tmpName = (string)$file['tmp_name'];
        $fileName = (string)$file['name'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            flash('error', 'Hanya berkas berformat .csv yang diperbolehkan.');
            $this->redirect('kelola_user.php');
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            flash('error', 'Ukuran file CSV maksimal 2 MB.');
            $this->redirect('kelola_user.php');
        }

        $handle = fopen($tmpName, 'r');
        if ($handle === false) {
            flash('error', 'Gagal membaca berkas CSV.');
            $this->redirect('kelola_user.php');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            flash('error', 'Berkas CSV kosong.');
            $this->redirect('kelola_user.php');
        }
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false || empty($header)) {
            fclose($handle);
            flash('error', 'Header berkas CSV tidak terbaca.');
            $this->redirect('kelola_user.php');
        }

        $headerMap = [];
        foreach ($header as $idx => $colName) {
            $normalized = strtolower(trim((string)$colName));
            $normalized = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $normalized) ?? $normalized;
            $headerMap[$normalized] = $idx;
        }

        $usernameIdx = $headerMap['username'] ?? null;
        $namaIdx = $headerMap['nama'] ?? $headerMap['name'] ?? $headerMap['nama_lengkap'] ?? null;
        $unitIdx = $headerMap['unit'] ?? $headerMap['unit_kerja'] ?? null;
        $emailIdx = $headerMap['email'] ?? null;

        if ($usernameIdx === null || $namaIdx === null) {
            fclose($handle);
            flash('error', 'Format header CSV wajib memuat kolom: username, nama, unit.');
            $this->redirect('kelola_user.php');
        }

        $db = $this->db();
        $stmtUnits = $db->query("SELECT id, nama FROM units");
        $unitsList = $stmtUnits ? $stmtUnits->fetchAll(PDO::FETCH_ASSOC) : [];

        $imported = [];
        $skipped = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) {
                continue;
            }

            $username = trim((string)($row[$usernameIdx] ?? ''));
            $nama = trim((string)($row[$namaIdx] ?? ''));
            $unitStr = $unitIdx !== null ? trim((string)($row[$unitIdx] ?? '')) : '';
            $email = $emailIdx !== null ? trim((string)($row[$emailIdx] ?? '')) : '';

            if ($username === '' || !preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) {
                $skipped[] = "Baris {$rowNum}: Username '{$username}' tidak valid (wajib 3-50 karakter alfanumerik/titik/underscore).";
                continue;
            }

            if ($nama === '') {
                $nama = $username;
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = strtolower($username) . '@bps.go.id';
            }

            $stmtCheck = $db->prepare("SELECT id_admin FROM admin WHERE username = ? OR email = ? LIMIT 1");
            $stmtCheck->execute([$username, $email]);
            if ($stmtCheck->fetch()) {
                $skipped[] = "Baris {$rowNum}: Akun dengan username '{$username}' atau email '{$email}' sudah terdaftar.";
                continue;
            }

            $matchedUnitId = null;
            $matchedUnitName = 'Subbagian Umum';
            if ($unitStr !== '') {
                if (is_numeric($unitStr)) {
                    $uId = (int)$unitStr;
                    foreach ($unitsList as $u) {
                        if ((int)$u['id'] === $uId) {
                            $matchedUnitId = $uId;
                            $matchedUnitName = (string)$u['nama'];
                            break;
                        }
                    }
                } else {
                    foreach ($unitsList as $u) {
                        if (stripos((string)$u['nama'], $unitStr) !== false || stripos($unitStr, (string)$u['nama']) !== false) {
                            $matchedUnitId = (int)$u['id'];
                            $matchedUnitName = (string)$u['nama'];
                            break;
                        }
                    }
                }
            }
            if ($matchedUnitId === null && !empty($unitsList)) {
                $matchedUnitId = (int)$unitsList[0]['id'];
                $matchedUnitName = (string)$unitsList[0]['nama'];
            }

            $parts = explode(' ', $nama, 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';

            $tempPassword = 'Bps!' . bin2hex(random_bytes(4));
            $hash = PasswordService::hash($tempPassword);

            $stmtIns = $db->prepare("
                INSERT INTO admin (
                    first_name, last_name, username, email, password,
                    status, role, unit_id, is_active, must_change_password, email_verified
                ) VALUES (?, ?, ?, ?, ?, 'approved', 'USER APP PORTAL', ?, 1, 1, 1)
            ");
            $stmtIns->execute([
                $firstName,
                $lastName ?: null,
                $username,
                $email,
                $hash,
                $matchedUnitId,
            ]);

            $imported[] = [
                'username'      => $username,
                'name'          => $nama,
                'unit'          => $matchedUnitName,
                'email'         => $email,
                'temp_password' => $tempPassword,
            ];
        }
        fclose($handle);

        if (!empty($imported)) {
            audit_log('import_csv', 'user', null, [
                'count'     => count($imported),
                'usernames' => array_column($imported, 'username'),
            ]);
            Session::set('imported_credentials', $imported);
            $msg = 'Berhasil mengimpor ' . count($imported) . ' akun pegawai.';
            if (!empty($skipped)) {
                $msg .= ' (' . count($skipped) . ' baris dilewati).';
            }
            flash('success', $msg);
        } else {
            flash('error', 'Tidak ada akun pegawai yang berhasil diimpor. ' . implode(' ', array_slice($skipped, 0, 3)));
        }

        $this->redirect('kelola_user.php');
    }

    /**
     * Proses undang user baru
     */
    public function inviteUser(): string
    {
        $this->authorizeAdminIt();

        if (($_POST['action'] ?? '') === 'import_csv') {
            $this->importUsersCsv();
            return '';
        }

        $email = trim($_POST['email'] ?? '');
        $db = $this->db();

        $stmt = $db->query("
            SELECT id_admin, username, email, status, role, email_verified, created_at
            FROM admin
            WHERE role = 'USER APP PORTAL'
            ORDER BY id_admin DESC
        ");
        $users = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        if (!csrf_verify()) {
            return $this->render('admin/manage_user', [
                'title'       => 'Kelola User Portal',
                'users'       => $users,
                'error'       => 'Sesi keamanan tidak valid atau telah kedaluwarsa.',
                'email_value' => $email,
            ]);
        }

        if ($email === '') {
            return $this->render('admin/manage_user', [
                'title'       => 'Kelola User Portal',
                'users'       => $users,
                'error'       => 'Email wajib diisi.',
                'email_value' => '',
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('admin/manage_user', [
                'title'       => 'Kelola User Portal',
                'users'       => $users,
                'error'       => 'Format email tidak valid.',
                'email_value' => $email,
            ]);
        }

        $check = $db->prepare("SELECT id_admin FROM admin WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch(PDO::FETCH_ASSOC)) {
            return $this->render('admin/manage_user', [
                'title'       => 'Kelola User Portal',
                'users'       => $users,
                'error'       => 'Email tersebut sudah terdaftar di sistem.',
                'email_value' => $email,
            ]);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (LoginThrottleService::isEmailRateLimited($db, $email, $ip)) {
            return $this->render('admin/manage_user', [
                'title'       => 'Kelola User Portal',
                'users'       => $users,
                'error'       => 'Terlalu banyak permintaan pengiriman email ke alamat tersebut. Silakan coba lagi nanti.',
                'email_value' => $email,
            ]);
        }

        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 jam per Task 6.7

        $stmtInsert = $db->prepare("
            INSERT INTO admin (
                first_name, last_name, username, email, password,
                status, role, email_verified, verification_token, verification_expires
            ) VALUES (
                NULL, NULL, NULL, ?, NULL,
                'pending', 'USER APP PORTAL', 0, ?, ?
            )
        ");
        $stmtInsert->execute([$email, $hashedToken, $expires]);
        $newUserId = (int)$db->lastInsertId();

        LoginThrottleService::recordEmailVerificationAttempt($db, $email, $ip);
        audit_log('invite', 'user', $newUserId, ['email' => $email]);

        $sent = Mailer::sendVerificationEmail($email, 'Pengguna Portal', $rawToken, true);

        if ($sent) {
            flash('success', 'Akun user berhasil dibuat. Link verifikasi telah dikirim ke email tersebut.');
        } else {
            flash('warning', 'Akun berhasil dibuat, namun email verifikasi gagal dikirim.');
        }

        $this->redirect('kelola_user.php');
    }

    /**
     * Halaman Riwayat Audit Sistem (Task 7.3)
     */
    public function auditLog(): string
    {
        $this->authorizeAdminIt();
        $db = $this->db();

        $stmt = $db->query("
            SELECT al.id, al.user_id, al.action, al.entity, al.entity_id, al.meta, al.created_at,
                   a.username, a.first_name, a.last_name, a.role
            FROM audit_log al
            LEFT JOIN admin a ON al.user_id = a.id_admin
            ORDER BY al.id DESC
            LIMIT 150
        ");
        $logs = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return $this->render('admin/audit_log', [
            'title' => 'Riwayat Audit Sistem',
            'logs'  => $logs,
        ]);
    }
}
