# Panduan agen dan aturan pengembangan
## Dashboard Admin: BPS Kabupaten Solok Selatan

Aturan teknis dan panduan operasional pengembangan sistem Dashboard Admin (App A) BPS Kabupaten Solok Selatan.

## 1. Identitas dan arsitektur sistem

- Proyek: Dashboard Admin BPS Kabupaten Solok Selatan (`administators.bps1310.cloud`)
- Arsitektur: Two-tier terpisah (App A Backend API dan Web Console terhubung ke App B Next.js Portal)
- Stack:
  - Bahasa dan runtime: PHP 8.1+ (PDO MySQL, mbstring, openssl, curl)
  - Web server: Apache 2.4+ (`.htaccess`, `mod_rewrite`, `mod_headers`)
  - Basis data: MariaDB 10.4+ / MySQL 5.7+ (`bps_solsel` atau `bps-solsel`)
  - Pengujian: Vitest dan Happy-DOM (Node.js runtime)
  - Mailer: PHPMailer

### Struktur direktori

```text
Dashboard_Admin/
├── config/
│   ├── config.php            # Muat konfigurasi .env, konstanta sistem, dan koneksi PDO
│   ├── auth.php              # Autentikasi sesi dan kendali akses peran (RBAC)
│   └── user_repo.php         # Pengelolaan data repositori pegawai
├── database/
│   ├── schema.sql            # Skema DDL tabel dasar
│   ├── bps_solsel.sql        # Template data awal
│   └── bps-solsel (1).sql    # Snapshot database produksi
├── public/
│   ├── api/                  # REST API dengan validasi header X-API-KEY
│   │   ├── layanan.php       # Data layanan publik dan internal
│   │   ├── kategori.php      # Data kategori layanan statistik
│   │   ├── website.php       # Tautan situs publik
│   │   ├── user_repo.php     # Repositori file, link, drive, dan aplikasi pegawai
│   │   └── auth/             # Autentikasi untuk portal frontend Next.js
│   ├── index.php             # Beranda admin dan ringkasan layanan
│   ├── layanan.php           # Olah data layanan (tambah, edit, hapus)
│   ├── kategori.php          # Olah data kategori statistik
│   ├── kelola_admin.php      # Verifikasi dan persetujuan akun administrator baru
│   ├── kelola_user.php       # Manajemen akun pegawai (peran USER)
│   ├── dashboard_user.php    # Beranda pegawai
│   ├── repositori_portal.php # Repositori layanan internal kantor
│   ├── repositori_private.php# Repositori privat pegawai
│   ├── switch_role.php       # Pindah peran antara Admin dan User
│   ├── switch_account.php    # Pindah akun pengguna aktif
│   ├── login.php / logout.php# Masuk dan keluar sistem
│   └── verify_email.php      # Aktivasi token pendaftaran akun
├── tests/                    # Berkas uji integrasi Vitest
└── .agents/                  # Konfigurasi skill dan aturan agen
```

## 2. Matriks pemilihan skill

Pilih skill berdasarkan kebutuhan pekerjaan:

| Kebutuhan | Skill | Penerapan |
| :--- | :--- | :--- |
| Klarifikasi kebutuhan | `interview-me` | Pertanyaan bertahap saat instruksi pengguna belum lengkap |
| Eksplorasi konsep | `idea-refine` | Menguji opsi teknis sebelum menyusun rencana kerja |
| Penyusunan spesifikasi | `spec-driven-development` | Menuliskan kontrak API atau modul sebelum membuat kode |
| Standar kualitas | `constraint-driven-development` | Menetapkan ambang pengujian dan performa |
| Perencanaan tugas | `planning-and-task-breakdown` | Membagi fitur ke dalam urutan langkah terarah |
| Implementasi bertahap | `incremental-implementation` | Mengerjakan kode per bagian kecil dengan verifikasi langsung |
| Desain REST API | `api-and-interface-design` | Menentukan route, format JSON, dan kode status HTTP |
| Pengujian berbasis TDD | `test-driven-development` | Menulis tes sebelum menulis fungsi baru |
| Investigasi kendala | `debugging-and-error-recovery` | Mencari sumber masalah saat query SQL atau tes gagal |
| Pengujian DOM | `browser-testing-with-devtools` | Memeriksa respons halaman, status cookie, dan jaringan |
| Penguatan keamanan | `security-and-hardening` | Validasi input, pencegahan SQL injection, XSS, CSRF, dan RBAC |
| Optimasi performa | `performance-optimization` | Penambahan index database dan optimasi query PDO |
| Review kode | `code-review-and-quality` | Pemeriksaan kode sebelum penggabungan |
| Penulisan alami | `humanizer` | Menghapus pola tulisan AI pada dokumen dan catatan rilis |
| Penyederhanaan kode | `code-simplification` | Memotong logika berulang tanpa mengubah perilaku sistem |
| Solusi minimal | `ponytail` | Mengutamakan fungsi bawaan PHP ketimbang menambah library |
| Audit kode tidak terpakai | `ponytail-audit` | Menghapus kode mati dan dependensi berlebih |
| Alur git | `git-workflow-and-versioning` | Pembuatan commit terstruktur dan pengelolaan branch |
| Catatan keputusan | `documentation-and-adrs` | Mendokumentasikan keputusan arsitektur penting |
| Pemetaan codebase | `graphify` | Melihat visualisasi hubungan antar berkas dan tabel |

## 3. Ringkasan fungsi skill

### 3.1 Analisis dan perencanaan
- `interview-me`: Menggali kebutuhan pengguna lewat dialog terfokus sebelum implementasi.
- `idea-refine`: Memeriksa kelemahan rencana dan membandingkan alternatif pendekatan.
- `spec-driven-development`: Mendefinisikan kontrak data dan cakupan fungsional sebelum menulis kode.
- `planning-and-task-breakdown`: Menyusun daftar pekerjaan berurutan dengan target file yang jelas.
- `constraint-driven-development`: Menjaga ambang batas kelulusan tes agar tidak dilonggarkan saat perubahan kode.

### 3.2 Backend dan rekayasa data
- `api-and-interface-design`: Merancang endpoint JSON, penanganan kesalahan, dan validasi header `X-API-KEY`.
- `source-driven-development`: Memverifikasi penggunaan fungsi PHP dan MariaDB berdasarkan dokumentasi resmi.
- `doubt-driven-development`: Menguji ulang logika berisiko tinggi seperti verifikasi sandi dan hak akses admin.
- `incremental-implementation`: Menerapkan perubahan sedikit demi sedikit agar mudah dilacak bila timbul kesalahan.
- `test-driven-development`: Menyiapkan berkas pengujian di `tests/` sebelum melengkapi implementasi script.
- `deprecation-and-migration`: Mengelola perubahan kolom atau tabel tanpa memutus layanan yang aktif.

### 3.3 Tampilan dan pengalaman pengguna
- `ui-ux-pro-max`: Menata hierarki halaman, navigasi tabel admin, dan form input agar mudah digunakan.
- `frontend-ui-engineering`: Menata interaksi tombol aksi, modal popup, dan tata letak responsif.
- `ui-styling`: Menyesuaikan warna indikator status (`approved`, `pending`, `rejected`) dengan palet BPS.
- `brand` dan `brand-guidelines`: Mempertahankan keselarasan elemen visual dengan panduan resmi BPS.
- `design-system`: Menyamakan pola komponen antarmuka di halaman admin dan area pegawai.

### 3.4 Keamanan dan keandalan
- `security-and-hardening`:
  - Gunakan prepared statements PDO untuk seluruh query yang melibatkan masukan pengguna.
  - Sanitasi semua keluaran teks ke HTML memakai `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
  - Pasang token CSRF pada form berstatus POST.
  - Periksa hak peran (`ADMIN` atau `USER`) lewat `config/auth.php` pada awal file.
  - Pakai fungsi `password_hash($password, PASSWORD_BCRYPT)` untuk menyimpan sandi. Jangan pernah memakai verifikasi alternatif berbasis hash mentah.
- `code-review-and-quality`: Mengevaluasi kebenaran alur, keterbacaan, dan efisiensi script.
- `code-simplification`: Menggabungkan logika serupa antara form penambahan dan pengubahan data.
- `performance-optimization`: Memberi index pada kolom foreign key (`id_user`, `id_kategori`) dan mempercepat query relasi.
- `debugging-and-error-recovery`: Mengurai pesan galat server dari log Apache dan PHP untuk menemukan akar persoalan.
- `observability-and-instrumentation`: Menambahkan pencatatan log pada proses otentikasi dan transaksi data penting.

### 3.5 Pengurangan kompleksitas
- `ponytail`: Memakai fitur standar bahasa PHP sebelum memutuskan menambah paket pihak ketiga.
- `ponytail-audit`: Menemukan berkas usang dan bagian fungsi yang sudah tidak dipanggil.
- `ponytail-review`: Menghilangkan kode perantara yang tidak memberi manfaat fungsional.

### 3.6 Dokumen dan pengolahan konten
- `pdf`: Membaca dan membuat berkas PDF untuk keperluan cetak laporan kedinasan.
- `xlsx`: Membaca dan menyusun data tabel Excel untuk ekspor maupun impor layanan statistik.
- `docx`: Mengolah berkas laporan dinas Microsoft Word.
- `slides`: Menyusun materi presentasi pengenalan portal.
- `humanizer`: Menghapus tanda penulisan mesin dari naskah dokumentasi, changelog, dan deskripsi teknis tanpa mengubah detail fakta.
- `graphify`: Membuat diagram keterhubungan modul dan struktur database.

## 4. Aturan pengembangan

1. Konsistensi basis data:
   - Skema acuan berpatokan pada 10 tabel di `database/bps-solsel (1).sql`.
   - Konfigurasi nama database lokal diatur pada `.env` (`DB_NAME=bps_solsel` atau `bps-solsel`).
2. Kendali akses dan autentikasi:
   - Pertahankan pemeriksaan `require_admin()` pada halaman data master dan persetujuan pengguna.
   - Sesi pengguna biasa dibatasi hanya pada data miliknya (`WHERE id_user = :current_user_id`).
   - Saring data layanan pada endpoint `public/api/layanan.php` agar hanya menampilkan layanan publik dan internal (`WHERE l.akses IN ('Public', 'Internal')`). Jangan kirim data layanan berstatus privat ke portal publik.
3. Kriteria pengujian:
   - Jalankan perintah berikut sebelum menandai pekerjaan selesai:
     ```bash
     npm test
     ```
   - Semua tes integrasi wajib berstatus lulus.
4. Pencatatan commit git:
   - Tulis pesan commit secara ringkas dan informatif menggunakan konvensi Conventional Commits (`feat:`, `fix:`, `refactor:`, `test:`).
