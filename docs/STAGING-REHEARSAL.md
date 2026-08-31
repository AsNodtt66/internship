# Staging Rehearsal

Staging should resemble production in PHP version, database engine, queue backend, HTTPS/session behavior and process topology.

## Automated validation

```bash
export APP_ENV=staging
bash scripts/release/staging-verify.sh
```

Then:

```bash
composer audit --locked
npm audit --audit-level=high
BASE_URL=https://staging.example.com k6 run load/k6/public-smoke.js
```

## Manual matrix

Test at minimum:

- Chrome/Edge current desktop;
- Firefox current desktop;
- Android Chrome;
- iOS Safari when available;
- 320, 375, 768, 1024 and 1440 px widths;
- keyboard-only navigation;
- 200% zoom;
- session expiry and re-login;
- per-role navigation and direct URLs;
- private file download authorization.

## Exit criteria

No unresolved Critical/High security issue, no workflow-blocking defect, no data-integrity defect, no repeated 5xx, no failed migration, and rollback rehearsal evidence exists.
