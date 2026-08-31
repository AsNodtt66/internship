#!/usr/bin/env bash
set -euo pipefail

if [[ "${APP_ENV:-}" != "staging" ]]; then
  echo "Refusing to run: set APP_ENV=staging explicitly in the shell." >&2
  exit 2
fi

php artisan optimize:clear
php scripts/verify.php --full
php scripts/ui-audit.php
php scripts/performance-audit.php
php scripts/release/release-candidate-audit.php
php artisan migrate:status
php artisan performance:check
php artisan release:check --strict
php artisan route:list --except-vendor >/dev/null
CACHE_STORE=array php artisan schedule:list
php artisan test
php vendor/bin/pint --test

npm ci --no-audit --no-fund
npm run build

echo "Staging verification passed. Run dependency audits, k6 smoke, backup/restore drill and manual browser/usability gates before promotion."
