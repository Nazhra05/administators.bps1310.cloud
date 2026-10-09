# Checklist QA Manual & Uji Lintas Aplikasi (App A & App B)

Dokumen ini memuat panduan pengujian manual antarmuka (QA Manual) dan verifikasi integrasi lintas aplikasi antara **App A** (`adminDashboard` / `administators.bps1310.cloud`) dan **App B** (`sipasti.bps1310.cloud`), sesuai pemenuhan **Task 8.5** dan **Task 8.6** pada `todo.md`.

---

## 1. Lingkungan Pengujian Lokal

| Komponen | Spesifikasi / Konfigurasi |
|---|---|
| **Web Server** | Apache 2.4 (XAMPP for Windows) |
| **VirtualHost** | `http://admin.test/` (Fallback: `http://localhost/adminDashboard/public/`) |
| **PHP Version** | PHP 8.3.x (PSR-12, error logging aktif ke `storage/logs/`) |
| **Database** | MariaDB 10.4+ (`bps_solsel_dev` untuk dev manual, `bps_solsel_test` untuk automated test) |
| **App B (Portal)** | Next.js Frontend (`http://localhost:3000/`) |
| **Header Integrasi** | `X-API-KEY: <API_KEY_APP_B>` |

---

## 2. Checklist QA Manual Antarmuka Dashboard (Task 8.5)

### 2.1 Autentikasi & Sesi
- [x] **Login Berhasil (Admin):** Masuk dengan username/password yang valid mengarahkan ke `dashboard/index.php`. Sesi aktif tercatat dengan cookie `HttpOnly; SameSite=Lax`.
- [x] **Login Gagal:** Input kredensial salah menampilkan pesan ramah seragam tanpa membocorkan apakah username atau password yang salah.
- [x] **Proteksi Brute-Force & Lockout:** 5 kali percobaan gagal berturut-turut mengunci akun selama 10 menit (HTTP 429).
- [x] **Logout:** Mengklik tombol Logout menghancurkan sesi di server, menghapus cookie sesi, dan mengarahkan kembali ke halaman login.
- [x] **Ganti Password:** Form ubah password di pengaturan memvalidasi password lama, mewajibkan password baru minimal 10 karakter, dan menyimpannya menggunakan hashing Bcrypt.

### 2.2 Hak Akses & Matriks Role
- [x] **Role Admin:** Memiliki akses penuh ke Dashboard, Kelola Layanan, Kelola Kategori, Kelola Pengguna, Pengaturan Sistem, dan Audit Log.
- [x] **Role User:** Tidak dapat mengakses halaman dashboard admin (ditolak dengan status 403 Forbidden). Akses khusus untuk konsumsi API portal pribadi.
- [x] **Leader Scope (Approval):** Workflow persetujuan layanan hanya dapat disetujui/ditolak oleh role Admin/Ketua Tim terkait.
- [x] **Last Admin Guard:** Sistem menolak menghapus atau menonaktifkan akun admin terakhir yang aktif di sistem.

### 2.3 Manajemen Layanan & Kategori
- [x] **CRUD Kategori:** Tambah, edit, dan arsipkan kategori. Kategori nonaktif (`is_active=0`) disembunyikan dari portal publik.
- [x] **CRUD Layanan:** Form tambah/edit layanan berfungsi dengan validasi field wajib, URL `http/https`, dan sanitasi alias pencarian.
- [x] **Alur Persetujuan (Workflow):**
  - Layanan baru disimpan sebagai `draft` atau diajukan sebagai `pending`.
  - Layanan `pending` tidak tampil di API publik App B.
  - Setelah disetujui Admin, status berubah menjadi `published` dan langsung muncul di katalog App B.
  - Penolakan layanan wajib menyertakan catatan penolakan (`rejection_notes`) yang tercatat di audit log.
- [x] **Ekspor & Impor CSV:**
  - Unduh template CSV menghasilkan format kolom yang tepat.
  - Ekspor CSV menyertakan UTF-8 BOM agar terbaca sempurna di Microsoft Excel.
  - Impor CSV memvalidasi baris demi baris, melaporkan nomor baris yang bermasalah, dan mencatat ringkasan aksi di audit log.

### 2.4 Keamanan Upload & Penanganan Berkas
- [x] **Validasi File Logo:** Menolak file selain JPG, PNG, dan WebP.
- [x] **Batas Ukuran:** Menolak file melebihi 2MB.
- [x] **Pembersihan Payload GD:** Gambar diolah ulang menggunakan library GD (`imagecreatefrom*` dan `image*`) untuk meremove metadata EXIF berbahaya atau polyglot PHP payload.
- [x] **Folder Upload Terproteksi:** Folder `public/uploads/` dilindungi `.htaccess` yang mematikan eksekusi script PHP.
- [x] **Fallback Inisial:** Jika logo tidak ada atau gagal dimuat, sistem menampilkan badge avatar inisial nama layanan secara otomatis.

### 2.5 Responsivitas, Aksesibilitas, dan UX
- [x] **Tampilan Mobile (375px & 768px):** Menu navigasi sidebar dapat dibuka/ditutup melalui tombol toggle responsif.
- [x] **Tabel Responsif:** Tabel data dibungkus container `.table-responsive` dengan scroll horizontal halus di perangkat layar sempit.
- [x] **Aksesibilitas (A11y):**
  - Terdapat tombol *Skip to Main Content* (`<a href="#mainContent" class="skip-link">`).
  - Semua elemen input form terhubung dengan elemen `<label for="...">`.
  - Elemen interaktif memiliki status fokus yang jelas (`:focus-visible`).
  - Atribut ARIA (`aria-expanded`, `aria-haspopup`) terpasang pada dropdown navigasi.
- [x] **Konsol JavaScript:** Nol error JavaScript di browser Developer Tools Console.
- [x] **Pencegahan Klik Ganda:** Tombol submit otomatis mendisabel dirinya sendiri dan menampilkan indikator loading saat proses pengiriman form.

### 2.6 Halaman Error Ramah Pengguna
- [x] **403 Forbidden:** Tampilan ramah pengguna tanpa stack trace teknis.
- [x] **404 Not Found:** Tampilan navigasi kembali yang jelas.
- [x] **500 Internal Error:** Menampilkan pesan aman bagi pengunjung dan menyertakan Request ID unik (`X-Request-Id`) untuk penelusuran log oleh tim IT.

---

## 3. Hasil Pengujian Lintas Aplikasi (App A & App B - Task 8.6)

Pengujian integrasi mensimulasikan pemanggilan App B (Next.js) ke App A backend API menggunakan header `X-API-KEY`:

### Skenario 1: Memuat Katalog Layanan & Kategori Publik
- **Endpoint:** `GET /api/layanan.php` & `GET /api/kategori.php` (Legacy) / `GET /api/v1/layanan` (V1).
- **Hasil Pengujian:**
  - Status code: `200 OK`.
  - Format respon: JSON envelope valid `{"status":"success","data":[...]}`.
  - Data yang dikembalikan **hanya** layanan berstatus `published` dengan kategori `is_active=1`.
  - URL ternormalisasi (`http://` atau `https://`).
- **Status:** **PASS** (Terverifikasi lewat `tests/api_contract.test.js` Test 1-3).

### Skenario 2: Login Pengguna / Admin dari Portal
- **Endpoint:** `POST /api/v1/auth/login` (atau `POST /api/auth/loginPortal.php`).
- **Hasil Pengujian:**
  - Kredensial valid menghasilkan token sesi `session_token` dengan masa aktif 8 jam yang tersimpan di tabel `sessions`.
  - Password lama dengan format SHA-256 otomatis ter-upgrade ke Bcrypt saat login berhasil.
- **Status:** **PASS** (Terverifikasi lewat `tests/functional_flow.test.js` Flow 3).

### Skenario 3: Logout dan Pembersihan Sesi
- **Endpoint:** `POST /api/v1/auth/logout`.
- **Hasil Pengujian:**
  - Sesi terkait di database dihapus seketika.
  - Request berikutnya dengan token sesi lama menghasilkan status `401 Unauthorized`.
- **Status:** **PASS** (Terverifikasi lewat `tests/Unit/PortalAuthApiTest.php`).

### Skenario 4: Pembatasan Laju (Throttle) pada Kegagalan Login Berulang
- **Hasil Pengujian:**
  - 5 percobaan login salah memicu respons HTTP 429 Too Many Requests.
  - Pesan penolakan informatif dan seragam mencegah enumerasi username.
- **Status:** **PASS** (Terverifikasi lewat `tests/Unit/LoginThrottleTest.php`).

### Skenario 5: Isolasi Layanan Pending
- **Hasil Pengujian:**
  - Layanan yang baru dibuat dengan status `pending` dipastikan **TIDAK MUNCUL** pada daftar layanan publik App B.
  - Setelah disetujui (status `published`), layanan langsung muncul dalam daftar pencarian dan kategori.
- **Status:** **PASS** (Terverifikasi lewat `tests/Unit/ServiceApprovalWorkflowTest.php`).

### Skenario 6: Layanan Diarsipkan / Dihapus
- **Hasil Pengujian:**
  - Layanan yang diarsipkan (`status='archived'`) atau dihapus otomatis hilang seketika dari respon API katalog App B tanpa merusak relasi data riwayat klik.
- **Status:** **PASS** (Terverifikasi lewat `tests/functional_flow.test.js` Flow 2).

### Skenario 7: Personalisasi Pribadi (Preferences & Bookmarks)
- **Endpoint:** `GET/PUT /api/v1/me/preferences` & CRUD `/api/v1/me/bookmarks`.
- **Hasil Pengujian:**
  - Pinned layanan dibatasi maksimal 12 ID dan divalidasi keberadaannya di database.
  - Riwayat pencarian dibatasi maksimal 8 entri.
  - Bookmark terisolasi secara ketat per user (`AND user_id = ?`); pengguna lain menerima respons 403/404 bila mencoba mengakses bookmark milik user lain.
- **Status:** **PASS** (Terverifikasi lewat `tests/Unit/PreferencesAndBookmarksTest.php`).

### Skenario 8: Health Check Endpoint
- **Endpoint:** `GET /api/v1/health`.
- **Hasil Pengujian:**
  - Mengembalikan `{"data":{"status":"ok"},"error":null}` dengan status code `200 OK` saat database terhubung.
  - Tidak membocorkan versi PHP, path internal, atau informasi infrastruktur server.
- **Status:** **PASS** (Terverifikasi lewat `tests/Unit/ClicksAndStatsTest.php`).

---

## 4. Kesimpulan Kesiapan QA

Seluruh skenario fungsional antarmuka, keamanan data, dan integrasi API lintas aplikasi antara App A dan App B telah teruji secara komprehensif, baik melalui pengujian otomatis (159 unit/integration tests dan 60 vitest HTTP tests) maupun checklist verifikasi fungsional manual.
