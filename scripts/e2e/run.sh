#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."

if [ ! -d node_modules/@playwright/test ]; then
  echo "Playwright belum terpasang. Jalankan: bash scripts/e2e/install-playwright.sh" >&2
  exit 2
fi

exec npx playwright test "$@"
