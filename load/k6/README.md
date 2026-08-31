# k6 release smoke tests

These tests are intentionally **read-only**. Do not load-test approval, document mutation, or other business transitions against production.

Public smoke:

```bash
BASE_URL=https://staging.example.test k6 run load/k6/public-smoke.js
```

Authenticated read smoke uses a dedicated staging account/session:

```bash
BASE_URL=https://staging.example.test \
SESSION_COOKIE_NAME='app-session' \
SESSION_COOKIE_VALUE='...' \
AUTH_PATHS='/admin,/admin/pengajuans' \
k6 run load/k6/authenticated-read-smoke.js
```

Never commit session cookies. Use CI masked variables or an ephemeral local shell environment.
