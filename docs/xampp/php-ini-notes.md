# Catatan Konfigurasi PHP (C:\xampp\php\php.ini)

## 1. Ekstensi Wajib
Pastikan baris ekstensi berikut aktif (tidak diawali titik koma `;`):
```ini
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_mysql
```

## 2. Pengaturan Pengembangan Lokal (Dev)
```ini
error_reporting = E_ALL
display_errors = On
date.timezone = Asia/Jakarta
upload_max_filesize = 6M
post_max_size = 8M
```

## 3. Verifikasi di Terminal PowerShell
```powershell
php -v
php -m
```
Pastikan `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `curl`, dan `openssl` terdaftar dalam output.
