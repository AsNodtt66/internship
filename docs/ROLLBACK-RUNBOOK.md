# Rollback Runbook

Rollback must be rehearsed before the first production release.

## Trigger conditions

Rollback is preferred for:

- widespread login/authorization failure;
- migration incompatibility with current code;
- workflow state corruption;
- private-document exposure;
- sustained 5xx increase;
- queue failure storm;
- severe performance regression.

## Application rollback

1. stop promotion/traffic shift;
2. preserve logs and incident timestamps;
3. deploy the previous known-good immutable artifact;
4. restore the previous environment configuration if it changed;
5. run:

```bash
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

6. verify `/up`, `/health/ready`, login and read-only workflow access.

## Database rule

**Do not automatically run `migrate:rollback` in production.**

Schema rollback can destroy data and may be incompatible with records already written by the new application version. Prefer backward-compatible expand/contract migrations:

```text
release N: add nullable/new structure
release N+1: migrate/read both
release N+2: remove old structure after verification
```

If database restore is required, execute the incident-specific recovery plan using a verified backup and explicit business approval.

## Queue considerations

A rollback must restart workers because workers are long-lived processes. Check failed jobs after rollback:

```bash
php artisan queue:restart
php artisan queue:failed
```

## Rollback evidence

Capture:

```text
incident/rehearsal ID
from tag
to tag
start/end time
DB action
queue action
health checks
smoke result
owner
```
