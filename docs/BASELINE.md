# Baseline Smoke Test Report

**Tanggal Audit Baseline:** 2026-10-09  
**Branch:** `fix/fase-0-fondasi`  
**Lingkungan:** Local XAMPP (Apache 2.4, MariaDB 10.4, PHP 8.3)  
**Database Uji:** `bps_solsel_dev` (diisi oleh `database/seeds/dev_seed.sql`)  
**Metodologi:** Pengujian otomatis HTTP via [smoke_test.js](file:///c:/xampp/htdocs/adminDashboard/scripts/smoke_test.js) baik dalam kondisi unauthenticated (tamu) maupun authenticated (sesi login role `ADMIN IT`).

---

## 1. Ringkasan Status Halaman & Endpoint

| Endpoint / Halaman | Metode | Status (Tanpa Login) | Status (Login Admin IT) | Status Baseline | Catatan / Temuan |
|---|---|---|---|---|---|
| `login.php` | GET / POST | 200 OK | 302 -> `index.php` | Berjalan | Form login utama, validasi username/email + bcrypt/SHA256. |
| `register.php` | GET / POST | 200 OK | 200 OK | Berjalan | Registrasi publik untuk akun portal user. |
| `index.php` | GET | 302 -> `login.php` | 200 OK | Berjalan | Dashboard utama admin, statistik layanan & kategori tampil. |
| `kategori.php` | GET | 302 -> `login.php` | 200 OK | Berjalan | Daftar kategori, tombol tambah/edit/hapus. |
| `tambah_kategori.php` | GET / POST | 302 -> `login.php` | 200 OK | Berjalan | Form penambahan kategori. |
| `edit_kategori.php?id=1` | GET / POST | 302 -> `login.php` | 200 OK | Berjalan | Form ubah kategori (memerlukan query parameter `id`). |
| `hapus_kategori.php` | GET | 302 -> `login.php` | 302 -> `kategori.php` | Berjalan (Rawan) | Menghapus via GET tanpa konfirmasi/CSRF token. |
| `layanan.php` | GET | 302 -> `login.php` | 200 OK | Berjalan | Daftar layanan (tabel & kartu), filter pencarian. |
| `tambah_layanan.php` | GET / POST | 302 -> `login.php` | 200 OK | Berjalan | Form penambahan layanan baru + upload logo. |
| `edit_layanan.php?id=1` | GET / POST | 302 -> `login.php` | 200 OK | Berjalan | Form ubah layanan + ganti logo. |
| `hapus_layanan.php` | GET | 302 -> `login.php` | 302 -> `layanan.php` | Berjalan (Rawan) | Menghapus via GET tanpa konfirmasi/CSRF token. |
| `kelola_admin.php` | GET / POST | 302 -> `login.php` | 200 OK | Berjalan | Manajemen akun administrator (khusus `ADMIN IT`). |
| `kelola_user.php` | GET / POST | 302 -> `login.php` | 200 OK | Berjalan | Persetujuan & status akun user portal. |
| `pengaturan.php` | GET / POST | 302 -> `login.php` | 200 OK | Berjalan | Pengaturan akun & ganti password. |
| `trending-topics.php` | GET | 404 Not Found | 404 Not Found | Butuh Data | Mengembalikan `{"error":"Topics not generated yet"}` jika berkas cache JSON belum dibuat oleh skrip generator. |
| `test_api.php` | GET | 302 -> `login.php` | 200 OK | Berjalan | Halaman diagnostik internal untuk menguji koneksi API. |
| `setup_user.php` | GET / POST | 200 OK | 200 OK | Berjalan (Perhatian) | Terbuka tanpa sesi login; perlu diaudit kepemilikan alurnya. |
| `verify_email.php` | GET | 200 OK | 200 OK | Berjalan | Halaman verifikasi token email aktivasi admin. |
| `verify_user.php` | GET | 200 OK | 200 OK | Berjalan | Halaman verifikasi token email user portal. |
| `send_verification_email.php` | GET / POST | 200 OK | 200 OK | Kosong/Rawan | Dapat diakses publik tanpa login; potensi penyalahgunaan pengiriman email. |
| `send_user_verification_email.php` | GET / POST | 200 OK | 200 OK | Kosong/Rawan | Dapat diakses publik tanpa login. |
| `send_admin_notification.php` | GET / POST | 200 OK | 200 OK | Kosong/Rawan | Dapat diakses publik tanpa login. |
| `logout.php` | GET | 302 -> `login.php` | 302 -> `login.php` | Berjalan | Menghancurkan session dan mengarahkan ke login. |
| `api/kategori.php` | GET | 401 Unauthorized | 200 OK | Berjalan | Memerlukan header `X-API-KEY`. Mengembalikan JSON kategori. |
| `api/layanan.php` | GET | 401 Unauthorized | 200 OK | Berjalan | Memerlukan header `X-API-KEY`. Mengembalikan JSON layanan. |
| `api/website.php` | GET | 401 Unauthorized | 200 OK | Berjalan | Memerlukan header `X-API-KEY`. Mengembalikan JSON website/layanan terkelompok. |
| `api/auth/loginPortal.php` | POST | 401 (tanpa API key) / 200 (dengan kredensial) | 200 OK | Berjalan | Memerlukan `X-API-KEY` dan payload JSON `{ identity, password }`. |

---

## 2. Catatan Khusus & Temuan Awal (Bukan Diperbaiki Sekarang)

1. **Aksi Destruktif via Metode HTTP GET:**
   - [`public/hapus_kategori.php`](file:///c:/xampp/htdocs/adminDashboard/public/hapus_kategori.php) dan [`public/hapus_layanan.php`](file:///c:/xampp/htdocs/adminDashboard/public/hapus_layanan.php) mengeksekusi penghapusan data secara langsung saat URL diakses via GET tanpa CSRF token. Ini melanggar standar HTTP dan berisiko terkena serangan CSRF.
2. **Endpoint Pengiriman Email Terbuka:**
   - [`public/send_verification_email.php`](file:///c:/xampp/htdocs/adminDashboard/public/send_verification_email.php), [`public/send_user_verification_email.php`](file:///c:/xampp/htdocs/adminDashboard/public/send_user_verification_email.php), dan [`public/send_admin_notification.php`](file:///c:/xampp/htdocs/adminDashboard/public/send_admin_notification.php) tidak membatasi akses (siapa saja yang meminta URL ini bisa memicu fungsi pengiriman email jika parameter ada).
3. **Endpoint AI Trending Topics:**
   - [`public/trending-topics.php`](file:///c:/xampp/htdocs/adminDashboard/public/trending-topics.php) merespons 404 secara default jika `scripts/generate-topics.php` belum dijalankan atau file JSON cache belum terbentuk.
4. **Kontrak API `api/auth/loginPortal.php`:**
   - Parameter identitas menggunakan nama field `identity` (menerima email atau username), bukan `username`. Respons status `200` memberikan data profil pengguna (`id_admin`, `username`, `email`, `role`).
5. **Autentikasi API Key:**
   - Seluruh endpoint API di `public/api/` terlindungi pengecekan API key (`X-API-KEY` atau query param `api_key`).
