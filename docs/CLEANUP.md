# Laporan Pembersihan Kode dan Perbaikan Fungsi (Fase 3)

**Tanggal:** 2026-10-09  
**Branch:** `fix/fase-3-perbaikan-fungsi`  
**Status:** Selesai (100%)  
**Dokumen Referensi:** [`todo.md`](file:///c:/xampp/htdocs/adminDashboard/todo.md) (Fase 3), [`docs/FUNGSI_MATRIX.md`](file:///c:/xampp/htdocs/adminDashboard/docs/FUNGSI_MATRIX.md), [`docs/API_CONTRACT.md`](file:///c:/xampp/htdocs/adminDashboard/docs/API_CONTRACT.md).

---

## 1. Ringkasan Perbaikan Bug Fungsional (Task 3.0)

Semua perbaikan bug dieksekusi dengan siklus **TDD (Test-Driven Development)** melalui pengujian otomatis Vitest di [`tests/functional_flow.test.js`](file:///c:/xampp/htdocs/adminDashboard/tests/functional_flow.test.js).

| ID Bug | Tingkat | Komponen | Perubahan / Remediasi | Status Akhir |
|---|---|---|---|---|
| **BUG-001** | Mayor | `public/hapus_kategori.php`<br>`public/kategori.php` | Menolak permintaan HTTP GET (405 Method Not Allowed), memvalidasi token CSRF (`csrf_verify()`), dan mengubah tombol hapus UI menjadi formulir POST dengan token keamanan. | **Berfungsi (Lolos Uji)** |
| **BUG-002** | Mayor | `public/hapus_layanan.php`<br>`public/layanan.php` | Menolak HTTP GET (405), memvalidasi CSRF (403 bila token salah/kosong), dan mengubah pemicu hapus pada kartu layanan menjadi form POST dengan token dan konfirmasi dialog. | **Berfungsi (Lolos Uji)** |
| **BUG-003** | Mayor | `public/verify_user.php`<br>`public/setup_user.php` | Mengeliminasi kerentanan IDOR (Insecure Direct Object Reference). Menghapus pengiriman integer ID mentah (`?id=X`), dan mewajibkan verifikasi token rahasia yang sah (`?token=...`) yang belum kedaluwarsa. Akses tanpa token atau token palsu ditolak. | **Berfungsi (Lolos Uji)** |
| **BUG-004** | Mayor | `public/setup_user.php` | Mengganti fungsi hash usang `hash('sha256', $password)` dengan algoritma standar industri `password_hash($password, PASSWORD_BCRYPT)`. Pengguna baru terbukti dapat login ke App B via API. | **Berfungsi (Lolos Uji)** |
| **BUG-006** | Minor | `public/pengaturan.php` | Menambahkan form dan handler POST ubah password yang memverifikasi password lama (`password_verify` + auto fallback SHA-256), validasi minimal 8 karakter, konfirmasi kecocokan, dan hashing Bcrypt baru. | **Berfungsi (Lolos Uji)** |

---

## 2. Berkas yang Dihapus (Task 3.4 - Keputusan GATE 1)

Sesuai prinsip **YAGNI** (*You Aren't Gonna Need It*) dan **Ponytail** (*Lazy Senior Developer Mindset*), fitur terisolasi yang tidak bernilai operasional langsung dihapus untuk mengurangi beban pemeliharaan dan risiko keamanan:

| Berkas | Kategori | Alasan Penghapusan & Bukti |
|---|---|---|
| `public/trending-topics.php` | Fitur AI Gemini | Endpoint terisolasi yang memuat cache JSON Google Gemini. Tidak terhubung ke dashboard admin maupun antarmuka pengguna App B. |
| `scripts/generate-topics.php` | CLI Cron Script | Skrip background pemanggil API Google Gemini untuk crawling topik. Tidak terpakai dan menambah dependensi API key eksternal. |
| `scripts/.env.example` | File Konfigurasi | Template env terpisah untuk cron trending topics; tidak lagi relevan. |
| Referensi `GEMINI_API_KEY` & `FEATURE_TRENDING` | Konfigurasi | Dihapus dari `.env.example` dan dokumentasi [`README.md`](file:///c:/xampp/htdocs/adminDashboard/README.md). |

---

## 3. Berkas yang Dipindahkan / Direlokasi (Task 3.1 & Task 3.6)

Pemisahan tegas antara berkas publik web, alat internal pengembang, dan unggahan pengguna:

| Berkas Asal | Lokasi Baru | Alasan & Dampak |
|---|---|---|
| `public/test_api.php` | `tools/test_api.php` | Alat uji coba API internal dipindahkan ke luar DocumentRoot publik (`public/`) dan dilindungi oleh `tools/.htaccess` (`Require all denied`) agar tidak dapat diakses pihak luar via web. |
| `public/uploads/logo_bps.jpg` | `public/assets/logo_bps.jpg` | Folder `public/uploads/` dikhususkan secara ketat hanya untuk file yang diunggah pengguna. Aset identitas visual resmi instansi dipindahkan ke `public/assets/` dan tag `<img>` pada halaman `login.php`, `register.php`, dan `verify_email.php` diperbarui. |

---

## 4. Berkas yang Dipertahankan (Task 3.3 & Task 3.5)

| Komponen | Status | Alasan |
|---|---|---|
| `public/api/website.php` | **Dipertahankan** | Diperlukan untuk kompatibilitas ke belakang (*backward compatibility*) dengan App B (`sipasti.bps1310.cloud`) yang mengonsumsi layanan terkelompok per kategori. Terdokumentasi di [`docs/API_CONTRACT.md`](file:///c:/xampp/htdocs/adminDashboard/docs/API_CONTRACT.md) Bagian 3. |
| `Vitest` & `package.json` | **Dipertahankan** | Berfungsi sebagai harness pengujian integrasi *black-box* HTTP otomatis ke server lokal Apache XAMPP, mendampingi PHPUnit untuk pengujian logika internal PHP. |
| `public/pengaturan.php` | **Dipertahankan & Ditingkatkan** | Berguna bagi administrator untuk mengubah password akun mereka secara mandiri, dengan sanitasi informasi teknis lingkungan server. |

---

## 5. Pembersihan Higienis & Konfigurasi Apache (Task 3.7 & 3.9)

1. **Audit Kode Mati:**
   - Pencarian terhadap fungsi debug `var_dump()`, `print_r()`, dan `dd()` menghasilkan **0 temuan** pada kode produksi.
   - Analisis statis **PHPStan (Level 3)** pada direktori `config` dan `tests` lulus bersih dengan pesan **[OK] No errors**.
2. **Pembersihan `.htaccess`:**
   - Menghapus pengecualian file `trending-topics.json`.
   - Menambahkan folder `tools` ke daftar blokir akses langsung folder internal: `RewriteRule ^(...|docs|tools|\.agents|\.git)(/|$) - [F,L]`.
   - Menghapus header usang `X-XSS-Protection` sesuai standar browser modern.
   - Membuat `tools/.htaccess` dengan kebijakan ketat `Require all denied`.
3. **Catatan Target Refaktor Layout (Task 3.8 / Fase 4):**
   - Struktur sidebar, navbar profil, tombol toggle responsif, dan styling duplikat pada 6 berkas berikut ditandai untuk disatukan ke template master (`src/views/layouts/`):
     1. `public/index.php`
     2. `public/kategori.php`
     3. `public/layanan.php`
     4. `public/kelola_admin.php`
     5. `public/kelola_user.php`
     6. `public/pengaturan.php`
