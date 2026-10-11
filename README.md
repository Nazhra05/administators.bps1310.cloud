# Dashboard Admin - BPS Kabupaten Solok Selatan

Dashboard administrasi berbasis web untuk pengelolaan katalog layanan, kategori statistik, serta akun administrator internal Badan Pusat Statistik (BPS) Kabupaten Solok Selatan.

---

## 📁 Struktur Direktori

```text
administators.bps1310.cloud/
├── app/
│   └── services/
│       ├── mail_service.php     # Layanan pengiriman email transaksi terpusat
│       └── user_repo_service.php# Layanan repositori privat pegawai
├── config/
│   ├── .htaccess                # Proteksi akses langsung web server
│   ├── auth.php                 # Middleware otentikasi & RBAC
│   ├── config.php               # Konfigurasi sistem, environment loader & PDO
│   └── user_repo.php            # Proxy kompatibilitas repositori privat
├── database/
│   ├── bps_solsel.sql           # Database dump produksi (data lengkap kategori, layanan & admin)
│   └── schema.sql               # Skema DDL struktur database & template seed awal
├── PHPMailer/                   # Library pengiriman email notifikasi & verifikasi
├── public/
│   ├── api/
│   │   ├── auth/loginPortal.php # REST API otentikasi login untuk App B
│   │   ├── kategori.php         # REST API endpoint kategori (CORS & Key protected)
│   │   ├── layanan.php          # REST API endpoint layanan CRUD
│   │   ├── user_repo.php        # REST API repositori privat pegawai
│   │   └── website.php          # REST API endpoint struktur website publik
│   ├── css/
│   │   └── style.css            # Token desain sistem dan kelas utilitas terpusat
│   ├── partials/
│   │   ├── account_dropdown.php # Dropdown profil & logout pengguna
│   │   ├── navbar.php           # Sidebar navigasi bersama terpadu berbasis peran
│   │   └── sidebar_user.php     # Proxy kompatibilitas navigasi peran pengguna
│   ├── uploads/
│   │   ├── .gitkeep
│   │   ├── .htaccess            # Blokir eksekusi script PHP di folder upload
│   │   └── logo_bps.jpg         # Logo resmi BPS
│   ├── index.php                # Dashboard utama (Admin & User)
│   ├── kategori.php             # Halaman & modal kelola kategori
│   ├── kelola_admin.php         # Manajemen administrator (undangan admin baru, aktivasi)
│   ├── kelola_user.php          # Manajemen pengguna (persetujuan registrasi pegawai, status user)
│   ├── layanan.php              # Halaman & modal kelola layanan
│   ├── login.php                # Autentikasi akun pengguna & admin
│   ├── logout.php               # Pembersihan sesi aman
│   ├── pengaturan.php           # Pengaturan profil dan keamanan akun
│   ├── register.php             # Pendaftaran mandiri pegawai (role USER)
│   ├── repositori_portal.php    # Repositori portal layanan
│   ├── repositori_private.php   # Repositori koleksi privat pegawai
│   ├── setup_user.php           # Pengaturan akun administrator baru
│   ├── switch_role.php          # Pergantian peran dinamis administrator
│   ├── verify_email.php         # Verifikasi token email pendaftar
│   └── verify_user.php          # Verifikasi token email administrator baru
├── scripts/
│   ├── .htaccess                # Proteksi folder cron/script dari web
│   └── test_api.php             # Alat uji endpoint API internal
├── tests/                       # Suite pengujian otomatis berbasis Vitest
├── .env.example                 # Template environment variables (aman untuk commit)
├── .gitignore                   # Aturan pengabaian file sensitif / rahasia Git
├── .htaccess                    # Pengamanan direktori root, MIME, dan HTTP headers
└── README.md
```

---

## ⚙️ Persyaratan Sistem

- **PHP**: Versi 8.1 atau lebih baru
  - Ekstensi PHP wajib: `pdo_mysql`, `curl`, `json`, `mbstring`, `openssl`, `fileinfo`, `gd`
- **Database**: MySQL 5.7+ atau MariaDB 10.4+
- **Web Server**: Apache dengan modul `mod_rewrite` dan `mod_headers` aktif (misalnya XAMPP)

---

## 🚀 Panduan Instalasi Lokal (XAMPP)

### 1. Salin Konfigurasi Lingkungan (`.env`)
Salin file `.env.example` menjadi `.env` pada root project:
```bash
cp .env.example .env
```
Sesuaikan nilainya sesuai dengan konfigurasi lokal Anda (misal untuk XAMPP default):
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=bps_solsel
DB_USER=root
DB_PASS=

APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/administators.bps1310.cloud/public
```

### 2. Impor Skema Database
Impor file [`database/schema.sql`](file:///c:/xampp/htdocs/administators.bps1310.cloud/database/schema.sql) ke server MySQL Anda melalui phpMyAdmin atau terminal:
```bash
mysql -u root -p < database/schema.sql
```

Akun administrator default yang otomatis dibuat:
- **Username**: `admin`
- **Password**: `admin123`
*(Sangat disarankan segera mengganti password saat pertama kali masuk)*

---

## 🔒 Keamanan & Praktik Sebelum Push ke Git

Project ini telah diamankan dan disiapkan agar aman di-push ke GitHub / GitLab:

1. **Pemisahan Kredensial Rahasia (`.env`)**:
   - Password database, Gmail App Password, dan Gemini API Key dipisahkan ke dalam `.env`.
   - File `.env`, file `*.log`, dan kredensial rahasia otomatis diabaikan oleh [`.gitignore`](file:///c:/xampp/htdocs/administators.bps1310.cloud/.gitignore).
   - Template aman disediakan pada [`.env.example`](file:///c:/xampp/htdocs/administators.bps1310.cloud/.env.example).
2. **Proteksi Otentikasi Terpusat**:
   - Seluruh halaman panel admin (`index.php`, `layanan.php`, `kategori.php`, `kelola_admin.php`, `pengaturan.php`, penambahan/pengubahan/penghapusan) dilindungi melalui [`config/auth.php`](file:///c:/xampp/htdocs/administators.bps1310.cloud/config/auth.php).
3. **Pencegahan Serangan CSRF (Cross-Site Request Forgery)**:
   - Formulir penting dilengkapi dengan token CSRF (`csrf_token()`, `csrf_field()`, dan `csrf_verify()`).
4. **Pencegahan SQL Injection**:
   - Semua operasi database menggunakan PDO Prepared Statements dengan parameter binding.
5. **Keamanan Upload Berkas**:
   - Validasi ketat format gambar (`jpg`, `jpeg`, `png`, `webp`) dengan pemeriksaan `getimagesize()` dan batasan ukuran 5MB.
   - Folder `public/uploads/` diproteksi dengan `.htaccess` khusus yang mematikan eksekusi skrip PHP.
6. **Keamanan Sesi & HTTP Headers**:
   - Sesi menggunakan flag cookie `HttpOnly`, `SameSite=Lax`, dan `Secure` (saat HTTPS aktif).
   - Proteksi header HTTP (`X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`).

---

## 🌐 Panduan Deployment / Hosting ke VPS

Saat nanti mendeploy project ini ke VPS (Ubuntu / Debian / AlmaLinux dengan Apache/Nginx/aaPanel/cPanel):

### 1. Buat Database di MySQL VPS
```sql
CREATE DATABASE `bps-solsel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'admin-bps-solsel'@'localhost' IDENTIFIED BY 'PasswordKuatAnda123!';
GRANT ALL PRIVILEGES ON `bps-solsel`.* TO 'admin-bps-solsel'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Impor Database Dump Produksi
Jalankan impor dump database:
```bash
mysql -u admin-bps-solsel -p bps-solsel < database/bps_solsel.sql
```

### 3. Buat File `.env` di VPS
Salin template `.env.example` menjadi `.env` di root direktori project pada VPS:
```bash
cp .env.example .env
nano .env
```
Sesuaikan variabel berikut untuk VPS:
```env
# Mode Produksi VPS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://administators.bps1310.cloud

# Koneksi Database VPS
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=bps-solsel
DB_USER=admin-bps-solsel
DB_PASS=PasswordKuatAnda123!

# URL Endpoint API Produksi
API_URL=https://administators.bps1310.cloud/api/layanan.php
```

