#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."
VERSION="$(tr -d '[:space:]' < .playwright-version)"
BROWSERS=("$@")

npm install --no-save --package-lock=false "@playwright/test@${VERSION}"

if [ ${#BROWSERS[@]} -eq 0 ]; then
  npx playwright install --with-deps
else
  npx playwright install --with-deps "${BROWSERS[@]}"
fi
