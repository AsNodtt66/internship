# Changelog P4 — Modernization Readiness

## Source compatibility

- Added Laravel 12/13 compatible CSRF/request-forgery middleware selection for both Filament panels.
- Added Laravel 12/13 compatible `QueueBusy` operational logging (`connection` → `connectionName`).
- Added Laravel 13 cache object-unserialization hardening (`serializable_classes=false`).
- Added Laravel 13 JSON session serialization target with `SESSION_SERIALIZATION=json`.
- Audited Laravel 13 breaking-change surface: no application `upsert()` call with empty `uniqueBy`, no custom `array_first`/`array_last`, no custom queue/cache contract implementation found.
- Audited Vite config for Rolldown-sensitive project options; no project-level `esbuild` or `rollupOptions` customization found.
- Audited custom Livewire surface; no direct `app/Livewire` components identified.

## Upgrade automation

Added:

```text
scripts/upgrade/p4/modernize.sh
scripts/upgrade/p4/modernize.ps1
scripts/upgrade/p4/compatibility-check.php
scripts/upgrade/p4/composer.target.json
scripts/upgrade/p4/package.target.json
```

The automation stages:

1. PHP 8.4 baseline.
2. Laravel 13 + Tinker 3 + PHPUnit 12.
3. Vite 8 + Laravel Vite Plugin 3.
4. Filament 5 + Livewire 4.
5. regression tests, build, and dependency audits.

## Documentation

Added:

- `docs/P4-MODERNIZATION.md`
- `docs/P4-LARAVEL-13.md`
- `docs/P4-VITE-8.md`
- `docs/P4-FILAMENT-5.md`

## Dependency-resolution note

The audit environment cannot reach Packagist/npm registry. Existing P3 lockfiles are therefore intentionally preserved instead of manually fabricated. Run the P4 modernizer in an online development/CI environment to generate valid upgraded lockfiles, then commit those lockfiles only after all quality gates pass.
