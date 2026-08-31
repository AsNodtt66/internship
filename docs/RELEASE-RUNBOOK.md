# Release Runbook

This is the canonical RC → production procedure.

## Preconditions

- immutable commit/tag selected;
- P4 actual dependency modernization completed and lockfiles committed;
- normal CI green;
- `release:check --strict` green on staging;
- backup/restore drill green;
- k6 smoke green;
- browser/usability critical issues closed;
- rollback owner identified.

## Build

```bash
composer install --no-dev --classmap-authoritative --no-interaction --prefer-dist
npm ci --no-audit --no-fund
npm run build
php artisan optimize
```

Never build production from a developer directory containing uncommitted source or local `.env`.

## Pre-deploy backup

Take a database backup using the infrastructure-approved method. Record backup ID/location and timestamp. Do not store production backups inside the Git repository or webroot.

## Deploy sequence

1. deploy immutable application artifact;
2. inject production environment/secrets;
3. run:

```bash
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

4. ensure scheduler trigger is active;
5. ensure queue worker process manager is healthy;
6. verify `/up` and `/health/ready`;
7. run a minimal production-safe smoke test;
8. inspect error, authorization, queue and performance logs.

## Immediate verification

```bash
php artisan migrate:status
php artisan performance:check
php artisan release:check --strict
php artisan queue:failed
CACHE_STORE=array php artisan schedule:list
```

Validate manually:

- admin login;
- participant login;
- one read-only workflow page per major role;
- authorized private document download with a non-sensitive test record;
- no unexpected 500/419 loop;
- queue worker consumes a safe test notification if available.

## Promotion decision

Promote only when all gates are PASS. If a migration, health, login, queue, authorization or data-integrity check fails, stop and follow `ROLLBACK-RUNBOOK.md`.
