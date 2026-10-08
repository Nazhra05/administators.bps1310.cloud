# Matriks Fungsi & Click-Path Audit

**Tanggal Audit:** 2026-10-09  
**Branch:** `fix/fase-1-audit`  
**Cakupan:** Seluruh halaman antarmuka pengguna (`public/*.php`) dan endpoint API (`public/api/*.php`).  
**Metodologi:** Penelusuran alur interaktif (*click-path*): UI Trigger → HTTP Request → PHP Handler → Query Database / Operasi File → Respons & Tampilan Akhir.

---

## 1. Matriks Interaksi Pengguna & Endpoint

| Halaman / Endpoint | Elemen / Aksi | Handler | Hasil yang Diharapkan | Hasil Nyata | Status | ID Bug / Catatan |
|---|---|---|---|---|---|---|
| `login.php` | Tombol `Login` (Submit Form) | `public/login.php` (POST) | Autentikasi username/email + password, set session, redirect ke `index.php`. | Berhasil redirect ke `index.php`, session admin terbentuk, upgrade hash otomatis ke bcrypt jika SHA256. | `berfungsi` | - |
| `login.php` | Tautan `Daftar sekarang` | `public/register.php` (GET) | Membuka form registrasi akun portal user. | Halaman pendaftaran terbuka normal. | `berfungsi` | - |
| `register.php` | Tombol `Daftar` (Submit Form) | `public/register.php` (POST) | Validasi input, simpan akun dengan status `pending`, kirim email verifikasi dan notifikasi admin. | Akun tersimpan dan email terkirim, namun formulir tidak memiliki rate-limiting atau verifikasi captcha. | `tanpa validasi` | `BUG-005` (Sedang): Potensi spamming / email bombing. |
| `register.php` | Tautan `Login di sini` | `public/login.php` (GET) | Kembali ke halaman login. | Berhasil membuka `login.php`. | `berfungsi` | - |
| `index.php` | Menu Navigasi Sidebar | Berkas target `.php` | Navigasi antar halaman admin (`layanan.php`, `kategori.php`, dll). | Seluruh tautan sidebar aktif dan mengarah ke berkas yang valid. | `berfungsi` | - |
| `index.php` | Dropdown Profil Admin | `toggleAdminMenu()` (JS) | Menampilkan menu profil dan tombol logout. | Dropdown muncul dan tertutup saat klik di luar area. | `berfungsi` | - |
| `index.php` | Tombol `Logout` | `public/logout.php` (GET) | Hancurkan session dan redirect ke `login.php`. | Session terhapus, redirect 302 ke `login.php`. | `berfungsi` | - |
| `kategori.php` | Tombol `Tambah Kategori` | `public/tambah_kategori.php` (GET) | Membuka form penambahan kategori. | Halaman tambah kategori terbuka normal. | `berfungsi` | - |
| `kategori.php` | Tombol `Edit` (Aksi Baris) | `public/edit_kategori.php?id=X` (GET) | Membuka form edit kategori dengan data terisi. | Form edit terbuka sesuai ID kategori. | `berfungsi` | - |
| `kategori.php` | Tombol `Hapus` (Aksi Baris) | `public/hapus_kategori.php?id=X` (GET) | Validasi keterikatan layanan, hapus kategori dengan konfirmasi dan proteksi CSRF. | Menghapus langsung via metode GET tanpa proteksi CSRF token! | `rusak` | `BUG-001` (Mayor): Penghapusan data via HTTP GET tanpa token CSRF. |
| `tambah_kategori.php` | Tombol `Simpan` (Submit Form) | `public/tambah_kategori.php` (POST) | Validasi CSRF & nama unik, simpan ke database, redirect ke `kategori.php`. | Kategori tersimpan dan redirect ke daftar kategori. | `berfungsi` | - |
| `tambah_kategori.php` | Tombol `Batal` | `public/kategori.php` (GET) | Kembali ke halaman kategori. | Berhasil kembali ke `kategori.php`. | `berfungsi` | - |
| `edit_kategori.php` | Tombol `Simpan Perubahan` | `public/edit_kategori.php` (POST) | Validasi CSRF & perubahan nama, update database, redirect ke `kategori.php`. | Data terupdate dan redirect sukses. | `berfungsi` | - |
| `layanan.php` | Tombol `Tambah Layanan` | `public/tambah_layanan.php` (GET) | Membuka form penambahan layanan baru. | Form tambah layanan terbuka normal. | `berfungsi` | - |
| `layanan.php` | Filter Kategori (Dropdown/Pills) | `public/layanan.php?id_kategori=X` | Memfilter daftar layanan berdasarkan kategori terpilih. | Layanan terfilter dengan benar berdasarkan parameter `id_kategori`. | `berfungsi` | - |
| `layanan.php` | Kotak Pencarian Keyword | Client-side filter (JS) | Menyaring kartu/baris layanan berdasarkan teks pencarian realtime. | Script JavaScript memfilter tampilan elemen secara lokal. | `berfungsi` | - |
| `layanan.php` | Tombol `Edit` (Aksi Baris) | `public/edit_layanan.php?id=X` (GET) | Membuka form edit layanan dengan data terisi. | Form edit terbuka sesuai ID layanan. | `berfungsi` | - |
| `layanan.php` | Tombol `Hapus` (Aksi Baris) | `public/hapus_layanan.php?id=X` (GET) | Hapus layanan dan berkas logo terkait dengan proteksi CSRF. | Menghapus langsung via HTTP GET tanpa CSRF token! | `rusak` | `BUG-002` (Mayor): Penghapusan data & berkas via HTTP GET tanpa CSRF. |
| `tambah_layanan.php` | Form Tambah + Upload Logo | `public/tambah_layanan.php` (POST) | Validasi CSRF, upload berkas logo, simpan data layanan, redirect ke `layanan.php`. | Layanan tersimpan, logo tersimpan di `public/uploads/`. | `berfungsi` | - |
| `edit_layanan.php` | Form Edit + Ganti Logo | `public/edit_layanan.php` (POST) | Validasi CSRF, update data, ganti logo lama jika ada logo baru, redirect ke `layanan.php`. | Update berhasil dan logo lama terhapus dengan benar. | `berfungsi` | - |
| `kelola_admin.php` | Aksi `Approve` / `Reject` / `Delete` | `public/kelola_admin.php` (POST) | Validasi role ADMIN IT, validasi CSRF, ubah status atau hapus akun admin. | Status akun terupdate dengan benar dan ada pesan feedback. | `berfungsi` | - |
| `kelola_admin.php` | Aksi `Jadikan Admin Utama` / `Biasa` | `public/kelola_admin.php` (POST) | Mengubah role admin antara `ADMIN IT` dan `ADMIN DASHBOARD`. | Role berhasil diperbarui. | `berfungsi` | - |
| `kelola_user.php` | Form Undang User Baru | `public/kelola_user.php` (POST) | Masukkan email, buat token verifikasi, kirim email aktivasi. | Akun dibuat berstatus pending, email verifikasi terkirim via Mailpit. | `berfungsi` | - |
| `verify_email.php` | Akses Link Verifikasi Email | `public/verify_email.php?token=X` | Verifikasi token admin, aktifkan email, kosongkan token sekali pakai. | Token divalidasi, `email_verified` menjadi 1, token dihapus. | `berfungsi` | - |
| `verify_user.php` | Akses Link Verifikasi User Portal | `public/verify_user.php?token=X` | Verifikasi token user portal, tampilkan tombol lanjut buat password. | Token divalidasi, menampilkan link ke `setup_user.php?id=X`. | `berfungsi` | - |
| `setup_user.php` | Akses Form Setup Kredensial | `public/setup_user.php?id=X` (GET) | User mengisi username dan password awal setelah verifikasi email. | Form dapat diakses hanya dengan ID integer tanpa token rahasia (IDOR)! | `rusak` | `BUG-003` (Mayor): Kerentanan IDOR pada aktivasi user portal tanpa token. |
| `setup_user.php` | Simpan Username & Password | `public/setup_user.php` (POST) | Simpan username dan hash password standar ke database. | Password disimpan memakai hash SHA-256 (`hash('sha256', $password)`) tanpa salt! | `rusak` | `BUG-004` (Mayor): Pemakaian hash usang SHA-256 pada pembuatan password portal. |
| `pengaturan.php` | Halaman Pengaturan Akun | `public/pengaturan.php` (GET) | Tampilan ringkasan profil dan pengaturan ganti password. | Hanya menampilkan info sistem statis dan tombol logout; tidak ada form ganti password. | `tanpa handler` | `BUG-006` (Minor): Fitur pengaturan ganti password belum tersedia. |
| `trending-topics.php` | Ambil Data Topik Populer | `public/trending-topics.php` (GET) | Mengembalikan JSON topik statistik terhangat. | Mengembalikan 404 jika berkas cache `trending-topics.json` belum digenerate; tidak terhubung ke UI dashboard. | `tanpa umpan balik` | `BUG-007` (Minor): Endpoint berdiri sendiri tanpa integrasi UI dashboard. |
| `api/kategori.php` | GET Data Kategori | `public/api/kategori.php` (GET) | Validasi API key, kembalikan daftar kategori dalam format JSON. | JSON kategori berhasil diambil (`status: true`). | `berfungsi` | - |
| `api/layanan.php` | GET Data Layanan | `public/api/layanan.php` (GET) | Validasi API key, kembalikan daftar layanan aktif. | JSON layanan berhasil diambil (`status: true`). | `berfungsi` | - |
| `api/layanan.php` | POST / PUT / DELETE Layanan | `public/api/layanan.php` (POST/PUT/DELETE) | Menambah, mengubah, atau menghapus layanan via REST API. | Operasi tulis hanya memverifikasi API key; tidak ada verifikasi sesi/role admin. | `tanpa validasi` | `BUG-008` (Sedang): API mutasi layanan hanya dijaga API key tanpa role check. |
| `api/website.php` | GET Data Website Terkelompok | `public/api/website.php` (GET) | Mengembalikan daftar layanan yang dikelompokkan per kategori. | Mengembalikan JSON hirarki kategori dan tautan layanan. | `berfungsi` | - |
| `api/auth/loginPortal.php` | POST Autentikasi Portal App B | `public/api/auth/loginPortal.php` (POST) | Memvalidasi identitas (username/email) dan password untuk App B. | Berhasil mengautentikasi dan mengembalikan data profil pengguna (200 OK). | `berfungsi` | - |

---

## 2. Rincian Bug Fungsional & Tingkat Prioritas

| ID Bug | Tingkat | Komponen / Berkas | Ringkasan Masalah | Akar Masalah | Rencana Remediasi (Fase) |
|---|---|---|---|---|---|
| **BUG-001** | **Mayor** | `public/hapus_kategori.php` | Penghapusan kategori dieksekusi via metode HTTP GET tanpa CSRF token. | Aksi destruktif dipicu dari tag `<a href="hapus_kategori.php?id=...">` biasa. | Fase 3 / Fase 6: Ubah ke form POST dengan token CSRF. |
| **BUG-002** | **Mayor** | `public/hapus_layanan.php` | Penghapusan layanan & penghapusan berkas logo dieksekusi via HTTP GET tanpa CSRF token. | Aksi destruktif dipicu dari tautan GET biasa. | Fase 3 / Fase 6: Ubah ke form POST dengan token CSRF. |
| **BUG-003** | **Mayor** | `public/setup_user.php`, `public/verify_user.php` | Kerentanan IDOR (Insecure Direct Object Reference) pada inisialisasi akun user portal. | `verify_user.php` menghapus token dan hanya mengoper `?id=<id_admin>` mentah ke `setup_user.php`. Siapapun bisa mengubah user lain jika mengetahui ID-nya. | Fase 3 / Fase 4: Pertahankan setup token acak atau verifikasi berbasis sesi/token terenkripsi. |
| **BUG-004** | **Mayor** | `public/setup_user.php` | Password baru untuk user portal di-hash menggunakan algoritma SHA-256 mentah tanpa garam. | Penggunaan fungsi usang `hash('sha256', $password)`. | Fase 3 / Fase 5: Ganti ke `password_hash($password, PASSWORD_BCRYPT)`. |
| **BUG-005** | **Sedang** | `public/register.php` | Formulir registrasi publik tidak memiliki pembatasan laju (*rate limiting*) atau filter domain. | Endpoint registrasi terbuka dapat disalahgunakan untuk spamming email SMTP. | Fase 3 / Fase 4: Tambahkan rate limiting IP dan pembatasan domain email jika diperlukan. |
| **BUG-006** | **Minor** | `public/pengaturan.php` | Fitur ubah password / pengaturan profil tidak memiliki handler atau form masukan. | Halaman hanya memuat teks statis informasi sistem dan tombol logout. | Fase 3 / Fase 4: Lengkapi form ubah password dengan verifikasi password lama. |
| **BUG-007** | **Minor** | `public/trending-topics.php`, `scripts/generate-topics.php` | Endpoint trending topics AI tidak terhubung ke dashboard admin dan mengembalikan 404 jika belum digenerate. | Fitur eksperimental Gemini terisolasi dan memerlukan API key eksternal. | GATE 1 / Fase 3: Keputusan developer apakah dipertahankan atau dihapus (YAGNI). |
| **BUG-008** | **Sedang** | `public/api/layanan.php` | Aksi penulisan/penghapusan REST API (POST, PUT, DELETE) hanya dilindungi `API_KEY` statis. | Tidak ada pemisahan hak akses antara consumer publik dan akun administrator pada API. | Fase 2 & 3: Standarisasi otorisasi API key khusus App B vs sesi Dashboard. |
