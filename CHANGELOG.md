# Changelog

Semua perubahan penting pada proyek **Dashboard Admin & Backend API BPS Kabupaten Solok Selatan** (App A) didokumentasikan dalam berkas ini. Format penulisan mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/).

---

## [0.9.0-rc1] - 2026-10-09

Rilis kandidat pertama (*Release Candidate*) menandai penyelesaian modernisasi penuh arsitektur App A, penguncian kontrak API untuk App B, penguatan keamanan berlapis, dan kelulusan seluruh gerbang kualitas otomatis (*zero regressions*).

### Ditambahkan (Added)
- **Arsitektur Front Controller:** Mesin perutean FastRoute terpusat di [`src/Http/Router.php`](file:///c:/xampp/htdocs/adminDashboard/src/Http/Router.php) melalui satu-satunya gerbang publik [`public/index.php`](file:///c:/xampp/htdocs/adminDashboard/public/index.php).
- **Sistem Migrasi Reversibel:** Skrip CLI [`scripts/migrate.php`](file:///c:/xampp/htdocs/adminDashboard/scripts/migrate.php) dengan 14 berkas migrasi berpasangan (`.sql` dan `.down.sql`), mendukung perintah `--up`, `--down=N`, dan `--status`.
- **REST API V1 (`/api/v1/*`):** Endpoint standar RESTful dengan amplop JSON seragam (`{status, data, error}`) untuk katalog layanan, kategori, autentikasi sesi portal 8 jam, dan pengaturan kontak.
- **Modul Personalisasi Pegawai (`/api/v1/me/*`):** Manajemen bookmark privat terisolasi, preferensi layanan tersemat (*pinned*), riwayat pencarian, dan lencana apresiasi aktivitas pegawai tanpa perbandingan publik.
- **Health Check Endpoint:** Endpoint ringan `GET /api/v1/health` dengan verifikasi koneksi basis data singkat (timeout 2 detik) tanpa membocorkan detail infrastruktur server.
- **Alur Kerja Persetujuan Layanan (Approval Workflow):** Siklus status layanan `draft` ➔ `pending` ➔ `published`/`rejected` dengan catatan penolakan dan hak verifikasi oleh role Admin / Ketua Tim.
- **Ekspor & Impor CSV Layanan:** Ekspor CSV dengan UTF-8 BOM untuk kompatibilitas Microsoft Excel, template unduhan, dan impor CSV massal dengan validasi baris demi baris serta audit logging.
- **Keamanan Git Hooks & CI:** Script [`.githooks/pre-commit`](file:///c:/xampp/htdocs/adminDashboard/.githooks/pre-commit) untuk menolak file `.env`, dump SQL liar, dan private key, serta workflow GitHub Actions [`.github/workflows/ci.yml`](file:///c:/xampp/htdocs/adminDashboard/.github/workflows/ci.yml).
- **Suite Pengujian Komprehensif:** 159 pengujian PHPUnit (unit, integrasi, migrasi naik-turun, otorisasi) dan 60 pengujian Vitest (HTTP black-box & kontrak API App B).
- **Dokumentasi Lengkap:** [`docs/ARCHITECTURE.md`](file:///c:/xampp/htdocs/adminDashboard/docs/ARCHITECTURE.md), [`docs/API_CONTRACT.md`](file:///c:/xampp/htdocs/adminDashboard/docs/API_CONTRACT.md), [`docs/QA_CHECKLIST.md`](file:///c:/xampp/htdocs/adminDashboard/docs/QA_CHECKLIST.md), [`docs/PRIVACY_NOTES.md`](file:///c:/xampp/htdocs/adminDashboard/docs/PRIVACY_NOTES.md), [`docs/SERVER_NOTES.md`](file:///c:/xampp/htdocs/adminDashboard/docs/SERVER_NOTES.md), dan [`docs/GO_NO_GO.md`](file:///c:/xampp/htdocs/adminDashboard/docs/GO_NO_GO.md).

### Diubah (Changed)
- **Refactoring Kode Prosedural ke MVC:** Seluruh file fisik lama di `public/*.php` dimigrasikan ke Controller berorientasi objek (`src/Http/Controllers/`) dan view template modular (`src/views/`).
- **Modernisasi Hash Password:** Password disimpan menggunakan algoritma Bcrypt (`PASSWORD_BCRYPT`, cost 10) dengan deteksi otomatis dan peningkatan (*auto-upgrade*) format SHA-256 lama saat login berhasil.
- **Pengerasan Sesi:** Siklus hidup sesi dashboard diamankan dengan flag cookie `HttpOnly`, `SameSite=Lax`, regenerasi ID sesi pada setiap login, batas inaktivitas 30 menit, dan batas absolut 8 jam.
- **Pembersihan Berkas Unggahan GD:** Gambar logo diolah ulang melalui fungsi GD Library (`imagecreatefrom*` dan `image*`) untuk meremove metadata EXIF atau polyglot PHP payload.
- **Analisis Kualitas Kode:** Dikonfigurasi [`.php-cs-fixer.php`](file:///c:/xampp/htdocs/adminDashboard/.php-cs-fixer.php) standar PSR-12 dan ditingkatkan level analisis statis PHPStan ke Level 5 (0 error).

### Diperbaiki (Fixed)
- **BUG-001 (Kategori):** Penghapusan kategori mewajibkan metode HTTP POST dan token CSRF valid (`hapus_kategori.php`).
- **BUG-002 (Layanan):** Penghapusan layanan mewajibkan metode HTTP POST dan token CSRF valid (`hapus_layanan.php`).
- **BUG-003 & BUG-004 (Otorisasi & Akun):** Celah IDOR pada verifikasi pengguna diperbaiki dan hash password portal diperbarui ke Bcrypt.
- **BUG-006 (Ubah Password):** Form ubah password pada menu pengaturan memverifikasi password lama dan mewajibkan password baru minimal 10 karakter.

### Keamanan (Security)
- Pembersihan riwayat Git dari berkas dump basis data riil produksi (`database/bps_solsel.sql`).
- Isolasi hak akses database: `bps_app` dibatasi pada operasi CRUD, sementara DDL hanya diizinkan untuk `bps_migrator`.
- Penonaktifan eksekusi skrip PHP di folder publik [`public/uploads/.htaccess`](file:///c:/xampp/htdocs/adminDashboard/public/uploads/.htaccess).
- Penanganan error aman: `APP_DEBUG=false` menampilkan halaman error 500 dengan `request_id` unik tanpa membocorkan rincian teknis kode.
