# Kontrak Nyata REST API — App A (Dashboard Admin) ↔ App B (Portal Internal)

> **Dokumen Kontrak API Resmi (Fase 2 — Task 2.2)**
> Dokumen ini mencatat kontrak HTTP nyata antara **App A** (`adminDashboard` / `administators.bps1310.cloud`) dan **App B** (`sipasti.bps1310.cloud` / `landingpageintra`).
> **Aturan Utama:** Kontrak ini tidak boleh diputus tanpa penyediaan alias backward-compatibility.

---

## 1. Konvensi Umum

* **Base URL:**
  * Lokal XAMPP Subfolder (Aktif): `http://localhost/adminDashboard/public`
  * Lokal XAMPP VirtualHost: `http://admin.test`
  * Produksi: `https://administators.bps1310.cloud`
* **Format Data:** `Content-Type: application/json; charset=utf-8`
* **Header Autentikasi Wajib:**
  Setiap permintaan API dari App B (server-side) **wajib menyertakan header**:
  ```http
  X-API-KEY: <API_KEY_APP_B>
  ```
  *(Jika header tidak dikirim atau nilainya salah, server mengembalikan status `401 Unauthorized`).*

* **Struktur Pembungkus Respons JSON (Envelope):**
  Semua endpoint menggunakan format pembungkus standar:
  ```json
  {
    "status": true,
    "message": "Deskripsi hasil eksekusi",
    "data": null
  }
  ```

---

## 2. Ringkasan Endpoint

| Endpoint | Metode | Kegunaan di App B | Autentikasi |
|---|---|---|---|
| `/api/layanan.php` | `GET` | Mengambil seluruh katalog link layanan | `X-API-KEY` |
| `/api/layanan.php?id={id}` | `GET` | Mengambil detail 1 layanan berdasarkan ID | `X-API-KEY` |
| `/api/layanan.php` | `POST` | Menambah layanan baru | `X-API-KEY` |
| `/api/layanan.php?id={id}` | `PUT` | Memperbarui layanan | `X-API-KEY` |
| `/api/layanan.php?id={id}` | `DELETE` | Menghapus layanan | `X-API-KEY` |
| `/api/kategori.php` | `GET` | Mengambil daftar master kategori | `X-API-KEY` |
| `/api/website.php` | `GET` | Mengambil agregasi 7 kategori & link untuk beranda portal | `X-API-KEY` |
| `/api/auth/loginPortal.php` | `POST` | Autentikasi login pengguna dari App B | `X-API-KEY` |

---

## 3. Spesifikasi Lengkap per Endpoint

### 3.1 `GET /api/layanan.php` (Katalog Layanan)

Mengambil seluruh daftar layanan aktif yang diurutkan secara descending (`id_layanan DESC`).

* **Header:**
  * `X-API-KEY: <API_KEY_APP_B>` (Wajib)
* **Status Berhasil:** `200 OK`
* **Contoh Respons (Data Seed):**
```json
{
  "status": true,
  "message": "Data layanan berhasil diambil",
  "data": [
    {
      "id_layanan": 8,
      "id_kategori": 5,
      "akses": "Internal",
      "vpn": "No",
      "nama_layanan": "Manajemen Repositori Dokumen Kantor (DocHub)",
      "deskripsi_layanan": "Penyimpanan arsip digital surat keputusan, nota dinas, dan notulensi rapat kantor.",
      "url": "http://dochub.local/",
      "logo": "logo_bps.jpg",
      "nama_kategori": "TI"
    },
    {
      "id_layanan": 1,
      "id_kategori": 1,
      "akses": "Internal",
      "vpn": "Yes",
      "nama_layanan": "Sistem Monitoring Survei Harga Konsumen (Simonev SHK)",
      "deskripsi_layanan": "Pemantauan berkala pencacahan harga komoditas pasar tradisional di Solok Selatan.",
      "url": "https://shk.internal.bps1310.test/",
      "logo": "logo_bps.jpg",
      "nama_kategori": "Distribusi"
    }
  ]
}
```

---

### 3.2 `GET /api/layanan.php?id={id}` (Detail Layanan)

Mengambil detail satu layanan berdasarkan primary key.

* **Parameter URL:**
  * `id` *(integer, required)*: ID layanan (contoh: `1`).
* **Header:**
  * `X-API-KEY: <API_KEY_APP_B>` (Wajib)
* **Status Berhasil:** `200 OK`
* **Contoh Respons:**
```json
{
  "status": true,
  "message": "Data layanan berhasil diambil",
  "data": {
    "id_layanan": 1,
    "id_kategori": 1,
    "akses": "Internal",
    "vpn": "Yes",
    "nama_layanan": "Sistem Monitoring Survei Harga Konsumen (Simonev SHK)",
    "deskripsi_layanan": "Pemantauan berkala pencacahan harga komoditas pasar tradisional di Solok Selatan.",
    "url": "https://shk.internal.bps1310.test/",
    "logo": "logo_bps.jpg",
    "nama_kategori": "Distribusi"
  }
}
```
* **Status Error:**
  * `404 Not Found` jika ID tidak ditemukan:
    ```json
    { "status": false, "message": "Data layanan tidak ditemukan", "data": null }
    ```

---

### 3.3 `POST /api/layanan.php` (Tambah Layanan)

Membuat data layanan baru.

* **Header:**
  * `X-API-KEY: <API_KEY_APP_B>` (Wajib)
  * `Content-Type: application/json` atau `multipart/form-data`
* **Body Request (JSON):**
```json
{
  "nama_layanan": "Layanan Uji Baru",
  "id_kategori": 1,
  "akses": "Public",
  "vpn": "No",
  "url": "https://layanan-uji.bps.go.id",
  "deskripsi_layanan": "Deskripsi pengujian",
  "logo": "default_logo.png"
}
```
* **Status Berhasil:** `201 Created`
* **Status Error:**
  * `400 Bad Request`: Input validasi kosong / format URL salah / akses/vpn tidak valid.
  * `404 Not Found`: Kategori yang dituju (`id_kategori`) tidak ada.

---

### 3.4 `PUT /api/layanan.php?id={id}` (Update Layanan)

Memperbarui data layanan yang ada.

* **Parameter URL:** `id` *(integer, required)*
* **Header:**
  * `X-API-KEY: <API_KEY_APP_B>` (Wajib)
  * `Content-Type: application/json`
* **Status Berhasil:** `200 OK`

---

### 3.5 `DELETE /api/layanan.php?id={id}` (Hapus Layanan)

Menghapus data layanan dari database.

* **Parameter URL:** `id` *(integer, required)*
* **Header:** `X-API-KEY: <API_KEY_APP_B>` (Wajib)
* **Status Berhasil:** `200 OK`
```json
{
  "status": true,
  "message": "Layanan berhasil dihapus",
  "data": null
}
```

---

### 3.6 `GET /api/kategori.php` (Daftar Master Kategori)

Mengambil daftar kategori BPS terurut ascending (`id_kategori ASC`).

* **Header:**
  * `X-API-KEY: <API_KEY_APP_B>` (Wajib)
* **Status Berhasil:** `200 OK`
* **Contoh Respons (Data Seed):**
```json
{
  "status": true,
  "message": "Data kategori berhasil diambil",
  "data": [
    {
      "id_kategori": 1,
      "nama_kategori": "Distribusi",
      "deskripsi": "Layanan yang berkaitan dengan pelaporan perusahaan, harga komoditas, dan kegiatan sensus ekonomi."
    },
    {
      "id_kategori": 2,
      "nama_kategori": "Produksi",
      "deskripsi": "Layanan yang berkaitan dengan pendataan dan statistik sektor pertanian, perkebunan, dan industri."
    },
    {
      "id_kategori": 3,
      "nama_kategori": "Neraca",
      "deskripsi": "Layanan yang berkaitan dengan data PDRB, neraca wilayah, dan kompilasi data ekonomi daerah."
    },
    {
      "id_kategori": 4,
      "nama_kategori": "Sosial",
      "deskripsi": "Layanan yang berkaitan dengan kependudukan, kemiskinan, ketenagakerjaan, dan pembangunan manusia."
    },
    {
      "id_kategori": 5,
      "nama_kategori": "TI",
      "deskripsi": "Sistem teknologi informasi, integrasi aplikasi portal, pemetaan spasial, dan infrastruktur jaringan."
    },
    {
      "id_kategori": 6,
      "nama_kategori": "Umum",
      "deskripsi": "Layanan pendukung administrasi kepegawaian, surat menyurat, pengadaan, dan PPID."
    },
    {
      "id_kategori": 7,
      "nama_kategori": "Diseminasi",
      "deskripsi": "Layanan penyebaran publikasi, portal data terbuka, dan konsultasi statistik terpadu."
    }
  ]
}
```

---

### 3.7 `GET /api/website.php` (Agregasi Portal Beranda)

Endpoint utama yang dikonsumsi frontend portal App B (`sipasti.bps1310.cloud`) untuk menampilkan kartu kategori beserta daftar link aplikasinya.

* **Header:**
  * `X-API-KEY: <API_KEY_APP_B>` (Wajib)
* **Status Berhasil:** `200 OK`
* **Contoh Respons:**
```json
{
  "status": true,
  "message": "Data website berhasil diambil",
  "data": [
    {
      "title": "Distribusi",
      "category": "Distribusi",
      "description": "Perdagangan, harga, transportasi, pariwisata, dan distribusi barang/jasa.",
      "links": [
        {
          "label": "Sistem Monitoring Survei Harga Konsumen (Simonev SHK)",
          "href": "https://shk.internal.bps1310.test/",
          "logo": "logo_bps.jpg",
          "id_layanan": 1,
          "akses": "Internal",
          "vpn": "Yes"
        }
      ]
    },
    {
      "title": "Produksi",
      "category": "Produksi",
      "description": "Pertanian, industri, pertambangan, energi, konstruksi, peternakan, kehutanan, perikanan.",
      "links": [
        {
          "label": "Aplikasi Entri Data Statistik Pertanian Hortikultura (SPH)",
          "href": "https://pertanian.internal.bps1310.test/",
          "logo": "logo_bps.jpg",
          "id_layanan": 2,
          "akses": "Internal",
          "vpn": "Yes"
        }
      ]
    }
  ]
}
```

---

### 3.8 `POST /api/auth/loginPortal.php` (Autentikasi Portal App B)

Digunakan oleh App B saat pengguna masuk melalui portal internal BPS Solok Selatan.

* **Metode:** `POST`
* **Header:**
  * `X-API-KEY: <API_KEY_APP_B>` (Wajib)
  * `Content-Type: application/json`
* **Body Request:**
```json
{
  "identity": "admin",
  "password": "admin123"
}
```
*Catatan:* Parameter `identity` menerima `username` ataupun alamat `email`.

* **Status Berhasil (`200 OK`):**
```json
{
  "status": true,
  "message": "Login berhasil",
  "data": {
    "id_admin": 1,
    "username": "admin",
    "email": "admin@bps1310.cloud",
    "role": "ADMIN IT"
  }
}
```

* **Status Error:**
  * `400 Bad Request` (Identity atau password kosong / JSON tidak valid):
    ```json
    { "status": false, "message": "Identity (email/username) dan password wajib diisi", "data": null }
    ```
  * `401 Unauthorized` (Password salah):
    ```json
    { "status": false, "message": "Password salah", "data": null }
    ```
  * `403 Forbidden` (Akun belum disetujui / status pending):
    ```json
    { "status": false, "message": "Akun belum disetujui atau berstatus pending", "data": null }
    ```
  * `404 Not Found` (Pengguna tidak terdaftar):
    ```json
    { "status": false, "message": "Akun tidak ditemukan", "data": null }
    ```

---

## 4. Standar Kode Status Error & Penanganan

| HTTP Code | Arti | Skenario Pemunculan |
|---|---|---|
| `200 OK` | Sukses | Permintaan `GET`, `PUT`, `DELETE`, atau `POST` login berhasil |
| `201 Created` | Dibuat | Penambahan layanan baru berhasil |
| `400 Bad Request` | Payload Salah | JSON tidak valid, field wajib kosong, tipe data salah |
| `401 Unauthorized` | Belum Otentikasi | `X-API-KEY` tidak ada, `X-API-KEY` salah, atau password salah |
| `403 Forbidden` | Dilarang | Akun berstatus belum approved |
| `404 Not Found` | Tidak Ditemukan | ID layanan / kategori / akun tidak ditemukan di database |
| `405 Method Not Allowed`| Metode Ditolak | Memanggil endpoint dengan metode yang tidak didukung (misal: POST ke kategori) |
| `500 Server Error` | Gangguan Server | Kesalahan kueri SQL / PDO atau server API key belum terpasang |
