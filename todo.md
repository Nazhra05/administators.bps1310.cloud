# Rencana kerja dan progres pengembangan
## Dashboard Admin: BPS Kabupaten Solok Selatan

Dokumen pelacak tugas (*task tracking*) pengembangan, penyelarasan fitur, dan perbaikan keamanan Dashboard Admin (App A).

## 1. Fondasi sistem dan konfigurasi

- [x] Konfigurasi database lokal MariaDB (`bps_solsel` dan `bps-solsel`)
- [x] Pengaturan web server Apache dan direktori junction `C:\xampp\htdocs\Dashboard_Admin`
- [x] Konfigurasi environment App B Next.js ke endpoint lokal `http://localhost/Dashboard_Admin/public/api`
- [x] Penyusunan panduan kerja agen pada `AGENTS.md`
- [x] Instalasi dan adaptasi skill `blader/humanizer` ke dalam sistem agen
- [x] Penyusunan dokumen arsitektur dan sistem desain pada `APP_CONTEXT.md`

## 2. Penyelarasan otorisasi peran dan tampilan

- [x] Membuka akses `public/kategori.php` untuk peran `USER` (hapus pengalihan paksa non-admin)
- [x] Memulihkan tombol aksi `+ Tambah Kategori` pada `public/kategori.php`
- [x] Menyatukan dashboard `public/index.php` agar peran `USER` tetap melihat ringkasan katalog layanan dan kategori
- [x] Memulihkan bilah saring `<select id="kategori">` dan tabel data layanan pada `public/index.php`
- [x] Memastikan formulir `public/register.php` secara konsisten menetapkan `role = 'USER'` dan status verifikasi email
- [x] Mengarahkan peran `USER` langsung ke `public/index.php` sebagai pengganti `dashboard_user.php`
- [x] Memperbaiki visibilitas menu Kategori pada sidebar di seluruh halaman (`layanan.php`, `pengaturan.php`, `tambah_layanan.php`, `edit_layanan.php`) untuk peran `USER`
- [x] Menyesuaikan `public/layanan.php` agar peran `USER` hanya melihat layanan miliknya sendiri demi kenyamanan tampilan (UX)
- [x] Membersihkan seluruh data layanan uji coba (*Layanan Portal Test*) dari database dan mempertahankan 17 layanan statistik resmi

## 3. Keamanan backend dan API untuk App B

- [x] Menambahkan filter `WHERE l.akses IN ('Public', 'Internal')` pada endpoint `public/api/layanan.php` agar layanan privat user tidak bocor ke App B
- [x] Memperbarui fungsi `validateAkses()` pada `public/api/layanan.php` untuk mendukung nilai `Public`, `Internal`, dan `Private`
- [x] Menghapus celah keamanan *pass-the-hash* pada `public/api/auth/loginPortal.php` (hapus perbandingan fallback `hash_equals`)
- [x] Menambahkan verifikasi status email aktif (`email_verified == 1`) pada `public/api/auth/loginPortal.php`

## 4. Pengujian integrasi otomatis (Quality Gate)

- [x] Memperbaiki kegagalan pengujian Test 8 pada `tests/app.test.js` (filter kategori dashboard)
- [x] Memperbaiki kegagalan pengujian Test 9 pada `tests/app.test.js` (tabel data layanan dashboard)
- [x] Memperbaiki kegagalan pengujian Test 12 pada `tests/app.test.js` (tombol tambah kategori)
- [x] Menjalankan suite pengujian Vitest dan memastikan seluruh 60 skenario uji berstatus lulus (100% pass)

## 5. Refaktorisasi dan perampingan kode (Ponytail Cleanup)

- [x] Mengekstrak komponen navbar seragam ke `public/partials/navbar.php` dengan penyaringan menu berbasis peran dan menerapkannya di seluruh halaman
- [x] Mendeprekasi berkas redundan `public/dashboard_user.php` dan mengalihkan seluruh sesi ke dashboard utama `public/index.php`
- [x] Menyiapkan stylesheet token desain bersama pada `public/css/style.css` untuk merapikan CSS inline

## 6. Penataan arsitektur routing dan struktur folder

- [x] Memindahkan fungsi pengiriman email dari `public/` ke `app/services/mail_service.php` dengan shim kompatibilitas
- [x] Memisahkan logika kueri repositori pegawai dari `config/user_repo.php` ke `app/services/user_repo_service.php`
- [x] Mengamankan skrip non-produksi `public/test_api.php` ke folder `scripts/test_api.php` agar tidak terekspos ke publik
- [x] Menghapus skrip trending topics (`public/trending-topics.php` dan `scripts/generate-topics.php`) serta aturan akses di `.htaccess` karena tidak terpakai oleh App B
- [x] Menghapus model yatim piatu `app/models/Layanan.php` dan berkas cadangan duplikat `database/bps-solsel (1).sql`
- [x] Menyelaraskan dokumentasi dan penamaan internal untuk `kelola_admin.php` dan `kelola_user.php` tanpa merusak kontrak pengujian
- [x] Menjalankan verifikasi penuh suite pengujian Vitest untuk menjamin 60 dari 60 skenario uji tetap 100% lulus

## 7. Pembersihan webroot dan modernisasi pengujian

- [x] Mengalihkan dependensi pemanggilan email di `public/register.php` dan `public/kelola_user.php` langsung ke `app/services/mail_service.php`
- [x] Menghapus 4 berkas shim email (`send_verification_email.php`, `send_admin_verification_email.php`, `send_user_verification_email.php`, `send_admin_notification.php`) dari folder `public/`
- [x] Menghapus berkas redundan `public/dashboard_user.php` dan `public/switch_account.php` dari folder `public/`
- [x] Memusatkan token desain sistem pada `public/css/style.css` sesuai spesifikasi APP_CONTEXT.md
- [x] Menyusun pengujian kontrak API untuk App B dan pengujian otorisasi RBAC yang tahan perubahan markup (`tests/app_b_api_contract.test.js`)
- [x] Menjalankan verifikasi penuh untuk memastikan integrasi App A dan App B tetap 100% stabil (72 dari 72 skenario uji 100% lulus)

## 8. Penyatuan desain sistem dan eliminasi form duplikat

- [x] Memindahkan seluruh blok `<style>` inline yang berulang di setiap berkas PHP ke kelas utilitas terpusat di `public/css/style.css`
- [x] Mengonsolidasikan seluruh alur penambahan dan pengubahan data layanan ke modal interaktif di `public/layanan.php` dan `public/repositori_portal.php`
- [x] Mengonsolidasikan alur penambahan dan pengubahan kategori ke modal interaktif di `public/kategori.php`
- [x] Menata berkas form terpisah (`tambah_layanan.php`, `edit_layanan.php`, `tambah_kategori.php`, `edit_kategori.php`) agar tidak lagi menduplikasi kode modal
- [x] Memastikan konsistensi tata letak bersama (sidebar vertikal kiri, header atas, area konten) di seluruh halaman

## 9. Pengamanan mutasi data dan refaktorisasi aksi

- [x] Mengganti metode penghapusan data via HTTP GET pada `public/hapus_layanan.php` dan `public/hapus_kategori.php` menjadi HTTP POST atau API dengan proteksi token CSRF
- [x] Menyelaraskan struktur dan rute penamaan warisan `kelola_admin.php` dan `kelola_user.php` secara bertahap dengan rute yang lebih representatif
- [x] Menegakkan isolasi data repositori privat pegawai pada tingkat kueri basis data untuk mencegah akses lintas pengguna

## 10. Penerapan matriks pengujian modern (lepas dari markup rapuh)

- [x] Pengujian kontrak API App B: verifikasi skema JSON, status HTTP 200/401/403, dan penyaringan layanan privat dari publik
- [x] Pengujian taksonomi dan relasi: memastikan relasi kategori dan layanan tetap konsisten saat dikonsumsi oleh App B
- [x] Pengujian keamanan dan RBAC: memvalidasi pengalihan rute anonim, blokade peran USER dari menu admin, dan isolasi data pribadi
- [x] Pengujian siklus hidup akun user: menguji alur pendaftaran mandiri, verifikasi token email, persetujuan admin, hingga login
- [x] Pengujian siklus hidup akun admin: menguji undangan email oleh admin aktif, pengaturan kata sandi, dan aktivasi akun
- [x] Pengujian siklus hidup katalog layanan: menguji penambahan data, refleksi pada dashboard dan API App B, pembaruan, hingga penghapusan aman
- [x] Pengujian navigasi UI (smoke test): memvalidasi visibilitas menu navigasi berdasarkan peran aktif tanpa terikat pada markup tag HTML yang kaku

## 11. Modernisasi antarmuka pengguna, ikon Lucide, dan penulisan humanis

- [x] Memasang pustaka ikon frontend Lucide dan menyediakan helper SVG terpusat di `public/partials/icons.php` (`render_icon()`)
- [x] Mengganti seluruh emoji kasar dan tombol teks mentah pada navbar, dropdown akun, katalog layanan, kelola user/admin, repositori portal, dan repositori private dengan ikon Lucide SVG modern
- [x] Menerapkan prinsip penulisan humanis (gaya humanizer) pada seluruh pesan kesalahan, notifikasi, placeholder, dan instruksi antarmuka (bebas kata klise AI, bahasa Indonesia wajar dan profesional)
- [x] Menjaga konsistensi mikro-interaksi tombol, badge, dan modal dialog di seluruh dashboard

## 12. Penyelarasan repositori privat untuk administrator

- [x] Membuka akses navigasi `Kelola Repositori Portal` dan `Kelola Repositori Private` di sidebar bersama (`public/partials/navbar.php`) untuk administrator
- [x] Menegakkan hak kelola koleksi privat mandiri bagi admin (favorit, link web, data, aplikasi mobile, drive, foto, lainnya) dengan isolasi data berbasis ID pengguna
- [x] Menyelaraskan judul dinamis `<title>` pada `repositori_portal.php` dan `repositori_private.php` sesuai peran aktif (`Admin` atau `User`)
- [x] Memperbaiki ekstraksi parameter tujuan `$to` pada `public/switch_role.php`
- [x] Memperbarui dokumentasi peran dan matriks akses pada `APP_CONTEXT.md`
- [x] Menambahkan skenario uji otomatis pada `tests/user_role.test.js` untuk memvalidasi repositori privat admin dan isolasi lintas pengguna (10 file uji, 91 pengujian 100% lulus)

## 13. Penyatuan navbar statis, perbaikan UX sidebar mobile, dan unifikasi kelola repositori portal

- [x] Menyatukan komponen top navbar bersama ke `public/partials/topbar.php` dengan tata letak judul di kiri dan panel akun ("Login sebagai") di kanan di seluruh halaman
- [x] Mengubah posisi navbar menjadi statis (`position: static`) sehingga tidak lagi melayang atau menempel saat halaman digulir
- [x] Memperbaiki UX sidebar mobile dengan menyembunyikan tombol 3 garis saat sidebar terbuka dan menyediakan tombol tutup silang (X) di header sidebar
- [x] Menyatukan menu "Kelola Layanan" dan "Kelola Repositori Portal" menjadi satu menu tunggal "Kelola Repositori Portal" dengan tampilan carousel horizontal
- [x] Menyesuaikan tampilan "Kelola Repositori Portal" agar pengguna biasa hanya melihat layanan yang ia tambahkan, sedangkan administrator melihat seluruh layanan
- [x] Menjalankan verifikasi penuh suite pengujian otomatis Vitest (10 file uji, 91 dari 91 pengujian 100% lulus) dan memastikan App B tetap aktif dengan respons HTTP 200

## 14. Standardisasi navbar seragam, perbaikan overlap tombol 3 garis, dan pembersihan redundansi kode

- [x] Menghilangkan tumpang tindih tombol 3 garis dengan judul halaman (Gambar 1) dengan memposisikan tombol secara inline flex di samping judul dan menyembunyikannya pada tampilan desktop
- [x] Menghilangkan tumpang tindih tombol 3 garis dengan header logo sidebar saat sidebar terbuka (Gambar 2) via penanganan visibilitas saat `body.sidebar-open`
- [x] Menyelaraskan seluruh navbar menjadi satu standar konsisten di semua halaman (`index.php`, `kelola_user.php`, `kelola_admin.php`, `pengaturan.php`, `kategori.php`, `repositori_portal.php`, `repositori_private.php`), membersihkan deklarasi CSS lokal yang memecah header (Gambar 3 dan Gambar 4)
- [x] Mengimplementasikan algoritma penyaringan layanan pada "Kelola Repositori Portal" sehingga pengguna biasa (USER) hanya melihat layanan yang ia tambahkan sendiri, sementara katalog utuh tetap tersedia di Dashboard
- [x] Membersihkan redundansi berkas ~1450 baris pada `public/repositori_portal.php` menjadi implementasi terpadu berbasis `public/layanan.php`
- [x] Memvalidasi kembali seluruh suite pengujian otomatis (10 berkas uji, 91 pengujian 100% lulus hijau)

## 15. Penerapan toggle collapsible sidebar (ikon tetap tampil saat tertutup, ikon dan teks saat terbuka)

- [x] Menyediakan tombol toggle interaktif pada topbar yang selalu aktif dan rapi untuk beralih mode sidebar
- [x] Mengonfigurasi mode tertutup (collapsed) sehingga seluruh ikon menu navigasi tetap tampil dan terpusat rapi, sedangkan teks label disembunyikan
- [x] Mengonfigurasi mode terbuka (expanded) sehingga menampilkan ikon dan teks menu secara lengkap
- [x] Menyimpan status preferensi sidebar pengguna di `localStorage` sehingga posisi sidebar konsisten saat berpindah halaman
- [x] Memvalidasi seluruh suite pengujian otomatis Vitest (10 file uji, 91 dari 91 pengujian 100% lulus)

## 16. Desain modern toggle sidebar, reposisi terpusat saat tertutup, dan eliminasi teks logo BPS

- [x] Menghilangkan teks logo BPS (`.logo-mini`) sepenuhnya saat sidebar dalam kondisi tertutup
- [x] Memposisikan tombol toggle tepat di lokasi bekas teks logo BPS secara terpusat (centered) di atas barisan ikon menu navigasi saat tertutup
- [x] Merancang gaya toggle modern bernuansa translucent glassmorphism dengan sudut halus, transisi mulus, dan ikon yang menyatu dengan tema biru BPS
- [x] Menetapkan posisi toggle saat sidebar terbuka pada sudut kanan atas header sidebar (`.logo`) sejajar dengan judul, mengacu pada standar desain modern (Shadcn UI, Linear, Notion) sehingga topbar utama tetap bersih
- [x] Mengintegrasikan pembaruan dinamis atribut aksesibilitas (`aria-expanded` dan `title`) pada JavaScript sidebar
- [x] Menambahkan skenario pengujian otomatis pada `tests/app.test.js` dan memvalidasi seluruh rangkaian pengujian (10 file pengujian, 92 pengujian 100% lulus hijau)

## 17. Penggantian ikon toggle yang simpel dan umum serta perbaikan bug sidebar repositori private

- [x] Mengganti ikon toggle menjadi chevron yang simpel dan umum (`chevron-left` saat sidebar terbuka dan `chevron-right` saat sidebar tertutup) yang mencerminkan arah lipat/buka toggle secara alami
- [x] Menambahkan efek mikro animasi hover arah panah (geser kiri saat terbuka, geser kanan saat tertutup) untuk memberikan umpan balik interaksi yang intuitif
- [x] Menginvestigasi dan memperbaiki akar masalah kegagalan toggle di `repositori_private.php` akibat fungsi lokal usang `openSidebar`, `closeSidebar`, dan `toggleSidebar` yang menimpa fungsi terpadu `navbar.php`
- [x] Membersihkan deklarasi CSS dan fungsi manipulasi sidebar usang di seluruh berkas halaman lainnya (`index.php`, `kategori.php`, `kelola_admin.php`, `kelola_user.php`, `layanan.php`, `pengaturan.php`, `tambah_layanan.php`, dan `edit_layanan.php`)
- [x] Menambahkan import `icons.php` pada `tambah_layanan.php` dan `edit_layanan.php` guna mencegah galat fatal pemanggilan fungsi ikon
- [x] Menambahkan skenario pengujian baru pada `tests/private_repository_categories.test.js` dan memastikan seluruh suite pengujian berjalan sempurna (10 file pengujian, 93 dari 93 tes 100% lulus hijau)

## 18. Dukungan API repositori pegawai untuk portal Next.js (Integrasi App B)

- [x] Menambahkan dukungan autentikasi ganda pada `public/api/user_repo.php` (mendukung `X-API-KEY` server-to-server dari App B dan sesi PHP browser)
- [x] Menambahkan aksi `get_all` pada `public/api/user_repo.php` untuk mengembalikan seluruh 7 kategori repositori privat dalam satu pemanggilan
- [x] Menghapus dependensi usang `auth.php` dari `app/services/user_repo_service.php` agar dapat dipanggil secara modular tanpa pembatasan sesi
- [x] Menambahkan skenario uji otomatis kontrak API repositori privat pada `tests/app_b_api_contract.test.js` (10 berkas uji, 95 skenario uji 100% lulus)

## 19. Dekopling antarmuka repositori private dan pemusatan ruang kerja pegawai di App B

- [x] Menghapus menu navigasi "Kelola Repositori Private" dari sidebar `public/partials/navbar.php` guna mengeliminasi redundansi visual pada back-office
- [x] Menambahkan banner notifikasi arsitektur terintegrasi pada `public/repositori_private.php` yang mengarahkan pegawai langsung ke portal satu pintu (Next.js)
- [x] Mempertahankan tabel basis data MySQL dan modul REST API (`user_repo_service.php` dan `public/api/user_repo.php`) sebagai mesin headless backend
- [x] Memperbarui pengujian otomatis pada `tests/user_role.test.js` untuk memastikan sidebar admin maupun user bersih dari redundansi menu repositori private (10 berkas uji, 95 tes 100% lulus)

## 20. Rencana peningkatan dan pengerasan sistem (Berdasarkan audit dokumentasi root)

Bagian ini mencatat seluruh item perbaikan, refaktorisasi, dan peningkatan yang diidentifikasi dari evaluasi 6 dokumen arsitektur di root (`API_and_Interface_Design.md`, `Business_Logic.md`, `Code_Quality_and_Architecture.md`, `Project_Context.md`, `Security.md`, dan `UI_UX.md`). Seluruh tugas diurutkan dari hal yang dapat diselesaikan terlebih dahulu secara mandiri tanpa risiko memutus kontrak atau mengganggu alur sistem yang sedang berjalan.

### Prioritas 1: Perbaikan mandiri dan non-breaking (Keamanan berkas, konfigurasi web server, dan refaktorisasi UI)
*Dapat dikerjakan langsung secara lokal di App A tanpa ketergantungan pada App B dan tanpa risiko memutus kontrak API.*

- [x] **Pengerasan keamanan folder upload (`public/uploads/.htaccess`)**:
  - Telah dibuat berkas `public/uploads/.htaccess` dengan konfigurasi penonaktifan handler eksekusi skrip dinamis (`Options -Indexes -ExecCGI`, `FilesMatch` penolakan file PHP/CGI/skrip, dan `php_flag engine off`) guna mencegah eksekusi payload berbahaya via upload logo.
- [x] **Penambahan header HTTP Strict Transport Security (HSTS)**:
  - Telah ditambahkan arahan `Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"` pada `Dashboard_Admin/.htaccess` guna menjamin proteksi enkripsi SSL/HTTPS di server VPS produksi.
- [x] **Fungsi pembantu standar respons JSON di backend (`config/config.php`)**:
  - Telah diterapkan fungsi global `json_response(bool $success, string $message, $data = null, int $statusCode = 200, array $extra = [])` di `config/config.php` untuk standardisasi payload JSON terpadu.
- [x] **Modernisasi notifikasi antarmuka (Penggantian `alert()` browser dengan Toast UI)**:
  - Telah dibuat utilitas Toast UI modern non-blocking pada `public/js/toast.js` dan diintegrasikan pada `public/partials/topbar.php` serta dilengkapi gaya CSS `.toast-container`, `.toast-item` di `public/css/style.css`.
- [x] **Modularisasi modal form layanan (`public/partials/modal_layanan.php`)**:
  - Telah diekstrak modal form tambah dan edit layanan dari `public/layanan.php` ke berkas parsial terpisah `public/partials/modal_layanan.php`, mempertahankan seluruh ID dan struktur form (100% lulus 5/5 uji `admin_layanan_forms.test.js`).
- [x] **Sentralisasi sisa blok `<style>` inline ke lembar gaya terpadu (`public/css/style.css`)**:
  - Telah disatukan kelas-kelas komponen modal, badge akses, carousel preview, dan card ke dalam `public/css/style.css` untuk efisiensi transfer aset dan pemeliharaan gaya terpadu.

### Prioritas 2: Perluasan internal backend dan logika bisnis (Audit trail, alur email, dan spesifikasi kontrak)
*Menambahkan kapabilitas fungsional baru di backend tanpa mengubah kontrak API publik yang dikonsumsi oleh App B.*

- [x] **Penerapan tabel riwayat aktivitas administratif (`audit_logs`)**:
  - Telah dibuat tabel `audit_logs` di database `bps_solsel` via migrasi `scripts/setup_audit_logs.php` dan dihubungkan fungsi pembantu `log_audit()` di `config/config.php`. Telah aktif mencatat aksi persetujuan user, penolakan, perubahan role, penonaktifan/penghapusan admin di `kelola_user.php`, `kelola_admin.php`, dan `hapus_layanan.php`.
- [x] **Notifikasi email otomatis pasca-persetujuan akun pegawai (`kelola_user.php`)**:
  - Telah diimplementasikan fungsi `sendAccountApprovedNotification()` di `app/services/mail_service.php` dan dihubungkan pada aksi `approve` di `public/kelola_user.php` dengan penanganan fallback asinkron/aman.
- [x] **Mekanisme kirim ulang token verifikasi email (*Resend Verification Email*)**:
  - Telah dibuat rute publik dan halaman `public/resend_verification.php` dengan proteksi CSRF, validasi email, regenerasi token 24 jam baru, serta integrasi tautan langsung dari `public/verify_email.php` saat token kedaluwarsa.
- [x] **Penyusunan spesifikasi kontrak formal OpenAPI 3.0 (`openapi.yaml`)**:
  - Telah disusun berkas spesifikasi standar OpenAPI 3.0 lengkap pada `Dashboard_Admin/openapi.yaml` mencakup seluruh endpoint katalog (`api/layanan.php`), taksonomi (`api/kategori.php`), repositori privat (`api/user_repo.php`), autentikasi portal (`loginPortal.php`, `registerPortal.php`), dan validasi sesi (`check_session.php`).

### Prioritas 3: Harmonisasi kontrak terkoordinasi dan skalabilitas lintas-aplikasi (Sinkronisasi dengan App B)
*Tugas bertingkat tinggi yang memerlukan sinkronisasi langsung dengan App B agar transisi berjalan mulus tanpa breaking changes.*

- [x] **Unifikasi skema envelope JSON endpoint katalog dan taksonomi**:
  - Telah distandarisasi fungsi `responseJson()` pada `public/api/layanan.php` dan `public/api/kategori.php` agar selalu menyertakan atribut `success` boolean terpadu sekaligus mempertahankan properti legacy `status` (100% backward compatibility terverifikasi).
- [x] **Implementasi paginasi terstandar pada endpoint katalog (`public/api/layanan.php`)**:
  - Telah ditambahkan dukungan query paginasi fleksibel `?page=X&limit=Y` pada `public/api/layanan.php` lengkap dengan metadata `meta: { page, limit, total, total_pages }` tanpa merusak pemanggilan tanpa parameter dari App B.
- [x] **Penyediaan endpoint validasi status sesi seketika (*Session Revocation Check*)**:
  - Telah dibuat endpoint terproteksi server-to-server `public/api/auth/check_session.php` untuk memvalidasi apakah akun pemegang token Next.js masih berstatus sah di database (`approved`) atau telah dinonaktifkan/dihapus oleh administrator.
- [x] **Verifikasi pengujian menyeluruh (11 berkas uji Vitest, 105 skenario uji 100% lulus)**:
  - Seluruh rangkaian pengujian regresi back-office dan API headless dinyatakan 100% lulus tanpa kegagalan (termasuk suite baru `tests/system_hardening_and_session.test.js`).

## 21. Integrasi Single Sign-On (SSO) dan Sinkronisasi Sesi Portal Next.js (App B)

Memfasilitasi transisi sesi mulus dan bebas hambatan antara portal publik Next.js (App B) dan Dashboard Admin PHP (App A) dengan kontrol hak akses yang terisolasi aman.

- [x] **Penerima dan Validator SSO (`public/sso.php`)**:
  - [x] Implementasi modul penangkap token SSO dengan verifikasi tanda tangan digital kriptografis HMAC-SHA256 berbasis `API_KEY`.
  - [x] Validasi masa berlaku token ketat (kedaluwarsa 120 detik untuk mencegah serangan replay).
  - [x] Inisialisasi sesi PHP sesuai identitas akun portal terverifikasi (`id_admin`, `role`, `status`) dan penghapusan sesi PHP lama agar hak akses admin tidak menempel pada user biasa.
  - [x] Pengalihan aman ke target modul yang diizinkan (`layanan.php`, `kategori.php`, `index.php`, `repositori_private.php`, `pengaturan.php`) dengan proteksi path traversal.
- [x] **Auto-Sync Sesi dari Cookie Portal (`config/auth.php`)**:
  - [x] Implementasi fungsi `verify_portal_session_cookie()` untuk memvalidasi cookie `bps_solsel_session` secara independen di setiap request dashboard.
  - [x] Implementasi fungsi `sync_session_from_portal_user()` yang otomatis mere-sinkronisasi sesi PHP jika akun aktif portal berbeda dengan sesi PHP yang ada, menjamin konsistensi lintas tab browser.
- [x] **Suite Pengujian Integrasi SSO (`tests/sso_and_role_sync.test.js`)**:
  - [x] Pengujian otomatis menyeluruh untuk memvalidasi penolakan tanpa token, token kedaluwarsa, tanda tangan palsu, inisialisasi sesi peran USER vs ADMIN, serta isolasi target pengalihan yang tidak sah.

## 22. Spesialisasi Penuh Dashboard Admin & Penyelarasan API Layanan Pengguna

Menegaskan `Dashboard_Admin` sebagai ruang kerja terisolasi penuh khusus untuk administrator, sementara pengelolaan repositori portal dan pengaturan user biasa didelegasikan sepenuhnya ke aplikasi Next.js (`landingpageintra`).

- [x] **Penyelarasan API Layanan (`public/api/layanan.php`)**:
  - [x] Dukungan proyeksi dan seleksi kolom `created_by` pada query data tunggal dan jamak.
  - [x] Dukungan filter parameter `?created_by={id}` untuk memfasilitasi pengambilan daftar layanan milik pengguna tertentu secara headless.
  - [x] Ekstraksi dan penyimpanan `created_by` pada method `POST` saat layanan baru ditambahkan dari Next.js.
  - [x] Validasi kepemilikan data (*ownership check*) pada method `PUT` dan `DELETE` berbasis parameter `user_id` guna mencegah manipulasi data antar pengguna, dengan izin bypass untuk panggilan administrator.
- [x] **SSO Dua Arah Tetap Utuh**:
  - [x] Akun administrator tetap dapat beralih mulus antara portal Next.js dan `Dashboard_Admin` via SSO tanpa perlu melakukan pendaftaran atau login ulang.
  - [x] Pengguna biasa mengelola layanan portal dan preferensi akun langsung dari antarmuka modern Next.js.

## 23. Endpoint Penggantian Kata Sandi Terautentikasi (`public/api/auth/change_password.php`)

Menyediakan antarmuka REST API aman bagi App B untuk memperbarui kata sandi akun pengguna:

- [x] **Verifikasi Kata Sandi Lama Mandiri**:
  - [x] Memvalidasi kecocokan kata sandi lama menggunakan `password_verify()` dengan fallback transparan ke SHA-256 untuk akun-akun legacy.
- [x] **Pembaruan Kata Sandi Baru dengan Bcrypt**:
  - [x] Mengenkripsi kata sandi baru menggunakan algoritma standar industri `password_hash(..., PASSWORD_BCRYPT)`.
- [x] **Otentikasi Server-to-Server**:
  - [x] Memvalidasi header keamanan `X-API-KEY` untuk mencegah panggilan liar di luar server Next.js.
- [x] **Pencatatan Audit Trail**:
  - [x] Mencatat aktivitas penggantian kata sandi ke dalam tabel `log_aktivitas` via `log_audit()` untuk keperluan akuntabilitas.

