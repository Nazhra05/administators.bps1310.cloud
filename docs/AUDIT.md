# Dokumen Audit Komprehensif Arsitektur & Keamanan (docs/AUDIT.md)

**Tanggal Audit:** 2026-10-09  
**Branch:** `fix/fase-1-audit`  
**Target Aplikasi:** Dashboard Admin BPS Kabupaten Solok Selatan (App A)  
**Lingkungan:** Local XAMPP (Apache 2.4, MariaDB 10.4, PHP 8.3)  
**Status Evaluasi:** READ-ONLY (Tidak ada kode produksi yang dimutasi selama audit ini)

---

## 1. Inventaris Berkas Proyek (Task 1.1)

| Berkas | Tujuan / Fungsi | Pemanggil / Konsumen | Perlu Login? | Metode HTTP | Menulis ke DB / File? |
|---|---|---|---|---|---|
| `public/login.php` | Halaman form autentikasi dan pemrosesan sesi admin | Browser / Tamu | Tidak | GET, POST | Ya (Update password upgrade ke Bcrypt) |
| `public/register.php` | Pendaftaran akun baru portal pengguna | Browser / Tamu | Tidak | GET, POST | Ya (Insert admin status 'pending') |
| `public/index.php` | Dasbor utama statistik layanan dan kategori | Browser / Administrator | Ya | GET | Tidak |
| `public/kategori.php` | Tampilan tabel dan kelola kategori layanan | Browser / Administrator | Ya | GET | Tidak |
| `public/tambah_kategori.php` | Form input kategori layanan baru | Browser / Administrator | Ya | GET, POST | Ya (Insert kategori) |
| `public/edit_kategori.php` | Form pengubahan nama kategori layanan | Browser / Administrator | Ya | GET, POST | Ya (Update kategori) |
| `public/hapus_kategori.php` | Handler penghapusan kategori layanan | Browser / Administrator | Ya | GET | Ya (Delete kategori) |
| `public/layanan.php` | Tampilan katalog layanan (tabel/kartu) & pencarian | Browser / Administrator | Ya | GET | Tidak |
| `public/tambah_layanan.php` | Form input layanan baru + upload berkas logo | Browser / Administrator | Ya | GET, POST | Ya (Insert layanan + simpan berkas) |
| `public/edit_layanan.php` | Form pengubahan layanan + perbarui logo | Browser / Administrator | Ya | GET, POST | Ya (Update layanan + ganti berkas) |
| `public/hapus_layanan.php` | Handler penghapusan layanan dan logo terkait | Browser / Administrator | Ya | GET | Ya (Delete layanan + unlink berkas) |
| `public/kelola_admin.php` | Persetujuan status dan promosi role admin | Browser / ADMIN IT | Ya (Khusus ADMIN IT) | GET, POST | Ya (Update role/status admin, Delete admin) |
| `public/kelola_user.php` | Manajemen aktivasi dan pengiriman undangan user | Browser / ADMIN IT | Ya (Khusus ADMIN IT) | GET, POST | Ya (Insert user portal status pending) |
| `public/pengaturan.php` | Halaman ringkasan profil sistem dan tombol logout | Browser / Administrator | Ya | GET | Tidak |
| `public/setup_user.php` | Pembuatan kredensial username & password user portal | Browser / User Terundang | Tidak (Rawan IDOR) | GET, POST | Ya (Update password user portal) |
| `public/verify_email.php` | Verifikasi token aktivasi pendaftaran admin | Browser / Admin Terdaftar | Tidak | GET | Ya (Update email_verified) |
| `public/verify_user.php` | Verifikasi token aktivasi user portal | Browser / User Terundang | Tidak | GET | Ya (Update email_verified) |
| `public/send_verification_email.php` | Fungsi pembantu pengiriman email verifikasi admin | Skrip internal `register.php` | Tidak (File publik) | GET/POST | Tidak (Hanya kirim email) |
| `public/send_user_verification_email.php` | Fungsi pembantu pengiriman email verifikasi portal | Skrip internal `kelola_user.php` | Tidak (File publik) | GET/POST | Tidak (Hanya kirim email) |
| `public/send_admin_notification.php` | Fungsi pembantu notifikasi pendaftaran ke admin | Skrip internal `register.php` | Tidak (File publik) | GET/POST | Tidak (Hanya kirim email) |
| `public/trending-topics.php` | Mengembalikan cache JSON topik populer AI | Browser / Client API | Tidak | GET | Tidak |
| `public/test_api.php` | Halaman pengujian koneksi API layanan internal | Administrator | Ya | GET | Tidak |
| `public/logout.php` | Handler penghancuran sesi dan logout | Browser / Pengguna Login | Ya | GET | Tidak (Menghapus sesi) |
| `public/api/kategori.php` | REST API daftar kategori | App B (`sipasti`) / Klien API | Tidak (API Key) | GET, OPTIONS | Tidak |
| `public/api/layanan.php` | REST API CRUD layanan statistik | App B (`sipasti`) / Klien API | Tidak (API Key) | GET, POST, PUT, DELETE, OPTIONS | Ya (Insert, Update, Delete layanan) |
| `public/api/website.php` | REST API daftar layanan terkelompok per kategori | App B (`sipasti`) / Portal Publik | Tidak (API Key) | GET, OPTIONS | Tidak |
| `public/api/auth/loginPortal.php` | REST API autentikasi pengguna portal untuk App B | App B Backend Server | Tidak (API Key) | POST, OPTIONS | Tidak |
| `config/config.php` | Bootstrapping, session, PDO database, CSRF & XSS helpers | Seluruh skrip aplikasi | - | - | Tidak |
| `config/auth.php` | Middleware guard pengecekan sesi dan role login | Seluruh skrip dashboard | - | - | Tidak |
| `app/models/Layanan.php` | Model query PDO Layanan (Tidak pernah di-instansiasi) | Tidak ada (Dead Code) | - | - | Tidak |
| `scripts/generate-topics.php` | CLI script pemanggil Google Gemini AI ke JSON file | Cron / CLI Administrator | - | CLI | Ya (Menulis `trending-topics.json`) |
| `tests/app.test.js` | Suite pengujian black-box HTTP menggunakan Vitest | Vitest Test Runner | - | CLI | Tidak |

---

## 2. Audit Keamanan Per Berkas (Task 1.2)

| Parameter Keamanan | Status | Analisis & Nomor Baris |
|---|---|---|
| **Pemeriksaan Sesi Login Sebelum Proses** | ⚠️ Sebagian Rawan | - Halaman dashboard admin (`index.php:9`, `layanan.php:9`, `kategori.php:9`, `edit_kategori.php:9`, `edit_layanan.php:9`, `tambah_kategori.php:9`, `tambah_layanan.php:9`, `hapus_kategori.php:9`, `hapus_layanan.php:9`, `pengaturan.php:3`) memeriksa sesi via `config/auth.php` atau `$_SESSION['admin_id']`.<br>- **Rawan:** `setup_user.php:15` dapat diakses tanpa login dan hanya mengandalkan `id` numerik.<br>- Berkas pembantu email (`send_*.php`) berada di folder web publik tanpa guard. |
| **Prepared Statements (Injeksi SQL)** | ✅ Aman | Seluruh query dinamis yang menerima parameter pengguna menggunakan `$conn->prepare()` dengan placeholder `?` dan parameter binding yang benar (misal `login.php:59`, `tambah_layanan.php:184`, `api/layanan.php:600`, `api/auth/loginPortal.php:90`). Tidak ditemukan concatenasi string langsung ke dalam klausa SQL. |
| **Pemeriksaan CSRF Token** | ⚠️ Sebagian Rawan | - Formulir POST (`login.php:47`, `register.php:12`, `tambah_kategori.php:19`, `edit_kategori.php:34`, `tambah_layanan.php:79`, `edit_layanan.php:84`, `kelola_admin.php:38`, `kelola_user.php:54`) memvalidasi CSRF token via `csrf_verify()`.<br>- **Pelanggaran Kritis:** [`hapus_kategori.php`](file:///c:/xampp/htdocs/adminDashboard/public/hapus_kategori.php) dan [`hapus_layanan.php`](file:///c:/xampp/htdocs/adminDashboard/public/hapus_layanan.php) melakukan mutasi data via HTTP GET tanpa pemeriksaan token CSRF. |
| **Output Escaping (XSS Prevention)** | ✅ Baik | Variabel keluaran yang dirender ke HTML telah di-escape menggunakan `htmlspecialchars()` atau fungsi helper `e()` (misalnya `layanan.php:1943`, `kategori.php`, `pengaturan.php:622`). Tidak ditemukan pemakaian `echo $_GET` mentah. |
| **Open Redirect Vulnerability** | ✅ Aman | Semua pemanggilan `header("Location: ...")` mengarah ke berkas statis internal yang telah ditentukan (mis. `login.php`, `index.php`, `layanan.php`). Tidak ada redirect dinamis berbasis parameter URL dari pengguna. |
| **Kebocoran Path Server / Error / Debug** | ⚠️ Waspada | `config/config.php:135` mengecek `APP_DEBUG`. Jika diset `true`, exception database dicetak ke layar (`die("Koneksi database gagal: " . $e->getMessage())`). Ini harus dinonaktifkan di produksi. |
| **Guard Endpoint Tulis API (POST/PUT/DELETE)** | ⚠️ Perlu Dibatasi | Endpoint `public/api/layanan.php` untuk aksi tulis (POST, PUT, DELETE) hanya dilindungi oleh `API_KEY` statis di baris 178. Tidak ada guard sesi pengguna atau otorisasi berbasis hak akses role. |
| **Konfigurasi Header CORS** | ⚠️ Berlebihan | Header `Access-Control-Allow-Origin` disetel di `api/layanan.php:68`, `api/kategori.php`, dan `api/website.php:74`. Karena App B berkomunikasi antarmuka backend-to-backend via cURL / fetch server, header CORS peramban sebenarnya berlebih. |
| **Perbandingan API Key** | ✅ Aman | Seluruh validasi API key di `api/layanan.php:232`, `api/kategori.php:164`, `api/website.php:163`, dan `api/auth/loginPortal.php:60` menggunakan `hash_equals(API_KEY, $clientApiKey)` yang mencegah timing-attack. |
| **Registrasi Admin Terbuka (`register.php`)** | ⚠️ Rawan Penyalahgunaan | Terbuka bebas untuk publik tanpa proteksi captcha atau rate-limiting. Mengirimkan 2 email sekaligus (ke pendaftar dan admin) per request yang lolos validasi. |
| **Keamanan Token Verifikasi Email** | ✅ Cukup Baik | Dibuat menggunakan `bin2hex(random_bytes(32))` (`register.php:89`, `kelola_user.php:68`). Memiliki batas kedaluwarsa 24 jam (`verification_expires`) dan dihapus (`SET verification_token = NULL`) setelah sukses diverifikasi. |
| **Keamanan Upload Berkas (`tambah_layanan.php`)** | ✅ Aman di Apache | Validasi ekstensi whitelist (`jpg`, `jpeg`, `png`, `webp`), validasi MIME riil via `finfo(FILEINFO_MIME_TYPE)`, batas ukuran maksimal 2MB, nama berkas disanitasi, dan direktori `public/uploads/.htaccess` memblokir eksekusi skrip PHP secara ketat. |
| **Keamanan Cookie Sesi** | ✅ Aman | `config/config.php:151-158` menyetel `httponly = true`, `samesite = 'Lax'`, dan `secure = isHttps`. Sesi di-regenerate via `session_regenerate_id(true)` saat login berhasil (`login.php:182`). |

---

## 3. Format Password dan Alur Login (Task 1.3)

### A. Format Hash Password di Tabel `admin`
Berdasarkan investigasi skema dan kode program:
1. **Format Historis (Warisan):** SHA-256 mentah tanpa salt (string heksadesimal 64 karakter). Contoh bawaan lama: `hash('sha256', 'admin123') = 240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9`.
2. **Format Standar Baru:** Bcrypt (`$2y$10$...` atau `$2y$12$...` panjang 60 karakter) yang dihasilkan oleh `password_hash($password, PASSWORD_DEFAULT)`.
3. **Mekanisme Migrasi Otomatis:** Pada [`public/login.php:178`](file:///c:/xampp/htdocs/adminDashboard/public/login.php#L178), jika admin login menggunakan password SHA-256 yang valid, sistem langsung memutakhirkan hash tersebut ke `password_hash()` standar di basis data.
4. **Anomali di `public/setup_user.php:156`:** Pembuatan password user portal masih memakai `hash('sha256', $password)` lama.

Hasil query distribusi panjang password di DB pengujian lokal (`bps_solsel_dev`):
```sql
SELECT CHAR_LENGTH(password) AS len, LEFT(password,4) AS prefix, COUNT(*) AS cnt FROM admin GROUP BY 1,2;
-- len: 60 | prefix: $2y$ | cnt: 2 (Semua akun dev telah berformat Bcrypt modern)
```

### B. Alur Login App B (`sipasti.bps1310.cloud`)
App B melakukan login dengan memanggil endpoint REST API:
- **Endpoint:** `POST http://<domain>/api/auth/loginPortal.php`
- **Header:** `X-API-KEY: <API_KEY_APP_B>` dan `Content-Type: application/json`
- **Payload:**
  ```json
  {
    "identity": "username_atau_email",
    "password": "password_pengguna"
  }
  ```
- **Logika Verifikasi:** Mendukung verifikasi *hybrid* (Bcrypt via `password_verify` dan fallback SHA-256 via `hash_equals`). Hanya akun berstatus `approved` yang diizinkan.
- **Respons JSON:**
  ```json
  {
    "status": true,
    "message": "Login berhasil",
    "data": {
      "id_admin": 1,
      "username": "admin",
      "email": "admin@bps1310.cloud",
      "role": "ADMIN IT"
    }
  }
  ```

### C. Lokasi Penulisan Kolom `password` di Seluruh Kode
1. [`public/register.php:102`](file:///c:/xampp/htdocs/adminDashboard/public/register.php#L102): Pendaftaran mandiri akun admin (`password_hash`).
2. [`public/setup_user.php:156`](file:///c:/xampp/htdocs/adminDashboard/public/setup_user.php#L156): Pembuatan password akun portal (`hash('sha256')` - **perlu diperbaiki**).
3. [`public/login.php:178`](file:///c:/xampp/htdocs/adminDashboard/public/login.php#L178): Pemutakhiran otomatis hash SHA-256 ke Bcrypt saat login berhasil.
4. [`database/seeds/dev_seed.sql`](file:///c:/xampp/htdocs/adminDashboard/database/seeds/dev_seed.sql): Injeksi akun seed pengembang (`$2y$10$...`).

---

## 4. Pemakaian dan Kode Mati (Task 1.4)

| Komponen / Fitur | Status Penggunaan | Analisis & Rekomendasi |
|---|---|---|
| `app/models/Layanan.php` | **Tidak Ada Referensi (Dead Code)** | Kelas `Layanan` tidak pernah di-require atau di-instansiasi di file mana pun. Kode lama yang tertinggal saat mencoba pola MVC separuh jalan. *Rekomendasi: Hapus di Fase 3.* |
| `public/trending-topics.php` & `scripts/generate-topics.php` | **Terisolasi (Eksperimental)** | Menggunakan Gemini AI API untuk menghasilkan rangkuman topik. Tidak dihubungkan ke dashboard admin mana pun. *Rekomendasi: Ditanyakan pada GATE 1 (opsi hapus demi kebersihan arsitektur).* |
| `public/api/website.php` | **Endpoint Eksternal** | Tidak dipakai oleh dashboard admin, namun menyediakan JSON kategori terkelompok untuk portal luar. *Rekomendasi: Pertahankan kontraknya untuk kompatibilitas App B.* |
| `public/test_api.php` | **Utilitas Pengujian Manual** | Berkas pengujian internal manual sederhana. *Rekomendasi: Pindahkan ke folder utilitas dev / tests.* |
| `public/send_*.php` di `public/` | **Arsitektur Salah Letak** | Berkas pustaka fungsi diletakkan di root web publik. *Rekomendasi: Refaktor ke class service di `src/Services/` di Fase 4.* |
| `public/setup_user.php` | **Aktif tapi Rentan** | Alur aktivasi user portal lanjutan dari `verify_user.php`. Mengandung celah IDOR dan hash usang. *Rekomendasi: Perbaiki token guard dan enkripsi di Fase 3 & 4.* |
| `tests/app.test.js` & `.vitest/` | **Aktif & Sangat Relevan** | Suite 20 tes Vitest menguji integrasi HTTP black-box secara nyata ke Apache lokal. *Rekomendasi: Pertahankan dan integrasikan dengan PHPUnit di Fase 2.* |

---

## 5. Ringkasan Temuan & Rekomendasi Arsitektur (Task 1.5)

### Tabel Temuan Prioritas

| ID | Temuan | Tingkat | Berkas Terkait | Fase Perbaikan |
|---|---|---|---|---|
| **SEC-01** | Penghapusan data kategori dan layanan dieksekusi via HTTP GET tanpa CSRF token | **Tinggi (Mayor)** | `public/hapus_kategori.php`, `public/hapus_layanan.php` | Fase 3 / Fase 6 |
| **SEC-02** | Kerentanan IDOR pada inisialisasi akun user portal via ID numerik tanpa token | **Tinggi (Mayor)** | `public/setup_user.php`, `public/verify_user.php` | Fase 3 / Fase 4 |
| **SEC-03** | Algoritma hash usang SHA-256 tanpa garam pada aktivasi user portal | **Tinggi (Mayor)** | `public/setup_user.php:156` | Fase 3 / Fase 5 |
| **SEC-04** | Endpoint pendaftaran publik `register.php` terbuka tanpa rate limiting / captcha | **Sedang** | `public/register.php` | Fase 3 / Fase 4 |
| **SEC-05** | REST API mutasi (POST, PUT, DELETE) di `api/layanan.php` hanya dijaga API key | **Sedang** | `public/api/layanan.php` | Fase 2 / Fase 3 |
| **SEC-06** | Berkas pembantu pengiriman email (`send_*.php`) dapat diakses langsung via browser | **Rendah** | `public/send_*.php` | Fase 4 |
| **ARCH-01** | Kode mati: Model `app/models/Layanan.php` tidak pernah dipakai | **Rendah** | `app/models/Layanan.php` | Fase 3 |
| **ARCH-02** | Fitur Gemini AI `trending-topics.php` terisolasi dan tidak terpakai | **Rendah** | `public/trending-topics.php`, `scripts/generate-topics.php` | GATE 1 / Fase 3 |
| **UI-01** | Halaman `pengaturan.php` tidak memiliki form ganti password / edit profil | **Rendah** | `public/pengaturan.php` | Fase 3 / Fase 4 |

---

### Rekomendasi Strategi: OPSI A (Perbaiki Bertahap / In-Place Modernization)

**Rekomendasi Kami: PILIH OPSI A**

**Alasan Singkat:**
1. **Risiko Rendah bagi App B:** App B (`sipasti.bps1310.cloud`) secara aktif bergantung pada endpoint API yang ada (`/api/layanan.php`, `/api/kategori.php`, `/api/website.php`, `/api/auth/loginPortal.php`). Membangun ulang dari nol dengan framework baru (Opsi B) berisiko mematahkan kontrak integrasi dan membuang waktu pada boilerplate.
2. **Kualitas Fondasi yang Sudah Terbentuk:** Di Fase 0, kita telah membangun fondasi kuat: Composer PSR-4, PHP 8.x, PDO terparameterisasi, Vitest test suite (20 tes lulus), serta database dev/test terisolasi di XAMPP.
3. **Hasil Terukur dan Teruji:** Opsi A memungkinkan kita mengunci kontrak via tes karakterisasi (Fase 2), memperbaiki bug satu per satu (Fase 3), merestrukturisasi ke PSR-4 modern (Fase 4), dan mengamankan autentikasi (Fase 6) tanpa merusak fungsionalitas yang sudah bekerja.
