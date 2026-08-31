#!/usr/bin/env bash
set -euo pipefail
umask 077

: "${SQLITE_DATABASE:?SQLITE_DATABASE is required}"
BACKUP_DIR="${BACKUP_DIR:-./storage/app/backups}"
command -v sqlite3 >/dev/null || { echo "sqlite3 CLI is required" >&2; exit 2; }
[ -f "$SQLITE_DATABASE" ] || { echo "SQLite database not found" >&2; exit 2; }

mkdir -p "$BACKUP_DIR"
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
output="$BACKUP_DIR/sqlite_${stamp}.sqlite"
sqlite3 "$SQLITE_DATABASE" ".backup '$output'"
sha256sum "$output" > "$output.sha256"
printf 'Backup written: %s\n' "$output"
