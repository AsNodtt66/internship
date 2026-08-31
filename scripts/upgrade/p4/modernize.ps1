$ErrorActionPreference = 'Stop'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..\..\..')).Path
Set-Location $Root

function Phase([string]$Text) { Write-Host "`n==> $Text" -ForegroundColor Cyan }
function Need([string]$Command) {
    if (-not (Get-Command $Command -ErrorAction SilentlyContinue)) { throw "$Command tidak ditemukan." }
}

Need php
Need composer
Need node
Need npm

php -r 'if (version_compare(PHP_VERSION, "8.4.0", "<")) { fwrite(STDERR, "PHP 8.4+ required. Current: ".PHP_VERSION.PHP_EOL); exit(1); }'
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
node -e 'const [maj,min]=process.versions.node.split(".").map(Number); if (!((maj===20&&min>=19)||(maj===22&&min>=12)||maj>22)) { console.error(`Node 20.19+ or 22.12+ required. Current: ${process.versions.node}`); process.exit(1) }'
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

if (Get-Command git -ErrorAction SilentlyContinue) {
    $dirty = git status --porcelain
    if ($dirty -and $env:P4_ALLOW_DIRTY -ne '1') { throw 'Working tree tidak bersih. Commit/stash perubahan terlebih dahulu.' }
}

Phase 'Pre-upgrade compatibility scan'
php scripts/upgrade/p4/compatibility-check.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Phase 'Backup manifests'
Copy-Item composer.json composer.pre-p4.json -Force
Copy-Item composer.lock composer.pre-p4.lock -Force
Copy-Item package.json package.pre-p4.json -Force
Copy-Item package-lock.json package.pre-p4.lock -Force

Phase 'Laravel 13 + PHP 8.4 baseline'
composer require 'php:~8.4' 'laravel/framework:~13.17' 'laravel/tinker:~3.0' 'barryvdh/laravel-dompdf:~3.1.2' --no-update
composer require --dev 'phpunit/phpunit:~12.5' 'laravel/pint:~1.27' --no-update
composer config minimum-stability stable
composer config prefer-stable true
composer update --with-all-dependencies
php artisan optimize:clear
php artisan --version
php artisan test
php vendor/bin/pint --test

Phase 'Vite 8 + Laravel Vite Plugin 3'
npm install --save-dev 'vite@^8.0.0' 'laravel-vite-plugin@^3.1' 'concurrently@^10.0.3'
npm run build
npm audit --audit-level=high

Phase 'Filament 5 / Livewire 4'
composer require 'filament/upgrade:~5.0' -W --dev
vendor/bin/filament-v5
composer require 'filament/filament:~5.0' -W --no-update
composer update --with-all-dependencies
try { composer remove filament/upgrade --dev --no-interaction } catch { Write-Warning $_ }
php artisan optimize:clear
php artisan filament:upgrade
php artisan test
php vendor/bin/pint --test
npm run build
composer audit --locked --no-interaction

Phase 'P4 post-upgrade verification'
php scripts/upgrade/p4/compatibility-check.php --expect-modern
php scripts/verify.php --full
Write-Host "`nP4 modernization selesai. Review git diff dan lakukan UAT /admin serta /peserta." -ForegroundColor Green
