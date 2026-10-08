# BPS Solok Selatan - Local Environment Starter (PowerShell)
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "  BPS Solok Selatan Admin Dashboard - Local Environment" -ForegroundColor Cyan
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Periksa MySQL
Write-Host "[1/3] Memeriksa layanan MySQL (Port 3306)..." -NoNewline
$mysqlPort = Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue
if (-not $mysqlPort) {
    Write-Host " [MEMULAI]" -ForegroundColor Yellow
    Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini", "--standalone" -WindowStyle Hidden
    Start-Sleep -Seconds 2
} else {
    Write-Host " [AKTIF]" -ForegroundColor Green
}

# 2. Periksa Apache
Write-Host "[2/3] Memeriksa layanan Apache (Port 80)..." -NoNewline
$apachePort = Get-NetTCPConnection -LocalPort 80 -ErrorAction SilentlyContinue
if (-not $apachePort) {
    Write-Host " [MEMULAI]" -ForegroundColor Yellow
    Start-Process -FilePath "C:\xampp\apache\bin\httpd.exe" -WindowStyle Hidden
    Start-Sleep -Seconds 2
} else {
    Write-Host " [AKTIF]" -ForegroundColor Green
}

# 3. Buka browser
Write-Host "[3/3] Membuka URL Dashboard di browser default..." -ForegroundColor Cyan
Start-Process "http://localhost/adminDashboard/public/login.php"

Write-Host ""
Write-Host "========================================================" -ForegroundColor Green
Write-Host "  Server Lokal Siap Digunakan!" -ForegroundColor Green
Write-Host "  - URL Login : http://localhost/adminDashboard/public/login.php"
Write-Host "  - Username  : admin"
Write-Host "  - Password  : admin123"
Write-Host "========================================================" -ForegroundColor Green
