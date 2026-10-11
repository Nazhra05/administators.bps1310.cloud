# Konteks sistem dan panduan desain
## Dashboard Admin: BPS Kabupaten Solok Selatan

Dokumen ini memuat batasan arsitektur, peran pengguna, alur penambahan admin, dan spesifikasi desain sistem untuk Dashboard Admin (App A).

## 1. Tujuan dan peran aplikasi

App A adalah aplikasi panel belakang berbasis PHP Native dan MariaDB. Fungsinya menyediakan antarmuka bagi pengguna internal untuk mengelola katalog data yang ditampilkan pada App B (Portal Layanan Publik dan Internal berbasis Next.js).

Data yang dikelola meliputi:
- Katalog layanan statistik (publik, internal instansi, dan privat pegawai)
- Taksonomi kategori layanan statistik
- Tautan situs web kedinasan dan aplikasi pendukung
- Metadata repositori pegawai

Data tersebut disajikan kepada App B melalui REST API pada direktori `public/api/` dengan proteksi header `X-API-KEY`.

## 2. Peran dan batasan akses

Sistem hanya mengenal dua peran pengguna:

| Fitur | Hak akses Admin | Hak akses User | Keterangan |
| :--- | :--- | :--- | :--- |
| Dashboard | Ya | Ya | Menampilkan ringkasan katalog layanan dan kategori |
| Kelola layanan | Ya | Ya | Admin melihat seluruh layanan; User melihat layanan yang ia tambahkan sendiri demi kerapian antarmuka |
| Kelola repositori portal | Ya | Ya | Menjelajahi katalog layanan publik portal dalam 7 kategori dan menandai layanan favorit |
| Kelola repositori private | Ya | Ya | Koleksi pribadi (7 kategori: Favorit, Link Web, Data, Aplikasi Mobile, Drive, Foto, Lainnya) dengan isolasi data ketat |
| Kelola kategori | Ya | Ya | Menambah, mengubah, dan melihat kategori statistik |
| Pengaturan profil | Ya | Ya | Mengubah profil pribadi dan kata sandi |
| Kelola user | Ya | Tidak | Mengelola akun pegawai dan penetapan hak |
| Kelola admin | Ya | Tidak | Menyetujui, mengonfirmasi, dan mengatur akun admin |

Aturan penegakan akses dan tampilan:
- Admin pada dasarnya juga merupakan user biasa (pegawai) yang memiliki otoritas administratif tambahan. Karena itu, Admin juga memiliki Repositori Private miliknya sendiri untuk menyimpan tautan kerja, dokumen, drive, dan layanan favorit pribadi.
- Menu `Kelola repositori portal` dan `Kelola repositori private` tampil konsisten pada sidebar untuk kedua peran (`ADMIN` maupun `USER`).
- Menu `Kategori` wajib konsisten tampil pada sidebar di setiap halaman untuk kedua peran.
- Menu `Kelola user` dan `Kelola admin` disembunyikan dari navbar dan sidebar saat pengguna berstatus `USER`.
- Backend memeriksa sesi dan peran pengguna pada baris awal file `kelola_user.php` dan `kelola_admin.php`. Jika pengguna bukan `ADMIN`, sistem langsung mengalihkan halaman ke `index.php`.
- Pengguna dengan peran `USER` tetap memiliki hak penuh membuka `kategori.php`, `layanan.php`, `repositori_portal.php`, dan `repositori_private.php`.
- Seluruh layanan yang ditambahkan oleh pengguna biasa tetap tersimpan di tabel database bersama dan disalurkan ke App B melalui REST API jika berstatus akses `Public` atau `Internal`.

## 3. Alur pendaftaran dan penambahan akun

Sistem membedakan mekanisme penambahan akun berdasarkan perannya:

### 3.1 Pendaftaran pengguna biasa (Role USER)
- Masyarakat atau pegawai dapat mendaftar mandiri melalui halaman registrasi publik (`public/register.php`).
- Formulir pendaftaran mengisi nama, username, email, dan kata sandi.
- Akun yang baru didaftarkan secara otomatis berstatus `pending` dengan peran `role = 'USER'`.
- Sistem mengirimkan email verifikasi token via SMTP PHPMailer.
- Setelah email terverifikasi dan dikonfirmasi/disetujui, akun aktif dapat login ke sistem dengan hak akses `USER`.

### 3.2 Penambahan administrator (Role ADMIN)
Pendaftaran akun administrator tidak dibuka untuk publik. Admin baru hanya dapat ditambahkan oleh admin yang sedang aktif melalui tiga tahap:
1. **Input oleh admin aktif**:
   - Admin aktif membuka halaman kelola admin atau kelola user, lalu menginput alamat email calon admin baru.
   - Sistem mencatat calon admin dengan status `pending`, `role = 'ADMIN'`, dan membuat token verifikasi acak dengan masa berlaku 24 jam.
2. **Verifikasi email**:
   - Sistem mengirim tautan verifikasi ke email calon admin via PHPMailer.
   - Calon admin membuka tautan, mengisi profil nama dan username, serta membuat kata sandi baru.
   - Kolom `email_verified` berubah menjadi 1.
3. **Konfirmasi akhir**:
   - Admin aktif memeriksa daftar admin pending pada halaman kelola admin.
   - Admin aktif menyetujui akun tersebut sehingga statusnya menjadi `approved` dan dapat masuk sebagai `ADMIN`.

## 4. Arsitektur tampilan dan komponen bersama

Aplikasi menggunakan satu basis tampilan tunggal. Seluruh halaman memakai tata letak, berkas CSS, navbar, dan komponen antarmuka yang sama. Tidak ada duplikasi halaman dashboard khusus untuk role tertentu.

### 4.1 Desain token

Tiga lapisan token warna mengikuti identitas resmi Badan Pusat Statistik:

**Primitive tokens**:
```css
:root {
    --color-bps-navy: #003366;
    --color-bps-blue: #0055A5;
    --color-bps-orange: #F7941E;
    --color-bps-green: #00A651;
    --color-gray-50: #F8FAFC;
    --color-gray-100: #F1F5F9;
    --color-gray-200: #E2E8F0;
    --color-gray-600: #475569;
    --color-gray-800: #1E293B;
    --color-white: #FFFFFF;
}
```

**Semantic tokens**:
```css
:root {
    --bg-page: var(--color-gray-50);
    --bg-surface: var(--color-white);
    --border-subtle: var(--color-gray-200);
    --text-main: var(--color-gray-800);
    --text-muted: var(--color-gray-600);
    --brand-primary: var(--color-bps-navy);
    --brand-accent: var(--color-bps-orange);
    --status-active: var(--color-bps-green);
    --status-pending: var(--color-bps-orange);
    --status-danger: #DC2626;
}
```

**Component tokens**:
```css
:root {
    --header-height: 64px;
    --sidebar-width: 250px;
    --card-radius: 8px;
    --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
```

### 4.2 Komponen antarmuka bersama

1. **Header dan navbar tunggal**:
   - Memuat logo resmi BPS, judul portal, profil pengguna aktif, penanda peran aktif (`ADMIN` atau `USER`), tombol alih peran untuk admin utama, dan tombol keluar.
2. **Navigasi menu tunggal**:
   - Memuat tautan: Dashboard, Kelola Layanan, Kategori, Pengaturan.
   - Menampilkan tambahan tautan `Kelola User` dan `Kelola Admin` hanya jika `$_SESSION['active_role'] === 'ADMIN'`.
3. **Kartu ringkasan**:
   - Menampilkan angka total layanan, total kategori statistik, dan status integrasi API ke App B.
4. **Tabel data**:
   - Menggunakan format tabel berseri, bilah pencarian instan, filter kategori, badge status, dan tombol aksi (edit/hapus).
5. **Modal konfirmasi**:
   - Dialog seragam untuk penambahan data baru, peringatan penghapusan, dan persetujuan akun admin.

## 5. Pemeriksaan kesesuaian kode saat ini

Hasil evaluasi terhadap codebase perbandingan dengan tujuan sistem:

### 5.1 Bagian yang sudah sesuai
- Tabel database menyimpan akun dengan dua nilai peran: `ADMIN` dan `USER`.
- Session auth menyediakan fungsi penegakan hak akses (`is_admin_user()`, `auth_active_role()`).
- Fitur pengelolaan layanan dan kategori berfungsi mengalirkan data ke App B melalui endpoint API JSON.
- Halaman `kelola_admin.php` dan `kelola_user.php` sudah memblokir pengguna biasa yang mencoba mengakses langsung lewat URL.

### 5.2 Bagian yang belum sesuai
1. **Akses kategori untuk User terblokir**:
   - Pada `public/kategori.php` baris 18, terdapat kode yang mengalihkan pengguna non-admin ke `index.php`. Seharusnya peran `USER` dapat mengakses halaman ini.
2. **Data dashboard untuk User dikosongkan**:
   - Pada `public/index.php` baris 26 sampai 34, sistem mengosongkan variabel `$layanan_terbaru` dan `$data_kategori` jika peran aktif adalah `USER`. Seharusnya peran `USER` tetap melihat ringkasan layanan dan kategori di dashboard utama.
3. **Pendaftaran publik untuk role USER**:
   - Berkas `public/register.php` berfungsi khusus untuk pendaftaran akun dengan `role = 'USER'` yang memerlukan verifikasi email dan konfirmasi admin sebelum aktif.
4. **Pemisahan file dan komponen berlebih**:
   - Berkas `public/dashboard_user.php` dibuat terpisah dari `index.php`, yang memecah basis tampilan bersama.
   - Blok CSS ribuan baris disalin ulang ke dalam setiap file PHP (`index.php`, `layanan.php`, `kategori.php`, `kelola_admin.php`, `kelola_user.php`).

## 6. Rencana perbaikan teknis

Langkah penyelarasan terarah:
1. Menghapus blokade peran pada `public/kategori.php` agar pengguna dengan peran `USER` dapat mengakses kategori.
2. Menghapus pengosongan katalog pada `public/index.php` agar kedua peran melihat dashboard dan katalog layanan yang sama.
3. Memastikan registrasi publik `public/register.php` secara konsisten menetapkan `role = 'USER'`.
4. Menyederhanakan navbar ke dalam satu komponen bersama (`public/partials/navbar.php`) yang menyaring menu `Kelola User` dan `Kelola Admin` secara kondisional berdasarkan peran aktif.
5. Menghapus berkas redundan `public/dashboard_user.php` dan mengarahkan seluruh pengguna ke `public/index.php`.
