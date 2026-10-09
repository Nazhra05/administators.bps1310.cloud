# Catatan Rekonsiliasi Server (SERVER_NOTES.md)

Dokumen ini adalah **dokumen hidup** untuk mencatat seluruh asumsi, konfigurasi, dan perilaku spesifik lingkungan lokal (XAMPP / Windows) yang harus diterjemahkan saat aplikasi dipindahkan ke lingkungan server produksi nantinya (Fase 10).

---

## 1. Asumsi Lingkungan Lokal

- **Sistem Operasi:** Windows 10/11 x64 (Shell: PowerShell)
- **Web Server:** Apache 2.4.x (XAMPP for Windows, binary: `C:\xampp\apache\bin\httpd.exe`)
- **PHP Version:** PHP 8.2 / 8.3 (VC16 x64 Thread Safe)
- **Ekstensi PHP Aktif:**
  - `pdo_mysql` (Database PDO)
  - `mbstring` (Manipulasi string multibyte)
  - `curl` (HTTP client untuk integrasi API)
  - `fileinfo` (Validasi MIME type berkas upload)
  - `gd` (Pemrosesan dan verifikasi gambar/logo)
  - `openssl` (Kriptografi, token generator, SMTP TLS)
  - `json` (Serialisasi data API)
  - `zip` & `dom` (Dukungan Composer dan XML)
- **Path Proyek:**
  - Root: `C:\xampp\htdocs\adminDashboard`
  - Document Root Web: `C:\xampp\htdocs\adminDashboard\public`
  - Storage logs: `C:\xampp\htdocs\adminDashboard\storage\logs`
  - Storage uploads: `C:\xampp\htdocs\adminDashboard\storage\uploads`
- **Virtual Host:** `http://admin.test/` (Port 80)
- **Database Server:** MariaDB 10.4.x / MySQL 8.x pada `127.0.0.1:3306`
  - Database Pengembangan: `bps_solsel_dev`
  - Database Pengujian: `bps_solsel_test`
  - User Aplikasi: `bps_app` (hak akses DML: `SELECT`, `INSERT`, `UPDATE`, `DELETE`; `DROP TABLE` ditolak)
  - User Migrasi: `bps_migrator` (hak akses DDL & DML penuh)
- **Testing Mail Server:** Mailpit (`127.0.0.1:1025` SMTP, Web UI `http://localhost:8025`)

---

## 2. Aturan Khusus Apache (`.htaccess` & VirtualHost)

- **Modul Apache yang Dibutuhkan:**
  - `mod_rewrite`: Untuk penulisan ulang URL dan front controller routing (`public/index.php`).
  - `mod_headers`: Untuk menetapkan header keamanan (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`).
- **Direktif VirtualHost:**
  - Mengarahkan DocumentRoot langsung ke folder `public/`, bukan folder root proyek:
    ```apache
    DocumentRoot "C:/xampp/htdocs/adminDashboard/public"
    <Directory "C:/xampp/htdocs/adminDashboard/public">
        AllowOverride All
        Require all granted
    </Directory>
    ```
- **Front Controller & Proteksi Eksekusi PHP (`public/.htaccess`):**
  - Mengalihkan semua request non-file ke `index.php`, memblokir ekstensi sensitif, dan membatasi eksekusi file PHP **hanya** pada `index.php`:
    ```apache
    Options -Indexes -MultiViews
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]

    <FilesMatch "^\.|\.(sql|log|bak|old|ini|md|lock|sh|env)$">
        Require all denied
    </FilesMatch>

    <FilesMatch "\.(php|phtml|phar)$">
        Require all denied
    </FilesMatch>
    <Files "index.php">
        Require all granted
    </Files>
    ```
  - *Padanan Nginx:*
    `location / { try_files $uri /index.php?$query_string; }`
    `location = /index.php { include fastcgi_params; fastcgi_pass ...; }`
    `location ~ \.php$ { return 404; }`
- **Proteksi Folder Uploads (`public/uploads/.htaccess`):**
  - Mencegah eksekusi script PHP yang diunggah pengguna lewat `.htaccess`:
    ```apache
    php_flag engine off
    Options -Indexes -ExecCGI
    <FilesMatch "\.(php|phtml|phar|php[0-9])$">
        Require all denied
    </FilesMatch>
    ```
  - *Padanan Nginx:*
    `location ^~ /uploads/ { location ~ \.php$ { return 403; } }`

---

## 3. Perilaku Khusus Windows

- **Pemisah Direktori (Path Separator):**
  - Windows menggunakan backslash `\`, sedangkan Linux menggunakan forward slash `/`.
  - Kode PHP **wajib** menggunakan konstanta `DIRECTORY_SEPARATOR` atau menormalisasi path dengan `/` agar portabel.
- **Sensitivitas Huruf (Case Sensitivity):**
  - Filesystem Windows (NTFS) bersifat *case-insensitive* (tidak membedakan huruf besar/kecil), sedangkan Linux (ext4) bersifat *case-sensitive*.
  - Namespace PSR-4 (`App\...` -> `src/...`) dan pemanggilan nama berkas `require`/`include` harus persis sama huruf besar-kecilnya agar tidak gagal saat dipindahkan ke Linux.
- **Line Ending:**
  - Git dikonfigurasi dengan `.editorconfig` dan `.gitattributes` (`* text=auto eol=lf`) untuk mencegah format baris CRLF Windows merusak eksekusi script di Linux.
- **Resolusi Domain Lokal:**
  - Penambahan domain `admin.test` dilakukan di berkas Windows: `C:\Windows\System32\drivers\etc\hosts`.

---

## 4. Yang Harus Diterjemahkan Saat Pindah ke Server

| Komponen | Lingkungan Lokal (XAMPP/Windows) | Lingkungan Server Target (Linux) |
|---|---|---|
| **Web Server** | Apache 2.4 mod_php di XAMPP | Nginx + PHP-FPM (atau Apache mpm_event + PHP-FPM) |
| **Path Proyek** | `C:\xampp\htdocs\adminDashboard` | `/var/www/adminDashboard` (atau direktori standar Linux) |
| **Izin Folder** | Windows ACL (otomatis writable) | `chown -R www-data:www-data storage/ public/uploads/`<br>`chmod -R 775 storage/` |
| **Kredensial Database** | User `bps_app` & `bps_migrator` lokal | Kredensial produksi kuat yang diinjeksi via `.env` server |
| **Virtual Host** | `docs/xampp/httpd-vhosts.sample.conf` | Konfigurasi Nginx Server Block (port 80/443 dengan SSL) |
| **Pengiriman Email** | Mailpit localhost port 1025 | Server SMTP resmi BPS / Google Workspace SMTP dengan TLS |
| **Sertifikat SSL** | HTTP biasa (`http://admin.test`) | HTTPS dengan sertifikat SSL valid (Let's Encrypt / Certbot) |
| **Scheduler / Cron** | Manual script CLI PowerShell | Linux Crontab (`cron.d`) untuk menjalankan task terjadwal |

---

## 5. Pertanyaan Terbuka untuk Server (Untuk Dikonfirmasi Nanti)

1. Apakah server produksi menggunakan Nginx mandiri (PHP-FPM) atau Apache di belakang reverse proxy?
2. Apakah versi PHP di server produksi sudah PHP 8.1+ atau masih versi lama?
3. Apakah ada batasan alokasi memori (`memory_limit`) atau durasi eksekusi (`max_execution_time`) di hosting produksi?
4. Apakah direktori `public/uploads/` akan disimpan pada local disk server atau object storage (S3/MinIO)?
5. Apakah akses jaringan keluar (outgoing HTTPS) dari server produksi dibuka untuk API eksternal (AI / SMTP)?
