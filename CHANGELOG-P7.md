# Changelog P7 — Release Candidate Validation

## Release engineering

- Added `php artisan release:check` and strict staging mode `--strict`.
- Added P7 source-only release audit (`composer verify:release`).
- Added GitLab CI jobs for source release audit, clean-DB release gate, and manual strict staging gate.
- Added staging verification helper and release/rollback runbooks.

## Security regression

- Added participant private-document IDOR/download tests.
- Added Kepala Bagian and Pembimbing scope boundary tests.
- Extended private-path rejection for URL-like paths, `.` path segments and control characters.
- Added duplicate approval regression guard.

## Performance / load readiness

- Added k6 public smoke and authenticated read-only smoke tests.
- Added documented pass/fail thresholds and load progression guidance.
- Mutating concurrency tests remain staging-only with dedicated fixtures.

## Operations

- Added safety-first backup/restore drill helper.
- Added staging, browser/mobile/accessibility, ASVS verification and release evidence checklists.

## Known mandatory gate before production

The committed lockfiles remain on the pre-P4 dependency baseline until the P4 online modernization is executed using real Composer/npm dependency resolution. `release:check --strict` intentionally fails until the target Laravel/Filament/Vite majors are actually installed and committed.
