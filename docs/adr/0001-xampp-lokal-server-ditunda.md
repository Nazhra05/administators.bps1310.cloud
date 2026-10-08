# ADR-0001: Pengembangan Berfokus pada Lingkungan Lokal XAMPP dan Penundaan Rekonsiliasi Server

**Tanggal**: 2026-10-09  
**Status**: accepted  
**Pengambil Keputusan**: Developer, Antigravity Agent  

## Konteks

Aplikasi Dashboard Admin BPS Solok Selatan (App A) sebelumnya pernah berjalan di lingkungan server hosting publik, namun memiliki sejumlah kerentanan keamanan, dependensi manual, dan ketiadaan otomasi pengujian lokal. Di saat yang sama, pengembang bekerja di lingkungan Windows dengan XAMPP (Apache 2.4, MariaDB 10.4, PHP 8.3). Memaksakan sinkronisasi atau penerapan deploy langsung ke server VPS/Linux (Docker, Nginx, CI/CD) sejak awal refaktor berisiko tinggi merusak sistem yang sedang berjalan, memperlambat iterasi perbaikan kode, dan mengaburkan prioritas keamanan dasar aplikasi (App A) serta kestabilan kontrak API dengan App B (`sipasti.bps1310.cloud`).

## Keputusan

Seluruh pengembangan, perbaikan keamanan, refaktorisasi arsitektur, penulisan tes otomatis, dan pembersihan basis data **difokuskan 100% pada lingkungan lokal XAMPP** (Virtual Host `http://admin.test/`, database `bps_solsel_dev` dan `bps_solsel_test`). Semua tugas konfigurasi server produksi, Docker, Nginx, VPS, dan remote CI/CD resmi **ditunda (DEFERRED)** hingga seluruh Fase 0–9 selesai (didokumentasikan sebagai draf di Fase 10 dan Lampiran H–I pada `todo.md`). Setiap perbedaan platform Windows/Apache dicatat secara berkala ke `docs/SERVER_NOTES.md`.

## Alternatif yang Dipertimbangkan

### Alternatif 1: Melakukan refaktor langsung di server VPS staging/produksi
- **Kelebihan**: Menghindari disparitas lingkungan antara Windows dan Linux.
- **Kekurangan**: Risiko downtime data produksi, feedback loop lambat saat debugging, dan rawan kebocoran kredensial bila konfigurasi belum tuntas diuji.
- **Alasan Ditolak**: Tidak aman dan melanggar prinsip kehati-hatian data instansi.

### Alternatif 2: Menggunakan Docker container di mesin lokal pengembang
- **Kelebihan**: Meniru persis stack Linux yang akan dipakai di server produksi.
- **Kekurangan**: Memerlukan instalasi Docker Desktop di Windows yang berat resource, serta menambah lapisan kerumitan saat pengembang sudah memiliki instalasi XAMPP yang siap pakai.
- **Alasan Ditolak**: XAMPP sudah terpasang dan berfungsi optimal di mesin pengembang (prinsip KISS & YAGNI).

## Konsekuensi

### Positif
- Iterasi refaktor, pemindaian keamanan, dan eksekusi tes otomatis (Vitest, PHPUnit) dapat berjalan instan tanpa latency jaringan.
- Database lokal dev/test terisolasi dari database produksi, menjamin keamanan data statistik BPS.
- Lingkungan stabil untuk menstandarisasi kontrak API dengan App B sebelum rilis.

### Negatif
- Perlu rekonsiliasi konfigurasi server di kemudian hari (Apache mod_rewrite ke Nginx server block, path separator Windows vs Linux, dan case-sensitivity ext4).

### Risiko & Mitigasi
- **Risiko Disparitas OS:** Path Windows `\` atau case-sensitivity huruf besar/kecil tidak sengaja lolos ke produksi.
  - **Mitigasi:** Standarisasi PSR-4, normalisasi separator dengan `/`, pengujian linting ketat, dan pencatatan berkala di `docs/SERVER_NOTES.md`.
