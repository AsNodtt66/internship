# P7 Load and Concurrency Testing

Performance tests are release evidence, not a substitute for functional tests.

## Install k6

Use the official Grafana k6 installation instructions for your OS. Do not vendor the k6 binary into this repository.

## 1. Public smoke

```bash
BASE_URL=https://staging.example.com \
VUS=1 ITERATIONS=8 \
k6 run load/k6/public-smoke.js
```

Default gates:

```text
HTTP failures < 1%
checks > 99%
p95 < 1000 ms
p99 < 2000 ms
```

Tune only after collecting a representative staging baseline.

## 2. Authenticated read smoke

Create a dedicated staging-only test account/session. Never reuse a production administrator session.

```bash
BASE_URL=https://staging.example.com \
SESSION_COOKIE_NAME='...' \
SESSION_COOKIE_VALUE='...' \
AUTH_PATHS='/admin,/admin/pengajuans' \
VUS=3 DURATION=30s \
k6 run load/k6/authenticated-read-smoke.js
```

Use CI masked variables if automated.

## 3. Load progression

After smoke passes:

```text
1 VU → 5 VU → 10 VU → 25 VU → 50 VU
```

Do not assume 50 VU is a production SLO. Size test load from expected concurrency and staging capacity.

## 4. Business concurrency

Mutating business actions must not be fired blindly by k6 against real data. Use dedicated staging fixtures and verify:

- two users attempting the same approval;
- double-click approval;
- duplicate extension decision;
- duplicate mentor assignment;
- repeated completion-document generation.

Expected result:

```text
one valid state transition
second request safely rejected or idempotent
no duplicate domain row
no double notification
no corrupt workflow state
```

Existing workflow uses transaction + row locking on approval hot paths; the final proof must run on the same database engine used by production.

## Result capture

Record:

```text
commit/tag
staging topology
DB engine/version
data volume
VUs/duration
p50/p95/p99
HTTP error rate
DB slow-query events
failed jobs
deadlocks
result
```
