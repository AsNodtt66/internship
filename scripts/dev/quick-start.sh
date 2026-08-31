#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."

command -v php >/dev/null || { echo "[ERROR] php tidak ditemukan"; exit 1; }
command -v composer >/dev/null || { echo "[ERROR] composer tidak ditemukan"; exit 1; }
command -v npm >/dev/null || { echo "[ERROR] npm tidak ditemukan"; exit 1; }

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo "[OK] .env dibuat dari .env.example"
fi

composer install --no-interaction
php artisan key:generate --force

if grep -Eq '^DB_CONNECTION=sqlite' .env; then
  mkdir -p database
  touch database/database.sqlite
fi

php artisan migrate
php artisan db:seed
npm ci --no-audit --no-fund
npm run build

cat <<'EOF'

[OK] Setup dasar selesai.
Jalankan aplikasi:
  composer dev

Panel peserta:
  http://127.0.0.1:8000/peserta

Panel admin:
  http://127.0.0.1:8000/admin

Demo users TIDAK dibuat secara default. Untuk local demo, isi:
  SEED_DEMO_USERS=true
  SEED_DEFAULT_PASSWORD=<minimal 12 karakter>
kemudian jalankan:
  php artisan db:seed
EOF
