# Backup / Restore Drill

A backup is not considered proven until it has been restored into an isolated target and the application can read it.

## Safety rules

- never restore into production;
- target database name must be explicit, e.g. `internship_restore_drill`;
- never reuse the production database URL for the drill target;
- retain evidence of backup timestamp, checksum/size where available, restore duration and verification outcome.

## Helper

SQLite:

```bash
DRILL_DB_CONNECTION=sqlite \
DRILL_SOURCE_SQLITE=database/database.sqlite \
bash scripts/release/backup-restore-drill.sh
```

MySQL dry-run:

```bash
DRILL_DB_CONNECTION=mysql \
DRILL_SOURCE_DB=internship \
DRILL_DB_NAME=internship_restore_drill \
bash scripts/release/backup-restore-drill.sh
```

The MySQL helper prints commands by default. Execution requires `EXECUTE_MYSQL_DRILL=yes` after reviewing the target.

## Application verification against restored DB

Point a temporary staging/test `.env` at the drill database, then run:

```bash
php artisan migrate:status
php artisan performance:check
php artisan release:check
php artisan test --filter=HealthCheckTest
```

Manually verify representative counts and a read-only application record. Never seed or mutate the restored evidence before counts are checked.
