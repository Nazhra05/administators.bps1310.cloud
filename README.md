# Dashboard Admin & Backend API — BPS Kabupaten Solok Selatan

Aplikasi berbasis web (PHP 8.x + MariaDB) yang berfungsi sebagai **Dashboard Administrasi** internal BPS Kabupaten Solok Selatan (App A) dan penyedia **REST API** katalog layanan & kategori statistik untuk aplikasi portal publik **App B** ([sipasti.bps1310.cloud](https://github.com/Nazhra05/sipasti.bps1310.cloud)).

---

## 📋 Daftar Isi

1. [Fitur Utama](#-fitur-utama)
2. [Arsitektur & Tumpukan Teknologi](#-arsitektur--tumpukan-teknologi)
3. [Prasyarat Sistem](#-prasyarat-sistem)
4. [Instalasi & Menjalankan Lokal](#-instalasi--menjalankan-lokal)
5. [Database: Migrasi & Data Seed](#-database-migrasi--data-seed)
6. [Akun Pengujian (Dev Seed)](#-akun-pengujian-dev-seed)
7. [Menjalankan Pengujian & Quality Gate](#-menjalankan-pengujian--quality-gate)
8. [Struktur Direktori](#-struktur-direktori)
9. [Catatan Server & Lingkungan Produksi](#-catatan-server--lingkungan-produksi)

---

## 🌟 Fitur Utama

- **Dashboard Ringkasan Interaktif:** Metrik statistik layanan (total, publik, VPN, pending review, kategori), pintasan cepat aksi umum, dan feed riwayat audit log.
- **Manajemen Katalog Layanan:** CRUD layanan dengan alur persetujuan (*workflow* `draft` ➔ `pending` ➔ `published`), sanitasi alias pencarian, proteksi unggah logo dengan re-encoding GD, serta ekspor & impor CSV tervalidasi.
- **Kategori Statistik Terstruktur:** Pengelolaan kategori dinamis (Distribusi, Produksi, Neraca, Sosial, TI, Umum, Diseminasi) dengan mekanisme soft-delete.
- **REST API Kompatibilitas Ganda:**
  - Endpoint V1 RESTful (`/api/v1/*`) dengan amplop JSON seragam (`{status, data, error}`).
  - Endpoint Warisan / Legacy (`/api/layanan.php`, `/api/kategori.php`, `/api/website.php`, `/api/auth/loginPortal.php`) untuk menjaga kompatibilitas mutlak dengan App B.
- **Keamanan Berlapis:** Proteksi CSRF berbasis token, proteksi brute-force lockout, session hijacking prevention (`HttpOnly`, `SameSite=Lax`, regenerasi ID sesi), otorisasi RBAC berbasis role (`Admin` & `User`), dan audit logging terpusat.
- **Personalisasi Pegawai:** Endpoint bookmark privat, preferensi riwayat pencarian, dan lencana statistik aktivitas personal.

---

## 🛠️ Arsitektur & Tumpukan Teknologi

- **Backend:** Native PHP 8.1+ (PSR-12), FastRoute (`nikic/fast-route`), PDO MariaDB.
- **Frontend Dashboard:** Vanilla HTML5, CSS kustom responsif, JavaScript modular tanpa framework berat.
- **Database:** MariaDB 10.4+ / MySQL 8.0+.
- **Web Server Lokal:** Apache 2.4 (XAMPP for Windows) dengan *front-controller pattern* via `.htaccess`.
- **Quality Tools:** PHPUnit 10, PHPStan (Level 5), PHP-CS-Fixer, Vitest (black-box HTTP & API contract).

---

## ⚙️ Prasyarat Sistem

1. **PHP:** Versi 8.1 atau lebih baru (disarankan PHP 8.3).
   - Ekstensi PHP wajib: `pdo_mysql`, `curl`, `json`, `mbstring`, `openssl`, `fileinfo`, `gd`.
2. **Web Server:** Apache 2.4 dengan modul `mod_rewrite` dan `mod_headers` aktif (contoh: XAMPP).
3. **Database:** MariaDB 10.4+ atau MySQL 8.0+.
4. **Composer:** Versi 2.x untuk autoloading dan dependensi dev.
5. **Node.js & npm:** Versi 18+ (hanya untuk menjalankan pengujian integrasi Vitest).
6. **Git:** Versi 2.9+ (untuk dukungan Git Hooks lokal).

---

## 🚀 Instalasi & Menjalankan Lokal

### 1. Kloning Repositori & Instal Dependensi Composer
```powershell
git clone https://github.com/Nazhra05/administators.bps1310.cloud.git adminDashboard
cd adminDashboard
composer install
```

### 2. Pasang Git Hooks Lokal (Task 8.4)
Untuk mencegah kebocoran file rahasia (`.env`), dump SQL tidak sah, atau kunci kriptografis:
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\install-hooks.ps1
```

### 3. Konfigurasi Environment (`.env`)
Salin berkas template lingkungan:
```powershell
copy .env.example .env
```
Sesuaikan konfigurasi koneksi database dan kredensial lokal Anda:
```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://admin.test
APP_BASE_PATH=

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=bps_solsel_dev
DB_USER=bps_app
DB_PASS="password_database_anda"

DB_MIGRATOR_USER=bps_migrator
DB_MIGRATOR_PASS="password_migrator_anda"

API_KEY_APP_B="kunci_api_app_b_anda"
```

### 4. Konfigurasi VirtualHost Apache (Direkomendasikan)
Tambahkan ke berkas `httpd-vhosts.conf` XAMPP Anda:
```apache
<VirtualHost *:80>
    ServerName admin.test
    DocumentRoot "C:/xampp/htdocs/adminDashboard/public"
    <Directory "C:/xampp/htdocs/adminDashboard/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
Tambahkan entri ke `C:\Windows\System32\drivers\etc\hosts`:
```text
127.0.0.1 admin.test
```
*(Fallback bila tanpa vhost: akses via `http://localhost/adminDashboard/public/`)*

---

## 🗄️ Database: Migrasi & Data Seed

Proyek ini menggunakan pemisahan hak akses terkontrol:
- **`bps_app`:** Hak terbatas CRUD (`SELECT`, `INSERT`, `UPDATE`, `DELETE`) untuk eksekusi aplikasi harian.
- **`bps_migrator`:** Hak DDL penuh untuk migrasi skema dan reset database pengujian.

### Inisialisasi Database Lokal (Satu Kali)
Eksekusi skrip inisialisasi basis data di MariaDB (sebagai root):
```powershell
mysql -u root < docs/xampp/create-db.sql
```

### Menjalankan Migrasi Skema
Terapkan seluruh migrasi pending ke basis data dev:
```powershell
php scripts/migrate.php --up
```
Untuk melihat status migrasi:
```powershell
php scripts/migrate.php --status
```

### Memuat Data Dev Seed
Untuk memuat data seed dev contoh (8 layanan, 7 kategori, akun pengujian):
```powershell
mysql -u bps_migrator -p bps_solsel_dev < database/seeds/dev_seed.sql
```

---

## 👥 Akun Pengujian (Dev Seed)

Data seed pengujian menyediakan beberapa akun dengan role berbeda untuk simulasi lokal:

| Username | Role | Unit / Wewenang | Keterangan Password Dev |
|---|---|---|---|
| `admin_dev` | `admin` | IT / Administrator | Gunakan password dev yang dikonfigurasi pada environment lokal |
| `pimpinan_dev` | `admin` | Pimpinan / Approval | Memiliki hak verifikasi dan persetujuan layanan |
| `pegawai_dev` | `user` | Neraca / Staf | Akun pegawai portal (hanya akses via API App B) |

> ⚠️ **PENTING:** Demi keamanan, repositori ini **TIDAK PERNAH** menyimpan password produksi nyata. Seluruh hash password di-generate menggunakan Bcrypt (`PASSWORD_BCRYPT`, cost 10) dengan upgrade otomatis jika mendeteksi format hash lama.

---

## 🧪 Menjalankan Pengujian & Quality Gate

Proyek ini menerapkan *delivery gate* ketat sebelum setiap perubahan kode dapat digabungkan:

### 1. Composer Check (Semua Linter, Analisis Statis, & PHPUnit)
Menjalankan linting PSR-12, analisis statis PHPStan Level 5, dan 159 unit/integration tests:
```powershell
composer check
```
Perintah parsial yang tersedia:
```powershell
composer lint      # php-cs-fixer fix --dry-run --diff (PSR-12)
composer analyse   # phpstan analyse (Level 5)
composer test      # phpunit (159 tests unit & integrasi)
```

### 2. Pengujian Black-Box & Kontrak API (Vitest)
Menjalankan 60 pengujian fungsional interaksi HTTP dan penguncian kontrak API App B:
```powershell
npx vitest run
```

### 3. Reset Database Pengujian
Untuk mereset basis data pengujian `bps_solsel_test` secara bersih ke kondisi seed awal:
```powershell
php scripts/test-db-reset.php
```

---

## 📁 Struktur Direktori

```text
adminDashboard/
├── .agents/                 # ECC Agent rules, workflows, dan skills
├── .githooks/               # Git hook pre-commit versi repositori
├── .github/workflows/       # Otomatisasi CI GitHub Actions
├── config/                  # Konfigurasi sistem, environment, dan PDO
│   ├── auth.php             # Helper autentikasi & middleware RBAC
│   └── config.php           # Inisialisasi env, session, dan database
├── database/                # Skema dan versioning basis data
│   ├── migrations/          # 14 berkas migrasi berpasangan (.sql & .down.sql)
│   ├── seeds/               # Data awal dev_seed.sql
│   └── schema.sql           # DDL referensi baseline
├── docs/                    # Dokumentasi arsitektur, QA, & integrasi
│   ├── adr/                 # Architecture Decision Records
│   ├── API_CONTRACT.md      # Kontrak API resmi untuk App B
│   ├── ARCHITECTURE.md      # Diagram alur request & skema
│   ├── QA_CHECKLIST.md      # Checklist QA manual & uji lintas aplikasi
│   ├── PRIVACY_NOTES.md     # Kebijakan data pribadi & retensi
│   └── SERVER_NOTES.md      # Asumsi lingkungan & rekonsiliasi server
├── public/                  # DocumentRoot web server (satu-satunya akses publik)
│   ├── index.php            # Front controller tunggal
│   ├── .htaccess            # Routing rewrite & pemblokiran file PHP mentah
│   ├── assets/              # File statis (CSS, JS, logo resmi)
│   └── uploads/             # Unggahan gambar logo pengguna (.htaccess anti-PHP)
├── scripts/                 # Utilitas CLI (migrasi, reset DB, cleanup, install hooks)
├── src/                     # Kode sumber aplikasi berorientasi objek (PSR-4)
│   ├── Http/                # Controllers (Admin, API V1/Legacy, Layanan, Auth) & Middlewares
│   ├── Services/            # Logika bisnis (Layanan, Password, LoginThrottle, Mailer)
│   ├── Support/             # Komponen pendukung (Database, Session, Csrf, Validator, Logger)
│   └── views/               # Template UI bersih (layouts, partials, modul)
├── tests/                   # Suite pengujian otomatis (Unit, Integration, Vitest)
├── routes.php               # Pendaftaran rute URL aplikasi dan middleware pipeline
├── composer.json            # Manifest dependensi & skrip Composer
└── phpunit.xml              # Konfigurasi test runner PHPUnit
```

---

## 🌐 Catatan Server & Lingkungan Produksi

> ℹ️ **Catatan Tahap Pengembangan:**  
> Seluruh pengembangan aktif saat ini berfokus pada lingkungan **lokal XAMPP Apache + MariaDB**. Persiapan server produksi (Nginx, Docker, VPS, Linux permissions, rilis otomatis) secara sengaja **DITUNDA (Fase 10)**.
> 
> Seluruh asumsi perbedaan lingkungan, konfigurasi web server, dan daftar periksa transisi server dicatat secara komprehensif pada dokumen [**`docs/SERVER_NOTES.md`**](docs/SERVER_NOTES.md).

---

## 📜 Lisensi & Kepemilikan

Hak Cipta © 2026 Badan Pusat Statistik Kabupaten Solok Selatan.  
Seluruh hak cipta dilindungi undang-undang.
