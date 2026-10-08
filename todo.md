# TODO.md — Perbaikan dan Peningkatan App A (Dashboard Admin & Backend API): Fokus Fungsi, Development Lokal XAMPP

> **Dokumen kerja untuk agent lokal.** Baca bagian 1–4A sampai selesai sebelum mengubah apa pun.
> Kerjakan task **berurutan**. Satu task = satu commit kecil. Centang `[x]` hanya jika syarat **"Selesai jika"** terpenuhi.
> Titik **🛑 GATE** berarti: berhenti, tulis ringkasan, dan tunggu keputusan manusia.

---

## 1. Konteks

- **App A** = aplikasi PHP + MariaDB/MySQL: dashboard admin (halaman PHP) dan REST API untuk katalog layanan dan kategori BPS Kabupaten Solok Selatan. Folder kerja lokal: `C:\xampp\htdocs\adminDashboard`.
- **Fokus sekarang:** membuat App A **beroperasi sesuai fungsinya**: setiap fitur dashboard dan endpoint API berjalan benar, aman, dan teruji, di lingkungan **lokal XAMPP** (Apache + MariaDB + PHP 8.x).
- **Server, deploy, Docker, Nginx, VPS, CI/CD: DITUNDA.** Akan direkonsiliasi belakangan. Fase 10 dan Lampiran H–I dipertahankan sebagai **draf** dan **tidak boleh dikerjakan sekarang**. Yang dilakukan sekarang hanya mencatat asumsi khusus server di `docs/SERVER_NOTES.md` (0.9) agar rekonsiliasi nanti mudah.
- **App B** (Next.js, repo terpisah) App B repo link: https://github.com/Nazhra05/sipasti.bps1310.cloud. App B memanggil App A **dari sisi server** dengan header `X-API-KEY`. **Kontrak API ke App B tidak boleh putus** kecuali ada alias kompatibilitas.
- XAMPP memakai **Apache**, jadi `.htaccess` **berlaku** di lokal (berbeda dengan Nginx nanti). Setiap aturan khusus Apache dicatat di `docs/SERVER_NOTES.md`.
- Aplikasi ini sudah pernah live, jadi data lama mungkin ada. Di lokal dipakai **data seed palsu**. Latihan dengan salinan data lama masuk Fase 10 (ditunda).
- Developer memakai **Windows + PowerShell**. Jangan memakai `Makefile` atau skrip bash untuk alur kerja harian.
- Harness agent: **Google Antigravity 2.0** dengan konfigurasi **ECC minimal** (`AGENTS.md`, `.agents/rules`, `.agents/workflows`, `.agents/skills`). Cara memakainya ada di bagian 4A.
- Developer adalah pemilik penuh dan pengambil keputusan atas kode ini. Refactor besar boleh dilakukan bila terbukti lebih baik, tetapi tetap lewat commit kecil dan pesan jelas agar mudah dipelajari, di-review, dan dibatalkan.

## 2. Aturan Kerja Agent (wajib)

1. **Branch:** satu branch per fase (`fix/fase-N-nama`). Jangan commit langsung ke `main`.
2. **Commit kecil:** satu task satu commit, pesan format `tipe(area): ringkasan` (contoh `fix(auth): upgrade hash password saat login`). Isi pesan menjelaskan **mengapa**.
3. **Test dulu, ubah kemudian:** sebelum mengubah perilaku yang sudah ada, pastikan ada tes yang mengunci perilaku lama (Fase 2). Setelah itu ubah, dan tes harus tetap lulus atau diubah dengan alasan tertulis.
4. **Tidak ada kerusakan data:** hanya sentuh database **lokal** (`bps_solsel_dev`, `bps_solsel_test`). Jangan jalankan `DROP`/`TRUNCATE` di luar skrip reset dev yang eksplisit.
5. **Rahasia:** jangan pernah mencetak, mencatat, atau meng-commit nilai `.env`, password, API key. Gunakan placeholder di dokumen dan contoh.
6. **Sederhana:** tidak memakai framework penuh (Laravel/Symfony), ORM, atau DI container kecuali diputuskan manusia di GATE Fase 1. Pakai PHP biasa + library kecil: `nikic/fast-route`, `vlucas/phpdotenv`, `phpmailer/phpmailer`.
7. **Komentar untuk pembelajar:** developer masih belajar. Pada kode keamanan (sesi, CSRF, hash), tambahkan komentar singkat tentang *kenapa*, bukan hanya *apa*.
8. **Setelah tiap task:** jalankan `/verification-loop` (memakai `composer check` setelah tersedia di Fase 8; sebelum itu tes manual yang disebut di task), centang task, tambahkan catatan di **Log Agent** (bagian 13).
9. **Jika dokumen ini bertentangan dengan kode nyata atau ada hal ambigu:** berhenti dan tulis pertanyaan di bagian 12. Jangan menebak.
10. **Jangan** melakukan push ke remote atau mengakses server produksi. Agent hanya commit lokal. Langkah di luar folder proyek (mengubah `httpd.conf`/`httpd-vhosts.conf` XAMPP, berkas `hosts` Windows, rotasi secret di layanan lain) ditandai **[MANUAL]**: agent menuliskan langkah dan berkas contoh, lalu berhenti dan menunggu konfirmasi.
11. **Pakai workflow dan skill ECC** sesuai bagian 4A. Untuk tiap fase mulai dengan `/plan`, dan akhiri tiap task dengan `/verification-loop`.
12. **Server dan deploy ditunda:** jangan membuat Docker, Nginx, skrip deploy, atau CI/CD sekarang. Catat asumsi khusus server di `docs/SERVER_NOTES.md`.
13. **Keputusan penting dicatat sebagai ADR** di `docs/adr/` (skill `architecture-decision-records`).

## 3. Definition of Done (global)

App A dianggap **berfungsi dan siap** (untuk tahap lokal) jika:

- [ ] Semua task **Wajib** (bertanda **[W]**) di Fase 0–9 selesai (task **[MANUAL]** dikonfirmasi developer).
- [ ] **Setiap fitur** (login, logout, dashboard, kategori, layanan termasuk unggah logo, kelola pengguna, persetujuan layanan, API) lolos click-path audit dan QA manual: status semua baris di `docs/FUNGSI_MATRIX.md` = *berfungsi*.
- [ ] Kontrak API ke App B terjaga (tes kontrak lulus, atau perubahan terdokumentasi dengan alias lama).
- [ ] `composer check` dan `npx vitest run` lulus bersih.
- [ ] Tidak ada rahasia atau data produksi di repo (`git ls-files` bersih).
- [ ] `docs/SERVER_NOTES.md` terisi, sehingga rekonsiliasi server nanti tidak perlu menebak.
- [ ] Daftar **Go/No-Go** di bagian 11 tercentang semua.

## 4. Peta Fase dan Ketergantungan

| Fase | Isi | Bergantung pada |
|---|---|---|
| 0 | Fondasi lokal XAMPP (Git, Composer, vhost, MariaDB, `.env`, catatan server) | — |
| 1 | Audit read-only dan **audit fungsi (click-path)** → 🛑 GATE | 0 |
| 2 | Kontrak API, tes karakterisasi, tes alur fungsional | 1 |
| 3 | **Perbaikan fungsi (bug)** dan bersih-bersih kode | 2 |
| 4 | Struktur dan routing (front controller Apache) | 2, 3 |
| 5 | Database: migrasi, skema, seed | 4 |
| 6 | Keamanan | 5 |
| 7 | Fitur dan peningkatan: role, approval, sesi App B, personalisasi, UX dashboard | 6 |
| 8 | Kualitas: tes, lint, uji lintas aplikasi | berjalan paralel sejak 2 |
| 9 | Dokumentasi | 7, 8 |
| 10 | Server dan deploy: **DITUNDA** | — |

## 4A. Pemakaian Workflow dan Skill (ECC / Antigravity)

Gunakan perangkat yang sudah terpasang di `.agents/` supaya kerja konsisten dan tidak ada yang terlewat.

| Kapan | Gunakan | Hasil yang diharapkan |
|---|---|---|
| Awal tiap fase | `/plan` | Rencana 5–10 langkah, disetujui sebelum mengubah kode |
| Audit (Fase 1) | `/security-scan`, `/code-review`, skill `click-path-audit` | `docs/AUDIT.md` dan `docs/FUNGSI_MATRIX.md` |
| Keputusan arsitektur (GATE 1, routing, format sesi, format respons API) | skill `architecture-decision-records` | `docs/adr/NNNN-judul.md` |
| Memperbaiki bug atau menambah perilaku | `/tdd-workflow` + skill `tdd-workflow` | Tes gagal dulu, lalu perbaikan, lalu tes lulus |
| Error sintaks, fatal error PHP, konflik dependensi | `/build-fix` | Diperbaiki bertahap, bukan tebak-tebakan |
| Menulis penanganan error | skill `error-handling` | Pola seragam: exception, log, respons ke pengguna |
| **Setelah tiap task** | `/verification-loop` | Lint, tes, dan pemeriksaan keamanan lulus |
| Akhir tiap fase | `/code-review`; `/security-scan` untuk Fase 4–7 | Ringkasan temuan dan perbaikan |
| Sebelum menyatakan fase/rilis selesai | skill `delivery-gate`, lalu `agent-self-evaluation` | Penilaian akurasi, kelengkapan, kejelasan |

**Aturan benturan:** jika `todo.md` dan `.agents/rules/*` berbeda, ikuti yang **lebih ketat** dan catat alasannya sebagai ADR.

---

## FASE 0 — Fondasi Lokal (XAMPP)

### 0.1 [W] Baseline Git
- [x] Buat tag `baseline-sebelum-refactor` pada commit saat ini.
- [x] Repo hanya punya branch `dipuy`. Ganti namanya menjadi `main` (`git branch -m dipuy main`), lalu kerjakan tiap fase di branch turunannya.
- **Selesai jika:** tag ada, `git status` bersih, nama branch kerja jelas.

### 0.2 [W] Higienis file sensitif di repo
- [x] Jalankan `git ls-files` dan laporkan file yang tidak seharusnya ada di Git: `.env`, `*.sql` berisi data nyata, `*.log`, `vendor/`, `node_modules/`.
- [x] `database/bps_solsel.sql` (dump produksi): **keluarkan dari working tree** ke folder di luar repo (mis. `..\private\`), tambahkan pola ke `.gitignore`, dan commit penghapusannya. Riwayat Git dibersihkan di task 0.2b.
- [x] Perbarui `.gitignore`: `.env`, `.env.*` (kecuali `*.example`), `vendor/`, `storage/logs/*`, `storage/uploads/*` (simpan `.gitkeep`), `*.sql` di `database/` kecuali `migrations/` dan `seeds/`, `*.log`, `.phpunit.cache/`, `node_modules/`.
- [x] Tambahkan `.editorconfig` (UTF-8, LF, indent 4 spasi untuk PHP) dan `.gitattributes` (`* text=auto eol=lf`).
- **Selesai jika:** `git ls-files | Select-String "\.env$|\.sql$|\.log$"` hanya menampilkan file migrasi/seed/example yang sah.

### 0.2b [W] Bersihkan riwayat Git dari data sensitif
Repo ini pernah publik dan riwayatnya memuat dump produksi, jadi **anggap isinya sudah terbaca pihak lain**.
- [ ] Pilih strategi (rekomendasi **opsi 1**, karena riwayat baru sedikit):
  1. **Repo baru yang bersih:** buat repo `private` baru, salin working tree yang sudah bersih (0.2), buat satu commit awal `chore: baseline`. Arsipkan atau hapus repo lama.
  2. **Tulis ulang riwayat:** `git filter-repo --path database/bps_solsel.sql --invert-paths` (tambahkan path sensitif lain), lalu force-push ke remote private.
- [ ] Jalankan `gitleaks detect` pada hasil akhir. Hasilnya harus nol temuan.
- **Selesai jika:** riwayat tidak memuat dump atau rahasia apa pun.

### 0.2c [MANUAL] Rotasi rahasia
Karena repo pernah publik, ganti semua rahasia berikut. Agent hanya menulis daftar dan menunggu konfirmasi.
- [ ] Password user database produksi
- [ ] Gmail/SMTP App Password
- [ ] Gemini API key (atau cabut jika fitur dihapus)
- [ ] API key App A ↔ App B (`API_KEY_APP_B` sama dengan `BPS_API_KEY` di App B)
- [ ] Password semua akun admin lama (dipaksa ganti pada rilis pertama, lihat 10.9)
- [ ] Jadikan repo GitHub **private** dan aktifkan secret scanning

### 0.3 [W] Composer dan autoload
- [x] Buat `composer.json`: PHP `>=8.1`, ekstensi `ext-pdo`, `ext-mbstring`, `ext-gd`, `ext-json`, `ext-curl`; autoload PSR-4 `"App\\": "src/"`.
- [x] Dependensi: `phpmailer/phpmailer`, `vlucas/phpdotenv`, `nikic/fast-route`. Dev: `phpunit/phpunit`, `phpstan/phpstan`, `friendsofphp/php-cs-fixer`.
- [x] Ganti semua `require` PHPMailer manual agar memakai autoload Composer. **Hapus folder `PHPMailer/`** setelah email tetap bisa terkirim di lokal (lihat 0.5 Mailpit).
- **Selesai jika:** `composer install` berhasil, `PHPMailer/` sudah tidak ada, halaman yang memakai email tidak error.

### 0.4 [W] `.env` yang konsisten
- [x] Tulis ulang `.env.example` lengkap dan tanpa nilai nyata
- [x] Satu nama database di semua dokumen dan kode. Buang pemakaian ganda `bps_solsel` vs `bps-solsel`.
- [x] Buat validasi saat boot: jika variabel wajib kosong, aplikasi berhenti dengan pesan jelas (tanpa mencetak nilai).
- **Selesai jika:** menghapus satu variabel wajib dari `.env` menghasilkan pesan error yang jelas.

### 0.5 [W] Lingkungan lokal XAMPP
Detail konfigurasi ada di **Lampiran E**.
- [x] Agent membuat folder `docs/xampp/` berisi contoh siap salin: `httpd-vhosts.sample.conf`, `hosts.sample.txt`, `create-db.sql`, `php-ini-notes.md` (isi dari Lampiran E1–E3).
- [x] Agent membuat folder `storage/logs/` (dengan `.gitkeep`) **sebelum** vhost dipakai, karena log Apache vhost menulis ke sana.
- [x] **[MANUAL]** Developer menerapkan: aktifkan `mod_rewrite` dan `mod_headers`, tambah vhost `admin.test`, tambah baris `hosts`, aktifkan ekstensi PHP, jalankan `create-db.sql`, restart Apache. Agent berhenti dan menunggu konfirmasi.
- [x] Mailpit (Lampiran E5) agar email uji tidak terkirim sungguhan.
- [x] **Fallback** (tidak disarankan): akses lewat subfolder `http://localhost/adminDashboard/` memakai `.htaccess` di root (Lampiran E4) dan `APP_BASE_PATH=/adminDashboard`.
- **Selesai jika:**
  - `http://admin.test/` membuka aplikasi (halaman login atau dashboard);
  - `C:\xampp\apache\bin\httpd.exe -t` menjawab `Syntax OK`;
  - `php -m` memuat `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `curl`, `openssl`;
  - email uji muncul di `http://localhost:8025`;
  - user `bps_app` **gagal** menjalankan `DROP TABLE` pada database dev.

### 0.6 [W] Database lokal dari skema awal
- [x] Impor `database/schema.sql` ke `bps_solsel_dev`. **Hapus akun default `admin/admin123` dari skema** (akun awal dibuat oleh skrip seed di 5.6).
- [x] Buat `database/seeds/dev_seed.sql` berisi data **palsu** (kategori, layanan contoh dengan campuran akses publik dan VPN).
- **Selesai jika:** dashboard menampilkan data contoh, tanpa data produksi.

### 0.7 Smoke test baseline
- [x] Buka setiap halaman dan endpoint yang ada, catat hasilnya di `docs/BASELINE.md`: berjalan / error / butuh login / kosong.
- **Selesai jika:** `docs/BASELINE.md` ada, dan semua error bawaan sudah tercatat (bukan diperbaiki dulu).

### 0.8 [W] Selaraskan `AGENTS.md` dengan dokumen ini
- [x] Perbarui `AGENTS.md` memakai versi baru yang disediakan (stack XAMPP, vhost `admin.test`, `todo.md` sebagai sumber tugas, server ditunda).
- [x] Periksa nama berkas di `.agents/workflows/`: tabel lama menulis `/tdd-workflow` dengan teks `go-test.md` tetapi tautannya `tdd-workflow.md`. Pastikan berkas yang benar dan perbaiki tabelnya.
- [x] Baca `.agents/rules/*` dan catat aturan yang lebih longgar daripada `todo.md` (aturan bagian 4A: ikuti yang lebih ketat).
- **Selesai jika:** `AGENTS.md` konsisten dengan nama berkas sebenarnya di `.agents/`.

### 0.9 [W] Buat `docs/SERVER_NOTES.md`
Dokumen hidup untuk rekonsiliasi server nanti. Agent menambahkan entri setiap kali menyentuh sesuatu yang khusus Apache/XAMPP/Windows.
- [x] Bagian: **Asumsi lingkungan lokal** (versi PHP, ekstensi, path), **Aturan khusus Apache** (`.htaccess`, vhost, `php_flag`), **Perilaku khusus Windows** (path, line ending, hosts), **Yang harus diterjemahkan** saat pindah server (daftar), **Pertanyaan terbuka untuk server**.
- **Selesai jika:** file ada dengan kerangka bagian di atas; entri pertama ditulis dari 0.5.

### 0.10 [W] ADR awal
- [x] `docs/adr/0000-template.md` (Konteks, Keputusan, Alternatif, Konsekuensi) dan `docs/adr/0001-xampp-lokal-server-ditunda.md`.
- **Selesai jika:** keputusan "lokal dulu, server ditunda" tercatat.

---

## FASE 1 — Audit (READ-ONLY, jangan ubah kode)

Hasil tulis ke `docs/AUDIT.md`.

### 1.1 [W] Inventaris file
- [ ] Tabel: file | tujuan | siapa yang memanggil | perlu login? | metode HTTP | menulis ke DB/file?
- [ ] Cakup semua `public/*.php`, `public/api/*.php`, `config/*`, `app/models/*`, `scripts/*`, `tests/*`, `.vitest/*`.

### 1.2 [W] Audit keamanan per file
Untuk tiap file di 1.1, jawab ya/tidak dan beri nomor baris:
- [ ] Memeriksa sesi login **sebelum** memproses apa pun?
- [ ] Semua query memakai prepared statement? (tandai setiap string-concat ke SQL)
- [ ] Aksi tulis memeriksa token CSRF? Ada aksi tulis lewat `GET` (mis. `hapus_*.php?id=`)?
- [ ] Keluaran HTML di-escape (`htmlspecialchars`)? Ada `echo $_GET/$_POST` mentah?
- [ ] Ada redirect dari input pengguna (open redirect)?
- [ ] Error/`phpinfo()`/versi/path server bocor ke layar?
- [ ] Endpoint API tulis (POST/PUT/DELETE) hanya dijaga API key, atau juga sesi + role?
- [ ] Header CORS `Access-Control-Allow-Origin` apa isinya? (App B memanggil dari server, CORS seharusnya tidak perlu)
- [ ] Perbandingan API key memakai `hash_equals`?
- [ ] Pendaftaran admin (`register.php`) terbuka untuk umum? Bisa dipakai mengirim email ke alamat sembarang?
- [ ] Token verifikasi email: dibuat dengan `random_bytes`? Ada kedaluwarsa? Sekali pakai?
- [ ] Upload: validasi MIME sebenarnya, nama file acak, folder tujuan, bisa dieksekusi?
- [ ] Cookie sesi: flag `HttpOnly`, `SameSite`, `Secure`; `session_regenerate_id` setelah login?

### 1.3 [W] Format password dan alur login
- [ ] Temukan **format hash password** yang dipakai tabel admin (plaintext / MD5 / SHA-1 / SHA-256 / bcrypt / bergaram?). Lihat kode pembuatan akun di `register.php` dan `kelola_admin.php`, dan hasil `SELECT CHAR_LENGTH(password), LEFT(password,4), COUNT(*) FROM <tabel> GROUP BY 1,2` di DB **lokal/seed** saja.
- [ ] **Temukan bagaimana App B login saat ini.** README tidak menampilkan endpoint login API di `public/api/`. Cari tahu: apakah App B memanggil endpoint lain, atau mengambil data admin langsung? Dokumentasikan request/response aktual.
- [ ] Catat semua tempat yang **menulis** kolom password.

### 1.4 [W] Pemakaian dan kode mati
- [ ] Untuk tiap file/fungsi: cari referensi (`Select-String -Recurse`). Tandai **tidak ada referensi**.
- [ ] Tentukan status tiap fitur: `trending-topics.php` + `scripts/generate-topics.php` (Gemini), `website.php`, `pengaturan.php`, `test_api.php`, `.vitest/`, `package.json`, `vitest.config.js`, `Layanan.php` model.
- [ ] Isi `tests/` dan `.vitest/`: tes untuk apa? Masih relevan untuk proyek PHP?

### 1.5 [W] Ringkasan dan rekomendasi
- [ ] Susun tabel temuan: ID | temuan | tingkat (Kritis/Tinggi/Sedang/Rendah) | file | perbaikan di fase berapa.
- [ ] Beri rekomendasi: **A** (perbaiki bertahap, rencana dokumen ini) atau **B** (bangun ulang dengan framework), disertai alasan singkat.

### 1.6 [W] Audit fungsi (click-path audit)
Gunakan skill `click-path-audit`. Tujuannya mengetahui **apa yang sebenarnya rusak atau tidak berfungsi**, bukan hanya apa yang tidak aman.
- [ ] Untuk **setiap halaman dashboard**, daftar semua elemen yang bisa diklik atau dikirim: tombol, tautan, form, filter, pencarian, unggah, hapus, logout.
- [ ] Telusuri tiap elemen: **UI → request → handler PHP → query → respons → tampilan akhir**.
- [ ] Untuk **setiap endpoint API**: parameter, filter, urutan, penanganan data kosong, kode status, dan format error.
- [ ] Tulis hasilnya di `docs/FUNGSI_MATRIX.md`:

| Halaman/Endpoint | Elemen/Aksi | Handler | Hasil yang diharapkan | Hasil nyata | Status | ID bug |
|---|---|---|---|---|---|---|

  Status: `berfungsi`, `rusak`, `tanpa handler`, `hasil salah`, `tanpa validasi`, `tanpa umpan balik`.
- [ ] Setiap baris bukan `berfungsi` mendapat ID bug (`BUG-001`, ...), tingkat (blokir / mayor / minor), dan perkiraan akar masalah.
- **Selesai jika:** semua halaman dan endpoint tercakup, dan tidak ada elemen yang belum ditelusuri.

### 1.7 [W] Pemindaian otomatis
- [ ] Jalankan `/security-scan` dan `/code-review`; gabungkan temuannya ke `docs/AUDIT.md`, tanpa menggandakan temuan 1.2.

### 🛑 GATE 1
Berhenti. Tunggu keputusan developer atas: A atau B; fitur Gemini; pendaftaran admin publik; `website.php`; format hash. Isi jawaban di bagian 12 sebelum lanjut.

---

## FASE 2 — Kontrak API dan Tes Karakterisasi

### 2.1 [W] Siapkan PHPUnit, Vitest, dan database tes
- [ ] **PHPUnit** (logika PHP): `phpunit.xml`, folder `tests/Unit` dan `tests/Integration`.
- [ ] **Vitest** (tes HTTP black-box ke Apache lokal): periksa dulu `tests/app.test.js` yang sudah ada. Pertahankan yang menguji perilaku nyata, hapus yang usang. Tes baru membaca `BASE_URL` (default `http://admin.test`) dan `API_KEY_APP_B` dari `.env`.
- [ ] Database `bps_solsel_test` (sudah dibuat di 0.5). `scripts/test-db-reset.php` mengosongkan dan mengisi seed; skrip **menolak berjalan** jika nama DB bukan `*_test`.
- [ ] Jalankan keduanya berurutan: `composer test` lalu `npx vitest run`.
- **Selesai jika:** satu tes contoh PHPUnit dan satu tes contoh Vitest lulus.

### 2.2 [W] Dokumentasikan kontrak nyata
- [ ] `docs/API_CONTRACT.md`: tiap endpoint yang dipakai App B (`layanan`, `kategori`, dan alur login hasil 1.3): URL, metode, header, parameter, contoh respons JSON **dari data seed**, kode status, perilaku error.
- **Selesai jika:** seorang pembaca bisa memanggil API tanpa membaca kode.

### 2.3 [W] Tes karakterisasi (mengunci perilaku saat ini)
- [ ] Tes: tanpa `X-API-KEY` → ditolak; key salah → ditolak; key benar → 200 dengan **bentuk JSON yang sama** seperti kontrak (nama field, tipe).
- [ ] Tes: halaman dashboard tanpa login → redirect ke login.
- [ ] Tes alur login yang dipakai App B (hasil 1.3).
- **Selesai jika:** semua tes lulus pada kode **belum diubah**. Ini jaring pengaman untuk Fase 3–7.

### 2.4 [W] Tes alur fungsional
Kunci alur utama sebelum mengubah kode (gunakan `docs/FUNGSI_MATRIX.md` sebagai daftar).
- [ ] Login → dashboard → buat kategori → ubah → arsip/hapus → pastikan hasilnya di API.
- [ ] Login → buat layanan (dengan unggah logo) → ubah → arsip → pastikan hasilnya di API dan logo tersimpan benar.
- [ ] Kelola pengguna: tambah, ubah, nonaktifkan.
- [ ] Tes yang menunjukkan **bug yang diketahui** ditandai `skip` dengan ID bug, lalu diaktifkan saat bug diperbaiki di Fase 3.

---

## FASE 3 — Perbaikan Fungsi dan Bersih-bersih Kode

Aturan: **satu penghapusan = satu commit**, pesan memuat bukti (hasil pencarian referensi). Setelah tiap penghapusan, `phpunit` + smoke test.

- [ ] **3.0 [W]** Perbaiki semua bug di `docs/FUNGSI_MATRIX.md`, **satu bug satu commit**, dengan `/tdd-workflow`: aktifkan tes `skip`-nya (gagal) → perbaiki → tes lulus. Urutan: bug yang memblokir login dan CRUD dulu, lalu mayor, lalu minor. Update status di matriks.
- [ ] **3.1 [W]** Pindahkan `public/test_api.php` ke `tools/test_api.php` (di luar `public/`) atau hapus. Tidak boleh bisa diakses lewat web.
- [ ] **3.2 [W]** `public/pengaturan.php`: jika hanya menampilkan info sistem (versi PHP, `phpinfo`, path), **hapus**. Jika berguna, ganti dengan halaman minimal tanpa detail server.
- [ ] **3.3** `public/api/website.php`: jika tidak dipanggil siapa pun (App B hanya memakai layanan, kategori, login), hapus dengan bukti. Jika dipakai, dokumentasikan di kontrak.
- [ ] **3.4** Fitur trending/Gemini: **ikuti keputusan GATE 1.** Default sementara: bungkus dengan `FEATURE_TRENDING` (default `false`), jangan dihapus sebelum ada keputusan. Jika diputuskan hapus: hapus `trending-topics.php`, `scripts/generate-topics.php`, `scripts/.env.example`, `GEMINI_API_KEY`, dan bagian cron di README.
- [ ] **3.5** Tooling JS: periksa `.vitest/`, `package.json`, `vitest.config.js`, dan isi `tests/`. Jika tidak menguji PHP, hapus semuanya; tes PHP yang sah pindah ke struktur PHPUnit (2.1).
- [ ] **3.6 [W]** Hapus aset yang salah tempat: pindahkan `public/uploads/logo_bps.jpg` ke `public/assets/` (folder `uploads/` hanya untuk unggahan pengguna).
- [ ] **3.7** Kode mati: jalankan PHPStan (level 3 dulu) untuk fungsi/variabel tak terpakai, dan cari `include/require` yatim. Hapus kode yang dikomentari, `var_dump`, `print_r`, `error_log` debug.
- [ ] **3.8** Hapus **duplikasi tata letak**: header, navbar, footer yang disalin di tiap halaman → dipindah ke satu layout bersama (dikerjakan di 4.6, di sini cukup tandai).
- [ ] **3.9 [W]** Rapikan `.htaccess`: karena XAMPP memakai Apache, aturannya **berlaku** di lokal. Hapus aturan basi atau duplikat (mis. `X-XSS-Protection`), pertahankan yang berguna (blokir dotfile, `.sql`, `.log`; `Options -Indexes`; front controller di 4.7), dan catat setiap aturan khusus Apache di `docs/SERVER_NOTES.md`.
- **Selesai jika:** daftar "dihapus" dan "dipertahankan" dicatat di `docs/CLEANUP.md` lengkap dengan alasan.

---

## FASE 4 — Struktur dan Routing

**Masalah:** satu file PHP per halaman dan aksi di dalam folder web, jadi setiap file harus diamankan sendiri-sendiri.
**Tujuan:** satu pintu masuk; hanya `public/index.php` yang dieksekusi.

### 4.1 [W] Struktur target (lihat Lampiran A)
- [ ] Buat folder: `src/{Http,Auth,Models,Services,Support}`, `src/views`, `routes.php`, `storage/{logs,uploads}`, `database/{migrations,seeds}`, `tests/`.

### 4.2 [W] Bootstrap
- [ ] `src/bootstrap.php`: muat `.env`, validasi variabel, set timezone, handler error/exception (log ke `storage/logs/app.log`, **tidak** menampilkan detail di layar jika `APP_DEBUG=false`), inisialisasi sesi (Lampiran F), koneksi PDO lazy:
  - `ERRMODE_EXCEPTION`, `ATTR_EMULATE_PREPARES=false`, `utf8mb4`, `FETCH_ASSOC`.
- **Selesai jika:** error sengaja (mis. query salah) tercatat di log dan **tidak** tampil di browser saat `APP_DEBUG=false`.

### 4.3 [W] Router dan front controller
- [ ] `public/index.php` memanggil bootstrap, lalu `FastRoute` memuat `routes.php`.
- [ ] Middleware sederhana (fungsi/kelas kecil): `ApiKey`, `RequireLogin`, `RequireRole`, `VerifyCsrf`. Default rute **dilindungi**; rute publik harus dinyatakan eksplisit.
- [ ] 404 dan 405 mengembalikan halaman (dashboard) atau JSON (API) yang seragam.
- [ ] **Base path:** router membaca `APP_BASE_PATH` dari `.env` (kosong untuk vhost `admin.test`, `/adminDashboard` untuk fallback subfolder), membuangnya dari `REQUEST_URI` sebelum mencocokkan rute, dan menyediakan helper `url('/layanan')` untuk semua tautan dan form. **Tidak ada URL yang ditulis keras** di view.

### 4.4 [W] Pindahkan halaman satu per satu
Urutan: `login`/`logout` → dashboard (`index`) → `kategori` → `layanan` → `kelola_admin`.
Untuk tiap halaman: controller di `src/Http/`, view di `src/views/`, rute di `routes.php`, tes, lalu hapus file lama.
- [ ] Aksi yang mengubah data **hanya `POST`** + CSRF, contoh `POST /layanan/{id}/hapus`. Tidak ada `hapus_*.php` lewat `GET`.
- [ ] URL bersih: `/layanan`, `/layanan/tambah`, `/layanan/{id}/edit`, `/kategori`, `/admin/pengguna`, dst.
- **Selesai jika:** tidak ada lagi `public/*.php` selain `index.php`, dan semua alur dashboard bisa dijalankan.

### 4.5 [W] API v1 dan alias kompatibilitas
- [ ] Endpoint baru: `/api/v1/layanan`, `/api/v1/kategori`, `/api/v1/auth/...` (lihat Lampiran D).
- [ ] **Pertahankan alias lama** (`/api/layanan.php`, `/api/kategori.php`) dengan **bentuk JSON lama**, sampai App B beralih. Tes kontrak Fase 2 harus tetap lulus.
- [ ] Format respons v1 seragam (Lampiran C). Helper `Json::ok()` dan `Json::error()`.
- [ ] **Hapus semua header CORS.** Hanya server-ke-server.

### 4.6 Layout bersama
- [ ] `src/views/layout.php` (header, navbar, flash message, footer). Helper `e()` untuk escape.

### 4.7 [W] Apache ketat (`.htaccess` front controller)
- [ ] Terapkan `public/.htaccess` versi **ketat** (Lampiran E4): semua permintaan yang bukan berkas nyata dialihkan ke `index.php`, hanya `index.php` yang boleh dieksekusi, `.php` lain dijawab 403.
- [ ] `public/uploads/.htaccess` (Lampiran E4) mematikan eksekusi PHP di folder unggahan.
- [ ] Catat semua direktif Apache ini di `docs/SERVER_NOTES.md` sebagai "perlu diterjemahkan".
- **Selesai jika:** menaruh `public/uploads/x.php` lalu mengaksesnya menghasilkan 403/404; membuka `/login.php` lama menghasilkan 403/404; seluruh dashboard tetap berjalan.

---

## FASE 5 — Database: Migrasi, Skema, Seed

### 5.1 [W] Runner migrasi sederhana
- [ ] `database/migrations/NNN_nama.sql` (naik) dan `NNN_nama.down.sql` (turun). Tabel `schema_migrations(version, applied_at)`.
- [ ] `scripts/migrate.php` dengan opsi `--status`, `--up`, `--down=1`. Memakai user `bps_migrator`.
- **Selesai jika:** `--status` menampilkan daftar, `--up` idempoten (aman diulang).

### 5.2 [W] Migrasi bernomor (tulis, uji naik-turun di DB tes)
- [ ] `001_baseline.sql`: skema dari `schema.sql` **tanpa seed password**.
- [ ] `002_integritas.sql`: foreign key (mis. `layanan.kategori_id → kategori.id`), `utf8mb4`, `created_at`/`updated_at`, indeks. **Sebelum menulisnya**, buat query pendeteksi baris yatim dan jalankan pada DB seed.
- [ ] `003_password_kolom.sql`: password jadi `VARCHAR(255) NOT NULL`.
- [ ] `004_units.sql`: tabel `units(id, nama)` (unit kerja untuk scope leader).
- [ ] `005_role.sql`: kolom `role ENUM('user','leader','admin')`, `unit_id` FK, `is_active`, `must_change_password TINYINT(1) DEFAULT 0`, `failed_attempts`, `locked_until`, `last_login_at` pada tabel admin/pengguna.
- [ ] `006_sessions.sql`: `sessions(token CHAR(64) PK, user_id FK ON DELETE CASCADE, expires_at, created_at, ip, user_agent)` + indeks `expires_at`.
- [ ] `007_audit_log.sql`: `audit_log(id, user_id, action, entity, entity_id, meta JSON NULL, created_at)`.
- [ ] `008_layanan_workflow.sql`: `status ENUM('draft','pending','published','archived') DEFAULT 'published'`, `unit_id`, `search_aliases TEXT NULL`, `updated_by`, `reject_reason VARCHAR(255) NULL`.
- [ ] `009_settings.sql`: `settings(skey VARCHAR(50) PK, svalue TEXT)` untuk kontak admin dll.
- [ ] `010_login_attempts.sql` (jika pembatas login berbasis DB dipilih di 6.4).
- [ ] `011_user_bookmarks.sql`: `user_bookmarks(id, user_id FK CASCADE, title VARCHAR(150), url VARCHAR(500), note VARCHAR(500) NULL, created_at, updated_at)` + indeks `user_id`.
- [ ] `012_link_clicks.sql`: `link_clicks(id, user_id FK CASCADE, layanan_id FK, clicked_at)` + indeks `(user_id, clicked_at)` dan `(layanan_id)`.
- [ ] `013_user_preferences.sql`: `user_preferences(user_id PK FK CASCADE, pinned_layanan JSON, search_history JSON, theme VARCHAR(10) NULL, updated_at)`.
- [ ] **Prinsip migrasi untuk rilis (penting untuk rollback, lihat 10.3):** migrasi hanya **menambah** (tabel/kolom/indeks). Penghapusan atau perubahan destruktif dilakukan di rilis berikutnya, supaya kode rilis lama tetap berjalan di skema baru.
- **Selesai jika:** `--up` pada DB kosong menghasilkan skema lengkap, dan `--down` bisa membalik migrasi terakhir.

### 5.3 [W] Soft delete
- [ ] Hapus layanan/kategori = arsipkan (`status='archived'` / `is_active=0`), bukan `DELETE`. Semua query publik hanya mengambil yang aktif dan `published`.

### 5.4 [W] Seed pengembangan
- [ ] `database/seeds/dev_seed.php`: membuat unit contoh, kategori, layanan, dan **tiga akun** (`admin`, `leader`, `user`) dengan **password acak yang dicetak sekali ke konsol** atau diambil dari `DEV_ADMIN_PASSWORD`. **Tidak ada password tetap di kode.**
- [ ] Skrip `scripts/db-reset-dev.php` (menolak jalan jika bukan database `*_dev`): migrasi → seed.

### 5.5 Hak akses DB minimal
- [ ] Verifikasi: aplikasi berjalan dengan `bps_app` (tanpa `ALTER/DROP`). Uji bahwa `DROP TABLE` lewat `bps_app` ditolak.

---

## FASE 6 — Keamanan

### 6.1 [W] Konfigurasi dan error
- [ ] `APP_DEBUG=false` ⇒ tidak ada stack trace, path, atau SQL di respons. Pesan generik + `request_id` untuk dicocokkan dengan log.
- [ ] Log di `storage/logs/` (di luar `public/`), tanpa memuat password, token, atau API key.

### 6.2 [W] Sesi dashboard
- [ ] Terapkan Lampiran F: `use_strict_mode`, cookie `HttpOnly` + `SameSite=Lax` + `Secure` sesuai skema `APP_URL`.
- [ ] `session_regenerate_id(true)` setelah login dan setelah perubahan role.
- [ ] Timeout tidak aktif (`SESSION_LIFETIME_MIN`) dan timeout absolut; logout menghancurkan sesi dan cookie.

### 6.3 [W] Password
- [ ] `PasswordService` (Lampiran F): `password_verify` + **upgrade-on-login** dari format lama (sesuai hasil 1.3) + `password_needs_rehash`.
- [ ] **Semua** jalur tulis password (buat, ubah, reset, seed) memakai `password_hash()`. Hapus perbandingan password di SQL (`... = MD5(?)`).
- [ ] Kebijakan minimal: panjang ≥ 10, bukan sama dengan username, maksimal 255 karakter.
- [ ] Setelah semua akun lokal terbukti upgrade, cabang legacy ditandai `TODO: hapus setelah migrasi` dengan catatan di `docs/CLEANUP.md`.

### 6.4 [W] Anti brute-force
- [ ] Per akun: setelah 5 kegagalan, `locked_until = now + 10 menit`; reset saat sukses.
- [ ] Per IP: batas wajar di aplikasi (tabel `login_attempts` atau cache file), sebagai pelengkap pembatas Nginx nanti.
- [ ] Pesan error **sama** untuk "user tidak ada" dan "password salah"; lakukan `password_verify` palsu saat user tidak ada agar waktu respons mirip.

### 6.5 [W] CSRF
- [ ] Middleware `VerifyCsrf` untuk semua `POST/PUT/PATCH/DELETE` dashboard. Token per sesi, perbandingan `hash_equals`. Helper `csrf_field()`.
- **Selesai jika:** POST tanpa token mendapat 419/403 pada **semua** form (termasuk hapus dan logout).

### 6.6 [W] Otorisasi (role dan scope)
- [ ] Matriks role di Lampiran B diterapkan di **router** (bukan hanya menyembunyikan tombol). `RequireRole` default-deny.
- [ ] Leader hanya boleh mengubah layanan dengan `unit_id` miliknya; hasil perubahan selalu `status='pending'`.
- [ ] Setiap perubahan penting menulis ke `audit_log` (siapa, apa, kapan).
- [ ] **ID dari URL/body tidak dipercaya** untuk kepemilikan; selalu cek terhadap sesi.

### 6.7 [W] Pendaftaran dan email
- [ ] `ALLOW_PUBLIC_REGISTER=false` secara default. Jika `false`, rute `register` menjawab 404.
- [ ] Pembuatan akun dilakukan admin lewat halaman pengguna (7.2), bukan self-service.
- [ ] Jika verifikasi email dipertahankan: token `random_bytes(32)`, simpan **hash**-nya, kedaluwarsa 1 jam, sekali pakai, dibatasi laju pengiriman per alamat dan IP.
- [ ] Email dikirim lewat satu `Mailer` service (bukan endpoint publik `send_*.php`).

### 6.8 [W] Output, input, dan CSP
- [ ] Semua keluaran HTML lewat `e()`. Tidak ada `echo` variabel mentah di view.
- [ ] `Validator` kecil: tipe, panjang maksimal, enum, `FILTER_VALIDATE_URL` dengan skema hanya `http/https`; cast integer untuk semua ID.
- [ ] Pindahkan JavaScript inline ke `public/assets/*.js`, lalu terapkan header `Content-Security-Policy` (tanpa `unsafe-inline`; pakai nonce jika masih ada inline).

### 6.9 [W] Upload
- [ ] Validasi ukuran, ekstensi **dan** MIME asli (`finfo`), **olah ulang gambar dengan GD** (membuang payload tersembunyi), nama file acak (`bin2hex(random_bytes(16))`), simpan di `public/uploads/` yang tidak bisa mengeksekusi PHP (diverifikasi oleh 4.7).
- [ ] Tes: unggah `shell.php`, `gambar.php.jpg`, dan GIF berisi PHP → semua ditolak atau dinetralkan.

### 6.10 [W] API key dan API
- [ ] `API_KEY_APP_B` dibandingkan dengan `hash_equals`; tidak pernah dicatat di log.
- [ ] Endpoint tulis API (jika ada) **wajib sesi pengguna + role**, tidak cukup API key.
- [ ] Batasi metode HTTP per rute; metode lain → 405.

### 6.11 [W] Header keamanan
- [ ] `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` (atau CSP `frame-ancestors 'none'`), `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` ketat, `Cache-Control: no-store` untuk halaman terotentikasi.
- [ ] `Strict-Transport-Security` hanya dikirim jika `APP_URL` berskema `https`. Hapus `X-XSS-Protection` (usang).

### 6.12 Pemindaian dan dependensi
- [ ] `composer audit` bersih. Pindai rahasia dengan `gitleaks` (via Docker) pada working tree dan riwayat. Temuan harus nol.

### 6.13 [W] Tes keamanan otomatis (PHPUnit)
- [ ] Tanpa login → redirect/401; role salah → 403; scope unit salah → 403.
- [ ] POST tanpa CSRF → ditolak.
- [ ] Payload SQLi pada parameter (`' OR 1=1 --`) → tidak mengubah hasil, tidak error SQL.
- [ ] Payload XSS pada nama layanan → tampil ter-escape.
- [ ] Login: pesan sama untuk user tidak ada vs password salah; kunci akun setelah 5 gagal.
- [ ] Session fixation: ID sesi berubah setelah login.
- [ ] Upload berbahaya ditolak. Akses `/uploads/x.php` → 403/404.

---

## FASE 7 — Fitur Backend untuk Portal (App B) dan Dashboard

### 7.1 [W] Auth API untuk App B
Spesifikasi di Lampiran D.
- [ ] `POST /api/v1/auth/login` (butuh `X-API-KEY`): menerima **password mentah**, memverifikasi (6.3/6.4), membuat baris `sessions` (token `bin2hex(random_bytes(32))`, kedaluwarsa 8 jam), mengembalikan token dan data pengguna.
- [ ] `GET /api/v1/auth/session` (header `X-Session`): valid → data pengguna; tidak valid/kedaluwarsa → 401.
- [ ] `POST /api/v1/auth/logout`: hapus baris sesi.
- [ ] Hapus sesi kedaluwarsa secara oportunistik saat login.
- [ ] Pertahankan alur login lama sebagai alias (hasil 1.3) sampai App B beralih.
- **Selesai jika:** tes HTTP untuk login, session, dan logout lulus, dan token tidak pernah muncul di log.

### 7.2 [W] Manajemen pengguna (admin saja)
- [ ] Halaman daftar, tambah, ubah role/unit, nonaktifkan, reset password (admin membuat password sementara + wajib ganti).
- [ ] Admin tidak bisa menurunkan/menonaktifkan dirinya jika itu admin terakhir.

### 7.3 [W] Alur persetujuan layanan
- [ ] Leader tambah/ubah layanan unitnya → `pending`. Admin: daftar `pending`, tombol Setujui (`published`) dan Tolak (`draft` + `reject_reason`).
- [ ] Setiap transisi menulis `audit_log`. Halaman riwayat audit (admin).

### 7.4 [W] API publik hanya data yang sah
- [ ] `/api/v1/layanan` dan alias lama: hanya `status='published'` dan aktif. Tambahkan field baru **tanpa** menghapus field lama: `aliases` (array), `updated_at`.
- [ ] Tes kontrak diperbarui dan tetap kompatibel.

### 7.5 `search_aliases`
- [ ] Field di form layanan (dipisah koma), dinormalisasi (huruf kecil, trim, unik), disimpan di `search_aliases`, dikeluarkan sebagai array `aliases`.

### 7.6 Pengaturan (kontak bantuan)
- [ ] CRUD kecil `settings` untuk admin (email/WhatsApp unit, jam layanan). `GET /api/v1/settings/public` mengembalikan hanya kunci yang diperbolehkan (whitelist).

### 7.7 [W] Akun pegawai (role `user`)
- [ ] Admin membuat akun pegawai lewat **impor CSV** (`username, nama, unit`). Password sementara acak dibuat per akun, ditampilkan/diekspor **sekali**, dan `must_change_password=1`.
- [ ] Saat login pertama pengguna wajib mengganti password (validasi 6.3). Role `user` tidak bisa masuk dashboard.
- **Selesai jika:** impor 20 akun contoh berhasil, dan login pertama memaksa ganti password.

### 7.8 [W] Preferensi pribadi (riwayat pencarian, pin, tema)
- [ ] `GET /api/v1/me/preferences` dan `PUT /api/v1/me/preferences`: `pinned_layanan` (maks 12 ID), `search_history` (maks 8, teks maks 100 karakter), `theme`.
- [ ] `user_id` selalu dari sesi (`X-Session`), tidak pernah dari body. Validasi tiap ID layanan benar-benar ada.

### 7.9 [W] Bookmark pribadi
- [ ] CRUD `/api/v1/me/bookmarks`: maks 100 per pengguna; judul ≤150, URL ≤500 (hanya `http/https`), catatan ≤500 sebagai **teks biasa**.
- [ ] Semua `UPDATE/DELETE` memakai `... AND user_id = ?`. **Tidak ada** endpoint (termasuk admin) yang membaca bookmark milik orang lain.
- [ ] Tes: pengguna A tidak bisa membaca/mengubah/menghapus bookmark pengguna B (harus 404 atau 403).

### 7.10 Klik dan statistik pribadi (bahan penghargaan)
- [ ] `POST /api/v1/me/clicks` `{layanan_id}`; pembatas laju (mis. 60 per menit per pengguna).
- [ ] `GET /api/v1/me/stats`: layanan paling sering dibuka, jumlah layanan berbeda yang dipakai, streak harian, dan **lencana yang dihitung server** dari data milik sendiri (contoh: "Penjelajah" 10 layanan berbeda, "Rutin" streak 7 hari). **Tanpa leaderboard antar pegawai.**
- [ ] Retensi klik 12 bulan (dibersihkan oleh `scripts/cleanup.php`, 10.7).
- [ ] Catat data yang disimpan di `docs/PRIVACY_NOTES.md` (9.4).

### 7.11 [W] Health endpoint
- [ ] `GET /api/v1/health` (tanpa autentikasi): cek koneksi DB singkat (timeout 2 detik), balas `{"data":{"status":"ok"},"error":null}` atau 503. **Tidak menampilkan** versi, path, atau detail error.

### 7.12 [W] Tabel kelola yang nyaman (layanan, kategori, pengguna)
- [ ] Pencarian, filter (kategori, akses publik/VPN, status), pengurutan, dan **paginasi sisi server** (10/25/50). Parameter ada di URL sehingga hasilnya bisa dibagikan.
- [ ] Keadaan kosong yang jelas ("Belum ada layanan") dan pesan saat pencarian tanpa hasil.

### 7.13 [W] Form yang andal
- [ ] Validasi di server dengan pesan **per field**; nilai yang sudah diisi tetap tampil saat gagal; validasi klien ringan sebagai bantuan (bukan pengganti).
- [ ] Konfirmasi sebelum hapus/arsip (dialog), pesan sukses/gagal (flash), pencegahan klik ganda, dan indikator proses.
- [ ] Unggah logo: pratinjau, penggantian menghapus berkas lama, dan tampilan cadangan (inisial) bila logo tidak ada.

### 7.14 Dashboard ringkasan yang benar
- [ ] Angka total layanan, publik, VPN, kategori, dan `pending`; aktivitas terbaru dari `audit_log`; pintasan ke aksi umum. Query efisien dan angkanya cocok dengan data.

### 7.15 Aksesibilitas, responsif, dan halaman error
- [ ] Label form terhubung ke input, urutan fokus keyboard wajar, kontras cukup, tabel dapat dipakai di layar kecil.
- [ ] Halaman 403/404/500 ramah pengguna **tanpa detail teknis**.

### 7.16 [OPSIONAL] Ekspor dan impor layanan
- [ ] Ekspor CSV daftar layanan (admin). Impor CSV dengan **pratinjau dan validasi per baris** sebelum disimpan; baris gagal dilaporkan, tidak membatalkan seluruhnya diam-diam.

---

## FASE 8 — Kualitas

### 8.1 [W] Skrip Composer
```
composer test       # phpunit
composer lint       # php-cs-fixer --dry-run (PSR-12)
composer analyse    # phpstan (mulai level 5, naik bertahap)
composer check      # ketiganya
npx vitest run      # tes HTTP black-box (BASE_URL=http://admin.test)
```
- **Selesai jika:** `composer check` lulus bersih di terminal PowerShell.

### 8.2 [W] Cakupan tes minimum
- [ ] Unit: `Validator`, `PasswordService`, `Csrf`, helper `e()`.
- [ ] Integrasi: model/query (DB tes), migrasi naik-turun.
- [ ] HTTP: kontrak API, otorisasi, CSRF, upload, login.

### 8.3 CI (opsional, ditunda sampai repo bersih)
- [ ] `.github/workflows/ci.yml`: PHP 8.3, layanan MySQL, `composer install`, migrasi, `composer check`. Berjalan pada pull request.

### 8.4 Git hook lokal
- [ ] Pre-commit: tolak file `.env`, `*.sql` di luar `migrations/seeds`, dan string yang mirip kunci/rahasia.

### 8.5 [W] Checklist QA manual
- [ ] `docs/QA_CHECKLIST.md`: login/logout, tiap role, alur persetujuan, upload, tampilan mobile, pesan error, tanpa JavaScript error di konsol.

### 8.6 [W] Uji lintas aplikasi (lokal)
- [ ] Jalankan App B lokal (`npm run dev`) dengan `API_BASE_URL=http://admin.test` dan `BPS_API_KEY` sama dengan `API_KEY_APP_B`.
- [ ] Skenario: portal memuat layanan dan kategori; login admin berhasil; logout; login gagal berulang menampilkan pesan terkunci/429 yang wajar; layanan `pending` **tidak** tampil di portal, tampil setelah disetujui; layanan yang diarsipkan hilang dari portal.
- **Selesai jika:** semua skenario berjalan dan hasilnya dicatat di `docs/QA_CHECKLIST.md`.

### 8.7 [W] Gerbang akhir
- [ ] Jalankan `/verification-loop` dan `/security-scan`, lalu skill `delivery-gate` dan `agent-self-evaluation`. Tuliskan ringkasannya di `docs/GO_NO_GO.md`.

---

## FASE 9 — Dokumentasi

- [ ] **9.1 [W]** `README.md` baru: prasyarat, jalankan lokal (Docker dan fallback), migrasi dan seed, menjalankan tes, struktur folder, akun seed. **Tanpa password contoh yang tampak nyata.**
- [ ] **9.2 [W]** `docs/ARCHITECTURE.md`: diagram alur request, struktur, matriks role, skema database.
- [ ] **9.3** Server dan deploy **ditunda** (Fase 10). README cukup merujuk `docs/SERVER_NOTES.md` sebagai catatan asumsi lingkungan.
- [ ] **9.4** `docs/PRIVACY_NOTES.md`: daftar data pribadi yang disimpan (akun, riwayat pencarian, klik, bookmark), tujuan, masa simpan, dan teks usulan untuk modal Kebijakan Privasi di App B.
- [ ] **9.5** `CHANGELOG.md`, tag `v0.9.0-rc1` (hanya lokal).
- [ ] **9.6 [W]** Pastikan seluruh dokumen konsisten dengan kode (README, ARCHITECTURE, API_CONTRACT, QA_CHECKLIST, PRIVACY_NOTES). Lalu jalankan Go/No-Go (bagian 11) dan tulis hasilnya di `docs/GO_NO_GO.md`.

---

## FASE 10 — Persiapan Deploy (DITUNDA — jangan dikerjakan sekarang)

> **STATUS: DITUNDA.** Bagian ini adalah **draf** dari rencana awal dan mengasumsikan Docker dan Nginx. Akan direkonsiliasi dengan server yang sebenarnya **setelah App A berfungsi penuh di lokal**. Satu-satunya yang dikerjakan sekarang dari area ini adalah mengisi `docs/SERVER_NOTES.md` (0.9).
>
> Tujuan awal: setelah fase ini, deploy ke server cukup mengikuti runbook **tanpa improvisasi**. Langkah yang menyentuh server ditandai **[MANUAL]**.

### 10.1 [W] Build rilis dengan satu perintah
- [ ] File `VERSION` (mis. `1.0.0`). Versi tampil kecil di footer dashboard.
- [ ] `scripts/build-release.ps1` (Windows) dan `scripts/build-release.sh` (kontainer): pastikan `git status` bersih → `composer install --no-dev --optimize-autoloader --classmap-authoritative` pada folder sementara → salin hanya yang perlu (`public/`, `src/`, `config/`, `database/migrations/`, `scripts/`, `deploy/`, `vendor/`, `routes.php`, `composer.json`, `VERSION`) → tulis `release.json` (`version`, `git_sha`, `built_at`) → `release/app-<versi>.tar.gz` + `.sha256`.
- [ ] Skrip memeriksa isi arsip dan **gagal** jika memuat: `.env*`, `tests/`, `docker/`, `docs/`, `.git/`, `node_modules/`, `database/seeds/`, atau paket dev (PHPUnit, PHPStan).
- **Selesai jika:** `release/` berisi arsip dan checksum, dan pemeriksaan isi lulus.

### 10.2 [W] Konfigurasi server (contoh siap salin)
Taruh di `deploy/`, tanpa nilai rahasia nyata. Isi acuan ada di Lampiran H.
- [ ] `nginx.production.conf.example`: HTTPS, redirect HTTP→HTTPS, versi **ketat** (hanya `index.php` dieksekusi), pembatas laju login, HSTS.
- [ ] `php.production.ini.example`: `display_errors=Off`, `log_errors=On`, `expose_php=Off`, batas upload, cookie sesi aman, `open_basedir`, `disable_functions` (periksa dulu aplikasi tidak memakainya).
- [ ] `.env.production.example`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, semua kunci, **tanpa** nilai nyata.
- [ ] `logrotate.example` dan `crontab.example`.

### 10.3 [W] Skrip deploy dan rollback
Pola rilis atomik: tiap rilis di folder sendiri, `current` adalah symlink (Lampiran H1, H2).
- [ ] `deploy/deploy.sh <arsip>`: ekstrak ke `releases/<timestamp>` → tautkan `shared/.env`, `shared/storage`, `shared/uploads` → **backup DB** → `migrate.php --up` → alihkan `current` → reload PHP-FPM → **health check** → rollback otomatis jika gagal → simpan 5 rilis terakhir.
- [ ] `deploy/rollback.sh`: kembali ke rilis sebelumnya. **Catatan:** migrasi DB tidak dibalik otomatis; karena itu migrasi rilis hanya menambah (lihat 5.2).
- [ ] `deploy/healthcheck.sh`: memanggil `/api/v1/health` dan memeriksa `"status":"ok"`.
- [ ] Semua skrip: `set -euo pipefail`, pesan jelas, tidak mencetak rahasia, dan **bisa diuji di kontainer lokal** (10.8) dengan jalur direktori yang dapat diganti.

### 10.4 [W] Struktur dan izin di server
- [ ] Dokumentasikan struktur (Lampiran H1): `releases/`, `current`, `shared/{.env,storage,uploads}`, `backups/`.
- [ ] Aturan izin: aplikasi berjalan sebagai user non-root; `shared/.env` mode `600`; hanya `storage/` dan `public/uploads` yang bisa ditulis proses web; kode rilis hanya-baca.
- [ ] Document root di Nginx/CloudPanel menunjuk ke `.../current/public`.

### 10.5 [W] Backup dan restore
- [ ] `deploy/backup-db.sh`: `mysqldump --single-transaction --routines --triggers`, kompres, mode file `600`, simpan 14 hari. Kredensial lewat `~/.my.cnf` (mode `600`), tidak di baris perintah.
- [ ] Backup `shared/uploads` mingguan.
- [ ] `deploy/restore-db.sh`: wajib argumen eksplisit nama database tujuan dan flag konfirmasi; menolak berjalan tanpa keduanya.
- [ ] **[MANUAL]** Tentukan salinan di luar server (Q14) dan jadwalkan; uji restore ke database sementara sebulan sekali.
- **Selesai jika:** backup → restore di lokal menghasilkan jumlah baris yang sama di semua tabel.

### 10.6 [W] Health check dan pemantauan
- [ ] Gunakan endpoint 7.11. **[MANUAL]** Daftarkan pemantau uptime eksternal gratis untuk `/api/v1/health`.
- [ ] Peringatan jika backup gagal (skrip keluar non-nol → email lewat `MAILTO` cron atau webhook).
- [ ] Log aplikasi di `shared/storage/logs/` dengan rotasi (10.2).

### 10.7 [W] Cron dan pembersihan
- [ ] `scripts/cleanup.php`: hapus sesi kedaluwarsa, `login_attempts` > 1 hari, token verifikasi kedaluwarsa, `link_clicks` > 12 bulan. Aman dijalankan berulang (idempoten) dan mencatat jumlah baris yang dihapus.
- [ ] `crontab.example`: cleanup tiap jam, backup DB harian, backup uploads mingguan, dan generator trending jika fitur dipertahankan.

### 10.8 [W] Latihan produksi lokal
Menjalankan rilis nyata di lokal dengan perilaku produksi, **sebelum** menyentuh server.
- [ ] Profil Docker Compose `prodlike`: ekstrak arsip rilis (bukan folder kerja), `APP_ENV=production`, `APP_DEBUG=false`, Nginx **ketat**, HTTPS lokal (mis. `mkcert`) agar cookie `Secure` dan HSTS teruji.
- [ ] Jalankan `deploy.sh` dan `rollback.sh` terhadap folder tiruan; buktikan rollback otomatis saat health check dipaksa gagal.
- [ ] Skrip `scripts/verify-prod.ps1` dengan pemeriksaan berikut (semua harus sesuai):

| Permintaan | Hasil yang benar |
|---|---|
| `/.env`, `/composer.json`, `/vendor/`, `/src/`, `/database/`, `/storage/` | 404 |
| `/uploads/x.php` (file uji) | 403 atau 404 |
| `/login.php`, `/test_api.php` (file lama) | 404 |
| Error yang dipicu sengaja | Tanpa stack trace, path, atau SQL |
| Header respons | `nosniff`, CSP, `Referrer-Policy`, HSTS (HTTPS) |
| Cookie sesi | `HttpOnly`, `Secure`, `SameSite=Lax` |
| `POST` tanpa token CSRF | 419/403 |
| Login salah 6 kali berturut-turut | Terkunci/429 |
| `/api/v1/layanan` tanpa kunci | 401 |
| `/api/v1/health` | 200 `ok` |

- **Selesai jika:** semua baris tabel lulus dan hasilnya disimpan di `docs/VERIFY_PROD.md`.

### 10.9 [W] Latihan migrasi data produksi lama
Aplikasi pernah live, jadi database produksi sudah berisi tabel dan data lama. Migrasi harus diuji pada **salinan** sebelum menyentuh produksi.
- [ ] **[MANUAL]** Ambil dump produksi, simpan **di luar repo** dan terenkripsi. Dump ini tidak pernah di-commit.
- [ ] Impor ke database lokal terpisah `bps_solsel_rehearsal` (skrip menolak nama lain).
- [ ] `scripts/schema-diff.php`: bandingkan `SHOW CREATE TABLE` salinan dengan `001_baseline.sql`; laporkan selisih.
- [ ] `migrate.php --baseline=001`: menandai baseline sebagai sudah diterapkan **tanpa menjalankannya** pada DB yang tabelnya sudah ada (jika skema sama atau sudah disesuaikan).
- [ ] Jalankan `migrate.php --up` dan catat masalahnya: baris yatim yang menghalangi foreign key (query deteksi + keputusan perbaikan tertulis), kolom yang perlu dilebarkan, charset campur.
- [ ] Data akun: petakan admin lama ke role (Q11); set `must_change_password=1` untuk semua; **nonaktifkan** akun `admin` bawaan jika masih memakai password `admin123` (periksa dengan mencocokkan hash, bukan dengan mencoba login).
- [ ] Verifikasi: jumlah baris per tabel sebelum dan sesudah; aplikasi berjalan di data salinan; **tes kontrak App B lulus** dengan data nyata; login admin lama berhasil dan password ter-upgrade ke bcrypt.
- [ ] Tulis `docs/MIGRASI_DATA.md`: urutan langkah produksi (halaman pemeliharaan → backup → deploy rilis → migrasi → verifikasi → buka kembali), perkiraan durasi hasil latihan, dan kriteria berhenti/rollback.
- [ ] `public/maintenance.html` dan contoh pengalihan 503 di Nginx.

### 10.10 [MANUAL] Pengerasan server
Agent menulis daftar; manusia mengerjakan dan mencentang.
- [ ] Hanya port 80/443 (dan SSH yang dibatasi) terbuka; SSH hanya dengan kunci, login root dimatikan.
- [ ] Update sistem dan panel (CloudPanel) terbaru; versi PHP yang masih didukung.
- [ ] `shared/.env` mode `600`, dimiliki user aplikasi.
- [ ] User database aplikasi berhak minimal (`SELECT, INSERT, UPDATE, DELETE`), user migrasi terpisah; database tidak terbuka dari luar.
- [ ] Rahasia lama sudah dirotasi (0.2c).
- [ ] Pembatasan akses API (10.12) dan pembatas laju login di Nginx aktif.

### 10.11 [W] Runbook go-live (`docs/DEPLOY.md`)
- [ ] Urutan lengkap: persiapan → backup → halaman pemeliharaan → deploy → migrasi → health check → **verifikasi pasca-deploy** (tabel di 10.8 diulang terhadap domain produksi) → buka kembali.
- [ ] **Kriteria rollback** yang jelas (mis. health check gagal, login admin gagal, error 5xx berulang) dan langkah tepatnya.
- [ ] Daftar variabel `.env` produksi, lokasi log, perintah berguna, dan kontak darurat.

### 10.12 [W] Integrasi dengan App B saat deploy
- [ ] Urutan: deploy App A dengan **alias lama aktif** → verifikasi → deploy App B yang memakai `/api/v1` → pantau → hapus alias lama pada rilis berikutnya.
- [ ] Variabel yang harus sinkron: `API_KEY_APP_B` (App A) = `BPS_API_KEY` (App B); `API_BASE_URL` di App B menunjuk ke App A.
- [ ] Jika App A dan App B satu VPS (Q13): pertimbangkan vhost internal `127.0.0.1:8081` khusus `/api/v1/*` untuk App B, dan di domain publik batasi `/api/` agar hanya bisa diakses dari IP yang diizinkan. Jika tidak, andalkan API key + pembatas laju.
- [ ] Jika nama domain App A diganti (Q1): perbarui `API_BASE_URL` di App B, sertifikat SSL, dan pengalihan 301 dari domain lama.

### 10.13 [W] Penutup
- [ ] Jalankan seluruh **Go/No-Go (bagian 11)** dan tulis hasilnya di `docs/GO_NO_GO.md`.
- [ ] Buat tag `v1.0.0` (lokal), perbarui `CHANGELOG.md`, bangun rilis final (10.1).
- [ ] **[MANUAL]** Eksekusi deploy mengikuti `docs/DEPLOY.md`.

---

## 11. Checklist Go / No-Go (tahap lokal)

**Wajib — fungsi:**
- [ ] Semua baris `docs/FUNGSI_MATRIX.md` berstatus *berfungsi*: login, logout, dashboard, CRUD kategori, CRUD layanan (+ unggah logo), kelola pengguna, persetujuan layanan
- [ ] Kontrak API ke App B terjaga (tes lulus); uji lintas aplikasi lokal (8.6) lulus
- [ ] Alur persetujuan: layanan `pending` tidak tampil di portal, tampil setelah disetujui

**Wajib — keamanan:**
- [ ] Tidak ada rahasia atau data produksi di repo; riwayat bersih (0.2b); rahasia lama dirotasi (0.2c)
- [ ] Tidak ada akun default; pendaftaran publik mati
- [ ] Password memakai `password_hash`; upgrade dari format lama teruji
- [ ] Sesi aman; anti brute-force; pesan login seragam
- [ ] CSRF di semua aksi tulis; tidak ada aksi tulis lewat `GET`
- [ ] Semua rute diperiksa role dan scope di router; tes otorisasi lulus
- [ ] Semua query prepared; semua output di-escape
- [ ] `APP_DEBUG=false` tidak membocorkan detail (diuji dengan nilai `false`)
- [ ] Hanya `index.php` yang dieksekusi; folder `uploads/` tidak bisa menjalankan PHP (diuji lokal)

**Wajib — kualitas:**
- [ ] Migrasi naik-turun teruji; user DB aplikasi berhak minimal
- [ ] `composer check` dan `npx vitest run` lulus
- [ ] `docs/SERVER_NOTES.md` terisi

**Sangat disarankan:**
- [ ] Fitur tak terpakai sudah dihapus dan tercatat di `docs/CLEANUP.md`
- [ ] `docs/ARCHITECTURE.md`, `QA_CHECKLIST.md`, dan ADR keputusan penting terisi
- [ ] CSP tanpa `unsafe-inline`

**Ditunda (lihat Fase 10):** build rilis, konfigurasi server, backup otomatis, latihan migrasi data, runbook deploy.

---

## 12. Keputusan dan Pertanyaan Terbuka (agent: isi jawaban di sini)

| # | Pertanyaan | Jawaban |
|---|---|---|
| Q1 | [Ditunda] Nama domain App A final? (`administators` salah eja; ganti atau tetap?) | |
| Q2 | Strategi riwayat Git (0.2b): repo baru bersih atau `filter-repo`? | |
| Q3 | Format hash password lama yang sebenarnya (MD5/SHA-1/SHA-256/plain/bergaram)? | |
| Q4 | Bagaimana App B login saat ini (endpoint apa)? | |
| Q5 | Fitur trending/Gemini: dipakai atau dihapus? | |
| Q6 | `website.php` dan `pengaturan.php`: dipakai? | |
| Q7 | Pendaftaran admin publik: tetap diperlukan? | |
| Q8 | Matriks role (Lampiran B): disetujui atasan? Unit kerja apa saja? | |
| Q9 | Opsi A (perbaiki) atau B (framework)? | |
| Q10 | [Ditunda] Versi PHP-FPM dan panel di server (CloudPanel?), user aplikasi, path `htdocs`? | |
| Q11 | Akun admin lama: semua dipaksa ganti password pada rilis pertama? Role awalnya apa? | |
| Q12 | Sumber daftar akun pegawai (CSV dari kepegawaian?) dan siapa yang mengimpor? | |
| Q13 | [Ditunda] App A dan App B satu VPS: pakai vhost internal `127.0.0.1` untuk API (10.12)? | |
| Q14 | [Ditunda] Tujuan salinan backup di luar server (rclone, scp, drive instansi)? | |

## 13. Log Agent

```
[2026-10-09] [0.1] chore(baseline): tag baseline-sebelum-refactor, rename branch dipuy ke main, checkout fix/fase-0-fondasi | git status bersih, branch kerja siap | -
[2026-10-09] [0.2] chore(repo): remove production sql dumps from git, add .editorconfig and .gitattributes, update .gitignore | dump dipindah ke ../private, git ls-files bersih | -
[2026-10-09] [0.3] feat(core): initialize composer, setup psr-4 autoloading, replace manual PHPMailer | composer install sukses, folder PHPMailer dihapus, autoload teruji | -
[2026-10-09] [0.4] feat(config): rewrite .env.example, add boot validation for required env variables, unify db name | boot validation teruji, .env.example bersih | -
[2026-10-09] [0.5] feat(xampp): setup docs/xampp configs, storage/logs, storage/uploads, execute create-db.sql | databases bps_solsel_dev & bps_solsel_test created, bps_app privileges verified | -
[2026-10-09] [0.6] feat(db): remove default admin seed from schema.sql, create dev_seed.sql, import to dev/test databases | dev_seed loaded (8 layanan, 7 kategori, 2 admin dev), tests pass | -
[2026-10-09] [0.7] docs(baseline): record baseline smoke test status across 27 endpoints in docs/BASELINE.md | smoke test script ran, all endpoints tested unauth & auth, findings logged | -
[2026-10-09] [0.8] docs(agents): update AGENTS.md aligned with todo.md, clarify XAMPP local stack, fix workflow paths | AGENTS.md updated, rule precedence stated, workflow table verified | -
[2026-10-09] [0.9] docs(server): create SERVER_NOTES.md living document for server reconciliation | assumptions, apache quirks, windows behaviors, and migration checklist documented | -
[2026-10-09] [0.10] docs(adr): record ADR 0001 local xampp focus and server deferred, create template and index | docs/adr/ initialized with template, ADR-0001, and README index | -
```

---

# LAMPIRAN

## A. Struktur Target

```
C:\xampp\htdocs\adminDashboard\
  AGENTS.md  todo.md  composer.json  phpunit.xml  .env  .env.example
  .agents\                 <- rules, workflows, skills, agents (ECC)
  public\                  <- DocumentRoot vhost admin.test
    index.php              <- satu-satunya pintu masuk
    .htaccess              <- front controller + blokir (Lampiran E4)
    assets\                <- css, js, logo
    uploads\               <- unggahan pengguna (+ .htaccess tanpa eksekusi PHP)
  src\
    Http\  Auth\  Models\  Services\  Support\
    views\
  routes.php
  config\
  storage\{logs,uploads}   <- log aplikasi dan log Apache vhost
  database\{migrations,seeds}
  scripts\                 <- migrate.php, db-reset-dev.php, test-db-reset.php
  tests\{Unit,Integration}  + tes Vitest (HTTP)
  docs\                    <- AUDIT, FUNGSI_MATRIX, API_CONTRACT, SERVER_NOTES, adr\, xampp\
```

## B. Matriks Role (SEMENTARA — menunggu persetujuan atasan, Q8)

| Aksi | user | leader | admin |
|---|:--:|:--:|:--:|
| Masuk dashboard | ✗ | ✓ | ✓ |
| Lihat layanan | — | ✓ (semua) | ✓ |
| Tambah/ubah layanan unit sendiri | ✗ | ✓ → `pending` | ✓ → langsung |
| Ubah layanan unit lain | ✗ | ✗ | ✓ |
| Setujui/tolak `pending` | ✗ | ✗ | ✓ |
| Kelola kategori | ✗ | ✗ | ✓ |
| Kelola pengguna dan role | ✗ | ✗ | ✓ |
| Pengaturan dan riwayat audit | ✗ | ✗ | ✓ |
| Preferensi, bookmark, statistik **milik sendiri** (`/api/v1/me/*`) | ✓ | ✓ | ✓ |

`user` = akun pegawai yang hanya memakai portal (App B) dan endpoint `/api/v1/me/*`; tidak masuk dashboard.

## C. Format Respons API v1

```json
// sukses
{ "data": { ... }, "error": null }

// gagal
{ "data": null, "error": { "code": "unauthorized", "message": "Sesi tidak valid." } }
```

Kode: `400 bad_request`, `401 unauthorized`, `403 forbidden`, `404 not_found`, `405 method_not_allowed`, `422 validation_failed`, `429 too_many_requests`, `500 server_error`.
Alias lama mempertahankan **bentuk JSON lama**.

## D. Endpoint untuk App B

| Metode | Path | Header | Isi / hasil |
|---|---|---|---|
| GET | `/api/v1/layanan` | `X-API-KEY` | Layanan `published`, aktif; field lama + `aliases`, `updated_at` |
| GET | `/api/v1/kategori` | `X-API-KEY` | Kategori aktif |
| POST | `/api/v1/auth/login` | `X-API-KEY` | Body `{username, password}` (password mentah). 200 `{token, expires_at, user:{id, display_name, role, unit_id}}`; 401 generik; 429 |
| GET | `/api/v1/auth/session` | `X-API-KEY`, `X-Session` | 200 data pengguna / 401 |
| POST | `/api/v1/auth/logout` | `X-API-KEY`, `X-Session` | 204 |
| GET | `/api/v1/settings/public` | `X-API-KEY` | Kontak bantuan (whitelist) |
| GET / PUT | `/api/v1/me/preferences` | `X-API-KEY`, `X-Session` | Pin, riwayat pencarian (maks 8), tema |
| GET / POST / PUT / DELETE | `/api/v1/me/bookmarks[/{id}]` | `X-API-KEY`, `X-Session` | Bookmark pribadi (maks 100) |
| POST | `/api/v1/me/clicks` | `X-API-KEY`, `X-Session` | Catat klik `{layanan_id}` |
| GET | `/api/v1/me/stats` | `X-API-KEY`, `X-Session` | Layanan teratas, streak, lencana |
| GET | `/api/v1/health` | — | `{"data":{"status":"ok"}}` atau 503, tanpa detail |

## E. Konfigurasi XAMPP (Apache + MariaDB)

**E1. Modul dan vhost (satu kali) [MANUAL]**

1. Di `C:\xampp\apache\conf\httpd.conf` pastikan baris berikut **tidak** diawali `#`:
   - `LoadModule rewrite_module modules/mod_rewrite.so`
   - `LoadModule headers_module modules/mod_headers.so`
   - `Include conf/extra/httpd-vhosts.conf`
2. Di `C:\xampp\apache\conf\extra\httpd-vhosts.conf` tambahkan **dua** vhost. Yang pertama menjaga `http://localhost` tetap normal untuk proyek XAMPP lain:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs"
</VirtualHost>

<VirtualHost *:80>
    ServerName admin.test
    DocumentRoot "C:/xampp/htdocs/adminDashboard/public"
    <Directory "C:/xampp/htdocs/adminDashboard/public">
        AllowOverride All
        Require all granted
        Options -Indexes -MultiViews
    </Directory>
    ErrorLog  "C:/xampp/htdocs/adminDashboard/storage/logs/apache-error.log"
    CustomLog "C:/xampp/htdocs/adminDashboard/storage/logs/apache-access.log" combined
</VirtualHost>
```

3. Tambahkan ke `C:\Windows\System32\drivers\etc\hosts` (buka editor sebagai Administrator): `127.0.0.1  admin.test`
4. Restart Apache dari XAMPP Control Panel. Cek sintaks: `C:\xampp\apache\bin\httpd.exe -t`.

Keuntungan vhost: `DocumentRoot` hanya `public/`, sehingga `config/`, `.env`, `database/`, dan `src/` **tidak bisa dijangkau lewat web**.

**E2. PHP (`C:\xampp\php\php.ini`)**

- Aktifkan ekstensi: `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `curl`, `openssl` (hapus `;` di depannya).
- Pengaturan dev: `error_reporting=E_ALL`, `date.timezone=Asia/Jakarta`, `upload_max_filesize=6M`, `post_max_size=8M`. `display_errors=On` hanya untuk dev; handler error aplikasi (4.2) tetap mengatur tampilan.
- Tambahkan `C:\xampp\php` ke **PATH** Windows lalu cek `php -v` (harus 8.1 atau lebih baru) di PowerShell baru.
- Install **Composer** (Composer-Setup.exe) dan cek `composer -V`.

**E3. Database dan user (`docs/xampp/create-db.sql`)**

Jalankan lewat phpMyAdmin (tab SQL) atau `C:\xampp\mysql\bin\mysql.exe -u root`:

```sql
CREATE DATABASE bps_solsel_dev  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE bps_solsel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'bps_app'@'localhost'      IDENTIFIED BY 'change_me_dev';
CREATE USER 'bps_migrator'@'localhost' IDENTIFIED BY 'change_me_migrator_dev';

GRANT SELECT, INSERT, UPDATE, DELETE ON bps_solsel_dev.*  TO 'bps_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON bps_solsel_test.* TO 'bps_app'@'localhost';
GRANT ALL PRIVILEGES ON bps_solsel_dev.*  TO 'bps_migrator'@'localhost';
GRANT ALL PRIVILEGES ON bps_solsel_test.* TO 'bps_migrator'@'localhost';
FLUSH PRIVILEGES;
```

`root` XAMPP bawaan tanpa password; **aplikasi tidak boleh memakai `root`**. Gunakan `bps_app` (CRUD) untuk aplikasi dan `bps_migrator` hanya untuk `scripts/migrate.php`.

**E4. Berkas `.htaccess`**

*`public/.htaccess` — versi transisi (Fase 0–3, halaman lama masih berupa `.php`):*

```apache
Options -Indexes
<FilesMatch "^\.|\.(sql|log|bak|old|ini|md|lock|sh)$">
    Require all denied
</FilesMatch>
```

*`public/.htaccess` — versi ketat (Fase 4.7, setelah front controller):*

```apache
Options -Indexes -MultiViews
RewriteEngine On

# bukan berkas atau folder nyata -> front controller
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]

# blokir berkas tersembunyi dan ekstensi sensitif
<FilesMatch "^\.|\.(sql|log|bak|old|ini|md|lock|sh|env)$">
    Require all denied
</FilesMatch>

# hanya index.php yang boleh dieksekusi
<FilesMatch "\.(php|phtml|phar)$">
    Require all denied
</FilesMatch>
<Files "index.php">
    Require all granted
</Files>
```

*`public/uploads/.htaccess` — tanpa eksekusi PHP:*

```apache
# XAMPP memakai mod_php; jika PHP berjalan sebagai FastCGI, hapus baris php_flag ini
php_flag engine off
Options -Indexes -ExecCGI
<FilesMatch "\.(php|phtml|phar|php[0-9])$">
    Require all denied
</FilesMatch>
```

*`.htaccess` di root repo — hanya untuk fallback subfolder (tidak disarankan; wajib diuji manual):*

```apache
Options -Indexes
RewriteEngine On
RewriteRule ^(config|src|database|scripts|storage|tests|vendor|docs|\.agents)(/|$) - [F]
RewriteRule (^|/)\.(env|git) - [F]
RewriteRule ^$ public/ [L]
RewriteRule ^(?!public/)(.*)$ public/$1 [L]
```

Untuk fallback ini set `APP_BASE_PATH=/adminDashboard` dan `APP_URL=http://localhost/adminDashboard`.

**E5. Mailpit (email uji)**

Unduh `mailpit.exe` untuk Windows, jalankan di PowerShell. SMTP ada di `127.0.0.1:1025` dan antarmuka di `http://localhost:8025`. Isi `.env`: `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`. Email tidak keluar ke internet.

## F. Cuplikan Acuan

**Sesi (bootstrap):**

```php
// Cookie Secure hanya jika aplikasi memang berjalan di HTTPS (lokal http -> false)
$secure = str_starts_with($_ENV['APP_URL'] ?? '', 'https://');

ini_set('session.use_strict_mode', '1');   // tolak ID sesi buatan penyerang
ini_set('session.use_only_cookies', '1');
session_name('admin_sid');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'secure'   => $secure,
    'httponly' => true,    // JavaScript tidak bisa membaca cookie
    'samesite' => 'Lax',
]);
session_start();
// setelah login sukses: session_regenerate_id(true);
```

**Password (upgrade-on-login):**

```php
public function verify(string $input, array $user): bool
{
    $stored = $user['password'];

    if (password_verify($input, $stored)) {            // sudah format modern
        if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
            $this->save((int)$user['id'], $input);
        }
        return true;
    }

    // LEGACY: sesuaikan dengan hasil audit 1.3 (md5/sha1/sha256/plain). Hapus setelah semua migrasi.
    if (hash_equals($stored, md5($input))) {
        $this->save((int)$user['id'], $input);         // ganti ke bcrypt diam-diam
        return true;
    }
    return false;
}

private function save(int $id, string $plain): void
{
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $this->pdo->prepare('UPDATE admin SET password = ? WHERE id = ?')->execute([$hash, $id]);
}
```

## G. Perintah Umum (PowerShell)

```powershell
# PHP dan Composer
php -v
composer install
composer check            # lint + analisis statis + phpunit
composer test             # phpunit saja
npx vitest run            # tes HTTP black-box (BASE_URL=http://admin.test)

# Database (memakai user migrator)
php scripts\migrate.php --status
php scripts\migrate.php --up
php scripts\db-reset-dev.php        # hanya untuk database *_dev

# Apache dan log
C:\xampp\apache\bin\httpd.exe -t
Get-Content storage\logs\app.log -Tail 50 -Wait
Get-Content storage\logs\apache-error.log -Tail 50
```

## H. Acuan Deploy (Fase 10 — DITUNDA, draf)

**H1. Struktur di server**

```
/home/<user-app>/app/
  releases/20261015120000/     <- tiap rilis (hasil ekstrak arsip)
  current -> releases/...      <- symlink rilis aktif; document root = current/public
  shared/
    .env                       <- mode 600, tidak ikut rilis
    storage/                   <- log; ditulis aplikasi
    uploads/                   <- unggahan pengguna
  deploy/                      <- skrip (salinan dari repo)
  backups/{db,uploads}/
```

**H2. `deploy/deploy.sh` (kerangka)**

```bash
#!/usr/bin/env bash
# Pemakaian: deploy.sh /path/app-1.0.0.tar.gz
set -euo pipefail
APP_DIR=/home/USER/app            # sesuaikan
PHP_FPM=php8.3-fpm                # sesuaikan (Q10)
ARCHIVE="$1"
TS=$(date +%Y%m%d%H%M%S)
NEW="$APP_DIR/releases/$TS"

mkdir -p "$NEW" "$APP_DIR/shared/storage" "$APP_DIR/shared/uploads"
tar -xzf "$ARCHIVE" -C "$NEW"

ln -sfn "$APP_DIR/shared/.env"     "$NEW/.env"
rm -rf "$NEW/storage" "$NEW/public/uploads"
ln -sfn "$APP_DIR/shared/storage"  "$NEW/storage"
ln -sfn "$APP_DIR/shared/uploads"  "$NEW/public/uploads"

"$APP_DIR/deploy/backup-db.sh"                       # backup SEBELUM migrasi
php "$NEW/scripts/migrate.php" --up

PREV=$(readlink -f "$APP_DIR/current" || true)
ln -sfn "$NEW" "$APP_DIR/current"
sudo systemctl reload "$PHP_FPM"

if ! "$APP_DIR/deploy/healthcheck.sh"; then
  echo "Health check GAGAL, rollback ke rilis sebelumnya"
  if [ -n "$PREV" ]; then
    ln -sfn "$PREV" "$APP_DIR/current"
    sudo systemctl reload "$PHP_FPM"
  fi
  exit 1
fi

# simpan 5 rilis terakhir
ls -1dt "$APP_DIR"/releases/* | tail -n +6 | xargs -r rm -rf
echo "Deploy $TS selesai"
```

**H3. `deploy/rollback.sh`**

```bash
#!/usr/bin/env bash
set -euo pipefail
APP_DIR=/home/USER/app
PHP_FPM=php8.3-fpm
CUR=$(readlink -f "$APP_DIR/current")
PREV=$(ls -1dt "$APP_DIR"/releases/* | grep -vx "$CUR" | head -n1 || true)
[ -n "$PREV" ] || { echo "Tidak ada rilis sebelumnya"; exit 1; }
ln -sfn "$PREV" "$APP_DIR/current"
sudo systemctl reload "$PHP_FPM"
echo "Kembali ke $PREV. Migrasi DB TIDAK dibalik otomatis."
```

**H4. `deploy/healthcheck.sh`**

```bash
#!/usr/bin/env bash
URL="${HEALTH_URL:-https://DOMAIN/api/v1/health}"
curl -fsS --max-time 5 "$URL" | grep -q '"status":"ok"'
```

**H5. `deploy/backup-db.sh`**

```bash
#!/usr/bin/env bash
set -euo pipefail
DB_NAME=bps_solsel                # sesuaikan
OUT=/home/USER/app/backups/db
mkdir -p "$OUT"
F="$OUT/${DB_NAME}_$(date +%Y%m%d_%H%M%S).sql.gz"
mysqldump --defaults-extra-file=/home/USER/.my.cnf \
  --single-transaction --routines --triggers \
  --default-character-set=utf8mb4 "$DB_NAME" | gzip > "$F"
chmod 600 "$F"
find "$OUT" -name '*.sql.gz' -mtime +14 -delete
echo "Backup: $F"
```

**H6. `deploy/nginx.production.conf.example`**

Di CloudPanel, tempel bagian `server` melalui **Vhost editor** dan pertahankan blok `fastcgi` bawaan panel; letakkan `limit_req_zone` di berkas terpisah yang dimuat di konteks `http`.

```nginx
limit_req_zone $binary_remote_addr zone=admin_login:10m rate=5r/m;
limit_req_status 429;

server {
  listen 80;
  server_name DOMAIN;
  return 301 https://$host$request_uri;
}

server {
  listen 443 ssl;
  http2 on;
  server_name DOMAIN;
  root /home/USER/app/current/public;
  index index.php;
  server_tokens off;
  client_max_body_size 6m;

  # sertifikat: dikelola panel/Certbot
  # ssl_certificate ...; ssl_certificate_key ...;

  # HSTS di Nginx; header lain diset oleh aplikasi (6.11)
  add_header Strict-Transport-Security "max-age=31536000" always;

  error_page 503 /maintenance.html;

  location ~ /\.(?!well-known) { deny all; }
  location ~* \.(sql|log|bak|old|ini|md|lock|sh|env)$ { deny all; }
  location ^~ /uploads/ { location ~ \.php$ { return 403; } }

  # batasi percobaan login (GET ikut terhitung; burst cukup longgar)
  location = /login {
    limit_req zone=admin_login burst=3 nodelay;
    try_files $uri /index.php?$query_string;
  }
  location = /api/v1/auth/login {
    limit_req zone=admin_login burst=3 nodelay;
    try_files $uri /index.php?$query_string;
  }

  location / { try_files $uri /index.php?$query_string; }

  location = /index.php {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;   # sesuaikan (Q10)
  }

  location ~ \.php$ { return 404; }
}
```

**H7. `deploy/php.production.ini.example`**

```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /home/USER/app/shared/storage/logs/php-error.log
upload_max_filesize = 6M
post_max_size = 8M
session.use_strict_mode = 1
session.cookie_httponly = 1
session.cookie_samesite = Lax
session.cookie_secure = 1
opcache.enable = 1
open_basedir = /home/USER/app:/tmp
; periksa dulu bahwa aplikasi tidak memakai fungsi ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,show_source
```

**H8. `crontab.example` dan `logrotate.example`**

```
# crontab -e (user aplikasi)
MAILTO=email-anda@contoh.go.id
0 * * * *   /usr/bin/php /home/USER/app/current/scripts/cleanup.php >> /home/USER/app/shared/storage/logs/cron.log 2>&1
15 2 * * *  /home/USER/app/deploy/backup-db.sh
30 2 * * 0  tar -czf /home/USER/app/backups/uploads/uploads_$(date +\%Y\%m\%d).tar.gz -C /home/USER/app/shared uploads
```

```
# /etc/logrotate.d/app-a
/home/USER/app/shared/storage/logs/*.log {
  weekly
  rotate 8
  compress
  missingok
  notifempty
  copytruncate
}
```

## I. Konfigurasi Nginx (DITUNDA — draf dari rencana awal)

Tidak dipakai di lokal XAMPP. Disimpan untuk rekonsiliasi server nanti. Padanan Apache ada di Lampiran E4.

**Versi ketat (hanya `index.php` yang dieksekusi):**

```nginx
server {
  listen 80;
  server_name localhost;
  root /var/www/app/public;
  index index.php;
  server_tokens off;
  client_max_body_size 6m;

  location ~ /\.(?!well-known) { deny all; }
  location ~* \.(sql|log|bak|old|ini|md|lock|sh|env)$ { deny all; }
  location ^~ /uploads/ { location ~ \.php$ { return 403; } }

  location / { try_files $uri /index.php?$query_string; }

  location = /index.php {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass php:9000;
  }

  location ~ \.php$ { return 404; }
}
```