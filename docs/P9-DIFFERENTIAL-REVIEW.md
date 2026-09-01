# P9 Differential Review

**Scope:** `0c2f40b..working tree` on 1 September 2026  
**Method:** focused security differential review for a large Laravel/Filament change set; full review of authorization, workflow, migrations, route boundaries, CI, and new test infrastructure; sampled review of mechanical type and formatting edits.

## Result

| Severity | Findings |
| --- | ---: |
| Critical | 0 |
| High | 0 |
| Medium | 0 |
| Low | 0 |

**Recommendation:** approve the local candidate for CI. This is not a release approval: PHP coverage and mutation evidence must still come from PCOV CI on the exact candidate SHA.

## What changed

The working-tree diff contains 145 files and 4,184 additions with 2,559 deletions. The main behavioural areas are workflow status safety, private-document and authorization tests, test-environment determinism, browser quality gates, CI coverage/mutation jobs, and maintainer documentation. A substantial portion is PHPStan/Pint type and formatting normalization.

| Area | Risk | Review result |
| --- | --- | --- |
| Authorization policies and protected documents | High | Policy permissions and deny-by-default delete rules remain intact. Feature and Playwright direct-URL checks pass. |
| Workflow/status and evaluation display | High | Unknown timeline status now has a safe fallback; absent evaluation is read safely. Workflow tests and critical browser repetition pass. |
| Fresh SQLite migrations | High | The initial `pengajuans` enum includes statuses needed by the tested workflow. A fresh test migration completed successfully. |
| CI and dependency changes | High | PCOV coverage and Infection are mandatory dependencies of the aggregate gate. Workflow uses trusted checkout code and no unsafe event trigger was introduced. |
| Browser/Axe/visual infrastructure | Medium | Tests use deterministic seed data, trace/video on failure, and Chromium-only portable visual baselines. |
| Documentation and formatting | Low | Reviewed for executable instructions and unsupported release claims. |

## Security and regression checks

- `git diff --check 0c2f40b` passed with no whitespace error.
- High-risk policy edits are formatting-only or preserve the same role predicate. Delete, force-delete, and restore denials remain explicit for user and participant history.
- Route and protected-download boundaries remain covered by `PrivateDocumentAuthorizationTest` and browser direct-URL tests.
- The new CI jobs run `composer install` from the lockfile, create an ephemeral SQLite test configuration, and add their result to `ci-green`.
- The diff adds no `pull_request_target` or `workflow_run` trigger and no secret is printed by the new jobs.
- No security check was removed. A `git log -S`/blame review of the changed policy and workflow regions found no reverted security fix.

## Test coverage and blast radius

| Changed boundary | Callers / surface | Evidence |
| --- | --- | --- |
| `PengajuanTimelineService` fallback | Participant detail and internal workflow views | Unit regression test plus Chromium critical rerun. |
| Evaluation null handling | Participant detail page | Full Chromium and cross-browser suite. |
| Policy gates | Filament resources and protected routes | 44 PHPUnit tests and authorization browser cases. |
| CI quality jobs | Pull request and push validation | YAML inspection; remote execution remains required. |

Local test coverage metrics are unavailable because this PHP runtime lacks PCOV/Xdebug. That is a measurement limitation, not an exception from testing; the candidate is blocked from release until the CI artifacts exist.

## Residual risk

1. The first Linux CI run is required to validate PCOV artifact generation, Infection, and Linux screenshot comparison.
2. Visual regression intentionally covers landing and participant login only. Form, document, approval, and evaluation pages remain candidates for future stable baselines.
3. Axe automation does not replace manual keyboard, 200% zoom, screen-reader, and real-user review.

## Conclusion

No differential security regression was found in the reviewed scope. Local evidence supports committing the candidate; exact-SHA GitHub Actions remains the decisive next gate.
