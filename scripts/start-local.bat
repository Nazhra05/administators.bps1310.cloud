@echo off
title BPS Solok Selatan - Local Environment Starter
echo ========================================================
echo   BPS Solok Selatan Admin Dashboard - Local Environment
echo ========================================================
echo.

:: 1. Periksa apakah MySQL berjalan
echo [1/3] Memeriksa layanan MySQL (Port 3306)...
netstat -ano | findstr :3306 >nul
if %errorlevel% neq 0 (
    echo [INFO] Menjalankan MySQL dari XAMPP...
    start "" /b "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
    timeout /t 2 /nobreak >nul
) else (
    echo [OK] MySQL aktif dan siap.
)

:: 2. Periksa apakah Apache berjalan
echo [2/3] Memeriksa layanan Apache (Port 80)...
netstat -ano | findstr :80 >nul
if %errorlevel% neq 0 (
    echo [INFO] Menjalankan Apache dari XAMPP...
    start "" /b "C:\xampp\apache\bin\httpd.exe"
    timeout /t 2 /nobreak >nul
) else (
    echo [OK] Apache aktif dan siap.
)

:: 3. Buka halaman di browser default
echo [3/3] Membuka Dashboard di Browser...
start "" "http://localhost/adminDashboard/public/login.php"

echo.
echo ========================================================
echo   Server Lokal Siap Digunakan!
echo   - URL Login : http://localhost/adminDashboard/public/login.php
echo   - Username  : admin
echo   - Password  : admin123
echo ========================================================
echo.
pause
