#!/usr/bin/env bash
set -euo pipefail

: "${DB_HOST:?DB_HOST is required}"
: "${DB_USERNAME:?DB_USERNAME is required}"
: "${DB_PASSWORD:?DB_PASSWORD is required}"
: "${RESTORE_DB:?RESTORE_DB is required}"
: "${BACKUP_FILE:?BACKUP_FILE is required}"
DB_PORT="${DB_PORT:-3306}"

case "$RESTORE_DB" in
  *restore*drill*|*restore_drill*|*restore-drill*) ;;
  *)
    echo "REFUSED: RESTORE_DB must clearly contain restore_drill." >&2
    exit 2
    ;;
esac

[ -f "$BACKUP_FILE" ] || { echo "Backup not found: $BACKUP_FILE" >&2; exit 2; }

export MYSQL_PWD="$DB_PASSWORD"
table_count="$(mysql --batch --skip-column-names --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${RESTORE_DB}';")"

if [ "$table_count" != "0" ]; then
  echo "REFUSED: restore drill database is not empty (${table_count} tables)." >&2
  unset MYSQL_PWD
  exit 2
fi

if [[ "$BACKUP_FILE" == *.gz ]]; then
  gzip -dc "$BACKUP_FILE" | mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" "$RESTORE_DB"
else
  mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" "$RESTORE_DB" < "$BACKUP_FILE"
fi

restored_tables="$(mysql --batch --skip-column-names --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${RESTORE_DB}';")"
unset MYSQL_PWD

printf 'Restore drill completed. Restored tables: %s\n' "$restored_tables"
