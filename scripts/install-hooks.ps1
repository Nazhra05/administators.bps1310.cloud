# ==========================================================
# Install Git Hooks for Local Development (Task 8.4)
# ==========================================================
Write-Host "Mengonfigurasi Git Hooks lokal..." -ForegroundColor Cyan

# Set core.hooksPath ke folder .githooks
git config core.hooksPath .githooks

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Git core.hooksPath berhasil disetel ke '.githooks'." -ForegroundColor Green
} else {
    Write-Warning "Gagal mengatur core.hooksPath. Menyalin manual ke .git/hooks..."
    Copy-Item -Path ".githooks/pre-commit" -Destination ".git/hooks/pre-commit" -Force
    Write-Host "✓ File pre-commit berhasil disalin ke '.git/hooks/pre-commit'." -ForegroundColor Green
}

# Pastikan juga ada salinan di .git/hooks sebagai fallback
if (Test-Path ".git/hooks") {
    Copy-Item -Path ".githooks/pre-commit" -Destination ".git/hooks/pre-commit" -Force
}

Write-Host "Pemasangan Git Hooks selesai! Pre-commit aktif menjaga keamanan rahasia." -ForegroundColor Green
