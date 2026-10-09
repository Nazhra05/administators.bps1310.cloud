# ADR 0002: Keputusan Arsitektur GATE 1 — Modernisasi Bertahap, Penyederhanaan Role, dan Penghapusan Fitur Gemini

- **Status:** Diterima (Accepted)
- **Tanggal:** 2026-10-09
- **Pembuat Keputusan:** Developer & AI Agent Pair (Antigravity 2.0)
- **Konteks:** Evaluasi hasil audit Fase 1 (docs/AUDIT.md dan docs/FUNGSI_MATRIX.md) pada checkpoint GATE 1.

---

## Konteks dan Masalah

Setelah audit keamanan dan click-path pada codebase warisan (legacy), ditemukan sejumlah kerentanan (CSRF pada GET, IDOR, hashing SHA-256 tanpa garam), fitur mati (Gemini AI trending-topics), dan ketidakjelasan alur registrasi serta matriks otorisasi role. Proyek membutuhkan keputusan strategis sebelum memasuki Fase 2 (Kontrak API dan Tes Karakterisasi).

---

## Keputusan yang Diambil

1. **Strategi Arsitektur (Opsi A — Modernisasi Bertahap in-place):**
   - Tidak melakukan penulisan ulang (rewrite) total menggunakan framework baru (mis. Laravel/Slim).
   - Mempertahankan fondasi native PHP dengan peningkatan keamanan bertahap, modularisasi PSR-4, serta menjaga kestabilan kontrak API yang dikonsumsi oleh App B (`sipasti.bps1310.cloud`) dan 20 tes Vitest baseline yang sudah hijau.

2. **Penghapusan Fitur Gemini / Trending Topics (Prinsip YAGNI & Ponytail):**
   - Fitur `trending-topics.php` dan `scripts/generate-topics.php` yang memanggil Google Gemini API dihapus dari lingkup aplikasi. Fitur ini tidak terhubung dengan portal App B dan menambah kompleksitas/ketergantungan eksternal tanpa nilai operasional langsung.

3. **Penyederhanaan Role dan Pertukaran Metode Pendaftaran:**
   - Role disederhanakan menjadi dua tingkatan: **`Admin`** dan **`User`**.
   - **Registrasi Publik (`register.php`):** Hanya diperuntukkan bagi pengguna umum/pegawai dengan role **`User`**.
   - **Pendaftaran Role `Admin`:** Dilakukan secara tertutup melalui sistem input email/undangan aktivasi yang diinisiasi oleh akun pengembang (Super Admin / Admin pertama saat deploy).
   - **Hak Akses Manajemen:** Menu Kelola Admin dan Kelola User hanya dapat diakses oleh role **`Admin`**.

4. **Retensi Endpoint dan Format Password:**
   - `public/api/website.php` dipertahankan untuk menjamin kompatibilitas App B.
   - Format password lama (SHA-256 mentah) didukung secara transparan dan otomatis di-upgrade ke Bcrypt (`$2y$`) saat admin berhasil login.

---

## Konsekuensi

- **Positif:**
  - Risiko regresi pada App B terminimalisasi karena struktur endpoint tetap terjaga.
  - Permukaan serangan (attack surface) berkurang signifikan dengan menghapus fitur mati dan membatasi pembuatan akun admin.
  - Peran dan tanggung jawab dalam sistem menjadi jelas dan mudah diaudit.
- **Negatif / Perlu Penanganan:**
  - Perlu migrasi skema tabel admin/user untuk mendukung role `Admin` dan `User` serta alur aktivasi token undangan.
  - Halaman `kelola_admin.php` dan `kelola_user.php` perlu disesuaikan dengan aturan otorisasi baru pada Fase 5.
