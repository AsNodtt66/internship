#!/usr/bin/env bash
set -euo pipefail

# Safety-first restore drill. Never targets production database names.
# Supports sqlite directly. MySQL commands are printed unless EXECUTE_MYSQL_DRILL=yes.

connection="${DRILL_DB_CONNECTION:-sqlite}"
timestamp="$(date +%Y%m%d%H%M%S)"
workdir="${DRILL_WORKDIR:-/tmp/internship-management-restore-drill-${timestamp}}"
mkdir -p "$workdir"

if [[ "$connection" == "sqlite" ]]; then
  source_db="${DRILL_SOURCE_SQLITE:-database/database.sqlite}"
  target_db="$workdir/simaset_restore_drill.sqlite"
  [[ -f "$source_db" ]] || { echo "Source SQLite database not found: $source_db" >&2; exit 2; }
  cp "$source_db" "$workdir/backup.sqlite"
  cp "$workdir/backup.sqlite" "$target_db"
  echo "SQLite drill copy created at: $target_db"
  echo "Verify with a temporary .env pointing DB_DATABASE to that file, then run: php artisan migrate:status && php artisan release:check"
  exit 0
fi

if [[ "$connection" == "mysql" ]]; then
  : "${DRILL_SOURCE_DB:?Set DRILL_SOURCE_DB}"
  : "${DRILL_DB_NAME:?Set DRILL_DB_NAME, e.g. internship_restore_drill}"
  [[ "$DRILL_DB_NAME" == *_restore_drill ]] || { echo "DRILL_DB_NAME must end with _restore_drill" >&2; exit 2; }
  [[ "$DRILL_DB_NAME" != "$DRILL_SOURCE_DB" ]] || { echo "Drill target must differ from source DB" >&2; exit 2; }

  cat <<EOF
Safe MySQL drill commands (review before execution):
  mysqldump --single-transaction --routines --triggers "$DRILL_SOURCE_DB" > "$workdir/backup.sql"
  mysql -e 'CREATE DATABASE `${DRILL_DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
  mysql "$DRILL_DB_NAME" < "$workdir/backup.sql"
EOF

  if [[ "${EXECUTE_MYSQL_DRILL:-no}" != "yes" ]]; then
    echo "Dry-run only. Set EXECUTE_MYSQL_DRILL=yes after reviewing target names."
    exit 0
  fi

  mysqldump --single-transaction --routines --triggers "$DRILL_SOURCE_DB" > "$workdir/backup.sql"
  mysql -e "CREATE DATABASE \`${DRILL_DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql "$DRILL_DB_NAME" < "$workdir/backup.sql"
  echo "Restore drill completed into $DRILL_DB_NAME. Do not point production traffic at this database."
  exit 0
fi

echo "Unsupported DRILL_DB_CONNECTION: $connection" >&2
exit 2
