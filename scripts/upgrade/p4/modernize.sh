#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT"

phase() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }
need() { command -v "$1" >/dev/null 2>&1 || { echo "ERROR: $1 tidak ditemukan." >&2; exit 1; }; }

need php
need composer
need node
need npm

php -r 'if (version_compare(PHP_VERSION, "8.4.0", "<")) { fwrite(STDERR, "PHP 8.4+ required for the P4 target. Current: ".PHP_VERSION.PHP_EOL); exit(1); }'
node -e 'const [maj,min]=process.versions.node.split(".").map(Number); if (!((maj===20&&min>=19)||(maj===22&&min>=12)||maj>22)) { console.error(`Node 20.19+ or 22.12+ required. Current: ${process.versions.node}`); process.exit(1) }'

if [[ -n "$(git status --porcelain 2>/dev/null || true)" && "${P4_ALLOW_DIRTY:-0}" != "1" ]]; then
  echo "ERROR: working tree tidak bersih. Commit/stash perubahan atau set P4_ALLOW_DIRTY=1 dengan sengaja." >&2
  exit 1
fi

phase "Pre-upgrade compatibility scan"
php scripts/upgrade/p4/compatibility-check.php

phase "Backup manifests"
cp composer.json composer.pre-p4.json
cp composer.lock composer.pre-p4.lock
cp package.json package.pre-p4.json
cp package-lock.json package.pre-p4.lock

phase "Laravel 13 + PHP 8.4 baseline"
composer require php:'^8.4' laravel/framework:'^13.17' laravel/tinker:'^3.0' barryvdh/laravel-dompdf:'^3.1.2' --no-update
composer require --dev phpunit/phpunit:'^12.5.12' laravel/pint:'^1.27' --no-update
composer config minimum-stability stable
composer config prefer-stable true
composer update --with-all-dependencies

php artisan optimize:clear
php artisan --version
php artisan route:list --except-vendor >/dev/null
CACHE_STORE=array php artisan schedule:list >/dev/null
php artisan test
php vendor/bin/pint --test

phase "Vite 8 + Laravel Vite Plugin 3"
npm install --save-dev vite@'^8.0.0' laravel-vite-plugin@'^3.1' concurrently@'^10.0.3'
npm run build
npm audit --audit-level=high || true

phase "Filament 5 / Livewire 4"
composer require filament/upgrade:'^5.0' -W --dev
vendor/bin/filament-v5
composer require filament/filament:'^5.0' -W --no-update
composer update --with-all-dependencies
composer remove filament/upgrade --dev --no-interaction || true

php artisan optimize:clear
php artisan filament:upgrade
php artisan test
php vendor/bin/pint --test
npm run build
composer audit --locked --no-interaction

phase "P4 post-upgrade verification"
php scripts/upgrade/p4/compatibility-check.php --expect-modern
php scripts/verify.php --full

echo
echo "P4 modernization selesai. Review git diff, commit lockfiles baru, lalu jalankan UAT browser pada /admin dan /peserta."
