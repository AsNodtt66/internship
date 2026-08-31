$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..\..')

foreach ($cmd in @('php', 'composer', 'npm')) {
    if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
        throw "$cmd tidak ditemukan di PATH"
    }
}

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    Write-Host '[OK] .env dibuat dari .env.example'
}

composer install --no-interaction
php artisan key:generate --force

$dbLine = Select-String -Path '.env' -Pattern '^DB_CONNECTION=sqlite' -Quiet
if ($dbLine) {
    New-Item -ItemType Directory -Path 'database' -Force | Out-Null
    if (-not (Test-Path 'database/database.sqlite')) {
        New-Item -ItemType File -Path 'database/database.sqlite' | Out-Null
    }
}

php artisan migrate
php artisan db:seed
npm ci --no-audit --no-fund
npm run build

Write-Host ''
Write-Host '[OK] Setup dasar selesai.'
Write-Host 'Jalankan: composer dev'
Write-Host 'Peserta: http://127.0.0.1:8000/peserta'
Write-Host 'Admin:   http://127.0.0.1:8000/admin'
Write-Host 'Demo users tidak dibuat default. Lihat docs/QUICK-START.md.'
