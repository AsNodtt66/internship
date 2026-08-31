# Changelog P2 — Reliability & Delivery Engineering

## Added

- Request/correlation ID middleware (`X-Request-ID`) + log context.
- Database readiness endpoint `/health/ready` selain Laravel liveness `/up`.
- Dedicated daily `operations` log channel.
- Domain audit trail (`audit_logs`) dengan allowlist field non-PII.
- Queue failure and queue-busy operational logging.
- Queue monitor and failed-job pruning schedules.
- Scheduler success/failure operational logging.
- `QUEUE_AFTER_COMMIT=true` baseline untuk asynchronous work.
- GitLab CI pipeline: PHP 8.4/8.5 tests, migration-from-zero, Pint, frontend build, audits.
- Larastan configuration + transitional CI bootstrap.
- Node runtime pin `.nvmrc` (22.16.0).
- Supervisor worker example.
- MySQL/SQLite backup scripts and guarded MySQL restore drill script.
- Operations, backup/restore, and upgrade-readiness documentation.
- Health/request-ID feature tests.

## Stability decisions

- Laravel/Filament/Vite major versions are intentionally **not** changed in this artifact.
- No lockfile is hand-edited to fake a dependency upgrade.
- Vite upgrade and root Larastan dependency are deferred to resolver-backed branches where lockfiles can be regenerated and reviewed.
- Audit payloads intentionally exclude sensitive participant/document content.
