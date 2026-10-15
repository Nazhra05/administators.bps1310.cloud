# Dashboard Admin BPS Solok Selatan

Aplikasi web administrasi internal untuk pengelolaan katalog layanan, kategori statistik, repositori data, dan autentikasi akun pada Badan Pusat Statistik Kabupaten Solok Selatan.

## Fitur utama

- Pengelolaan katalog layanan kedinasan dan tautan aplikasi internal
- Pengelompokan layanan berdasarkan kategori statistik
- Manajemen repositori portal dan repositori koleksi pengguna
- Pengelolaan akun administrator dan verifikasi akun pegawai (RBAC)
- Layanan REST API terproteksi untuk integrasi portal intranet
- Proteksi formulir dengan token CSRF dan pengamanan sesi terpusat

## Prasyarat sistem

- PHP 8.1 atau lebih baru (ekstensi: `pdo_mysql`, `curl`, `json`, `mbstring`, `openssl`, `fileinfo`, `gd`)
- MySQL 5.7+ atau MariaDB 10.4+
- Web server Apache dengan modul `mod_rewrite` dan `mod_headers` aktif

## Instalasi lokal

### 1. Salin konfigurasi environment

Buat file konfigurasi `.env` dari template yang disediakan:
```bash
cp .env.example .env
```

Buka file `.env` lalu sesuaikan konfigurasi basis data lokal Anda:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=bps_solsel
DB_USER=root
DB_PASS=

APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/Dashboard_Admin/public
```

### 2. Impor skema database

Jalankan perintah impor skema tabel awal dari [database/schema.sql](file:///d:/BPS/Rangkiang/Dashboard_Admin/database/schema.sql):
```bash
mysql -u root -p < database/schema.sql
```

File skema menyertakan akun admin awal untuk setup pertama:
- Username: `admin`
- Password: password bawaan tercantum di dalam berkas [database/schema.sql](file:///d:/BPS/Rangkiang/Dashboard_Admin/database/schema.sql). Segera ubah kata sandi melalui menu Pengaturan setelah login pertama kali.

### 3. Konfigurasi web server

Arahkan *DocumentRoot* web server Apache ke direktori `public/`. Jika menggunakan folder subdirektori (misalnya htdocs XAMPP), pastikan modul `mod_rewrite` aktif agar aturan perutean berkas pada `.htaccess` berjalan normal.

## Pengujian otomatis

Proyek ini dilengkapi pengujian otomatis fungsional dan endpoint. Untuk menjalankan pengujian:
```bash
npm install
npm test
```

## Catatan keamanan

1. Kredensial rahasia seperti kata sandi basis data dan kunci API disimpan murni pada file `.env` yang dikecualikan dari Git melalui `.gitignore`.
2. Seluruh permintaan perubahan data (POST, PUT, DELETE) mewajibkan validasi token CSRF.
3. Kueri basis data memanfaatkan PDO prepared statements untuk mencegah injeksi SQL.
4. Unggahan berkas divalidasi berdasarkan tipe konten asli serta dibatasi ukurannya. Direktori unggahan mematikan eksekusi skrip PHP secara langsung.

## Panduan deployment VPS

Langkah penyiapan di server produksi Linux (Apache atau Nginx):

### 1. Buat database dan pengguna

```sql
CREATE DATABASE nama_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pengguna_db'@'localhost' IDENTIFIED BY 'isi_password_kuat_disini';
GRANT ALL PRIVILEGES ON nama_database.* TO 'pengguna_db'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Impor skema database

Jalankan perintah impor skema:
```bash
mysql -u pengguna_db -p nama_database < database/schema.sql
```

### 3. Konfigurasi file .env produksi

Salin template konfigurasi:
```bash
cp .env.example .env
```

Sesuaikan nilai variabel:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://nama-domain-anda.bps.go.id

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=nama_database
DB_USER=pengguna_db
DB_PASS=isi_password_kuat_disini

API_URL=https://nama-domain-anda.bps.go.id/api/layanan.php
```

## Lisensi dan kepemilikan

Hak Cipta (c) Badan Pusat Statistik Kabupaten Solok Selatan. Digunakan khusus untuk keperluan kedinasan internal.
