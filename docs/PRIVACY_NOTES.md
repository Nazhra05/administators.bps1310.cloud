# Catatan Privasi & Kebijakan Data (Privacy Notes)

Dokumen ini menjelaskan inventarisasi data pribadi yang dikumpulkan, diproses, dan disimpan oleh sistem Dashboard Administrator (App A) dan Portal Layanan Internal (App B) BPS Kabupaten Solok Selatan, sesuai kepatuhan prinsip tata kelola data pemerintahan dan Task 7.10 / 9.4.

---

## 1. Inventarisasi Data Pribadi yang Disimpan

| Kategori Data | Tabel Penyimpanan | Kolom / Elemen Data | Tujuan Penggunaan | Masa Retensi | Dasar Otoritas / Akses |
|---|---|---|---|---|---|
| **Data Akun Pegawai** | `admin` | `username`, `email`, `nama`, `unit`, `role`, `password` (Bcrypt hash) | Autentikasi pengguna, manajemen hak akses, dan personalisasi nama di dashboard/portal | Selama akun aktif sebagai pegawai / staf BPS | Akses mandiri pengguna & Admin IT (read/manage) |
| **Riwayat Pencarian** | `user_preferences` | `search_history` (JSON, maks 8 teks ≤ 100 char) | Mempermudah pencarian berulang oleh pegawai di portal App B | Tersimpan selama pengguna tidak mengganti/menghapus | Khusus pengguna bersangkutan (terisolasi per `user_id`) |
| **Layanan Tersemat & Tema** | `user_preferences` | `pinned_layanan` (JSON, maks 12 ID), `theme` ('light'/'dark'/'system') | Menampilkan pintasan layanan favorit pegawai di halaman utama portal | Diperbarui sesuai aksi pengguna | Khusus pengguna bersangkutan (terisolasi per `user_id`) |
| **Bookmark Pribadi** | `bookmarks` | `title`, `url`, `notes` (maks 100 entri) | Menyimpan tautan kerja personal pegawai untuk akses cepat | Tersimpan sampai dihapus oleh pengguna | **Ketat:** Hanya pengguna pemilik (`user_id`). Admin IT tidak dapat membaca bookmark pegawai lain |
| **Riwayat Klik Layanan** | `link_clicks` | `user_id`, `id_layanan`, `clicked_at` | Menghitung statistik pemanfaatan layanan pribadi, streak aktivitas, dan lencana apresiasi pegawai | **12 Bulan** (dibersihkan secara otomatis via `scripts/cleanup.php`) | Agregasi internal pengguna sendiri; **tanpa leaderboard publik antar pegawai** |
| **Sesi Login** | `sessions` | `session_id`, `user_id`, `data`, `last_activity`, `expires_at` | Menjaga sesi login API dan web yang aman | Maksimal 8 jam atau 30 menit inaktivitas | Otomatis kedaluwarsa & dibersihkan |
| **Riwayat Audit** | `audit_log` | `user_id`, `aksi`, `target_tipe`, `target_id`, `detail`, `ip_address`, `created_at` | Akuntabilitas dan jejak rekam perubahan data administratif | Permanen / log kepatuhan instansi | Hanya dapat diakses oleh Admin IT |

---

## 2. Prinsip Perlindungan Data & Isolasi Pengguna

1. **Prinsip Minimisasi & Isolasi Mutlak (Privacy by Design):**
   - Seluruh query data personal (`preferences`, `bookmarks`, `clicks`, `stats`) wajib menyertakan filter `WHERE user_id = ?` yang didapatkan dari token sesi resmi (`X-Session`).
   - Tidak ada endpoint API (termasuk modul admin) yang membuka daftar bookmark milik pegawai lain.
2. **Tanpa Leaderboard Antar Pegawai:**
   - Statistik personal (`GET /api/v1/me/stats`) dirancang semata-mata sebagai sarana motivasi diri (personal gamification: *streak*, jumlah layanan unik, dan lencana pencapaian).
   - Sistem **tidak mempublikasikan** peringkat atau perbandingan aktivitas antar pegawai demi menjaga kenyamanan kerja.
3. **Pembersihan Otomatis (Data Lifecycle):**
   - Skrip pembersihan berkala (`scripts/cleanup.php`) berjalan secara rutin untuk menghapus:
     - Riwayat klik yang berumur lebih dari 12 bulan (365 hari).
     - Sesi yang telah kedaluwarsa.
     - Riwayat percobaan login gagal (`login_attempts`) yang berusia lebih dari 24 jam.

---

## 3. Usulan Teks Modal Kebijakan Privasi di Portal App B

Teks berikut direkomendasikan untuk ditampilkan pada tautan *footer* atau dialog modal "Kebijakan Privasi" di aplikasi Portal App B (`sipasti.bps1310.cloud`):

> ### Kebijakan Privasi Portal Internal BPS Kabupaten Solok Selatan
>
> **1. Data yang Kami Kelola:**
> Portal Internal BPS Kabupaten Solok Selatan mengelola data akun pegawai (nama, email BPS, unit kerja), riwayat pencarian layanan, daftar layanan tersemat (*pinned*), bookmark personal, dan frekuensi pembukaan tautan layanan.
>
> **2. Penggunaan Data:**
> Data tersebut digunakan secara eksklusif untuk memberikan kemudahan navigasi kerja harian Anda, menyimpan preferensi tampilan (*dark/light mode*), dan memberikan apresiasi atas partisipasi pemanfaatan aplikasi internal melalui lencana pencapaian personal.
>
> **3. Perlindungan & Kerahasiaan:**
> Bookmark dan catatan pribadi Anda bersifat privat dan tidak dapat dibaca oleh pengguna lain maupun pengelola teknis portal. Aktivitas klik layanan Anda hanya diagregasikan untuk profil pribadi Anda sendiri tanpa perbandingan publik (*leaderboard*).
>
> **4. Retensi & Penghapusan:**
> Riwayat klik layanan disimpan maksimal selama 12 bulan dan akan dihapus secara berkala oleh sistem. Anda dapat memperbarui preferensi dan menghapus bookmark Anda sewaktu-waktu melalui menu profil.
