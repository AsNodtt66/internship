#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."

if [ "${APP_ENV:-}" != "testing" ]; then
  echo "ERROR: APP_ENV harus testing sebelum reset database E2E." >&2
  exit 2
fi

case "${DB_DATABASE:-}" in
  ""|*prod*|*production*)
    echo "ERROR: DB_DATABASE tidak aman untuk migrate:fresh: '${DB_DATABASE:-<empty>}'" >&2
    exit 2
    ;;
esac

php artisan migrate:fresh --force
php artisan db:seed --class=TestingSeeder --force
