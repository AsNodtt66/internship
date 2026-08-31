#!/usr/bin/env bash
set -euo pipefail
umask 077

: "${DB_HOST:?DB_HOST is required}"
: "${DB_DATABASE:?DB_DATABASE is required}"
: "${DB_USERNAME:?DB_USERNAME is required}"
: "${DB_PASSWORD:?DB_PASSWORD is required}"
DB_PORT="${DB_PORT:-3306}"
BACKUP_DIR="${BACKUP_DIR:-./storage/app/backups}"

mkdir -p "$BACKUP_DIR"
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
output="$BACKUP_DIR/${DB_DATABASE}_${stamp}.sql.gz"

export MYSQL_PWD="$DB_PASSWORD"
mysqldump \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USERNAME" \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --set-gtid-purged=OFF \
  "$DB_DATABASE" | gzip -9 > "$output"
unset MYSQL_PWD

sha256sum "$output" > "$output.sha256"
printf 'Backup written: %s\n' "$output"
