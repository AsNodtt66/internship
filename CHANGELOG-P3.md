# Changelog P3 — Production Hardening & Developer Experience

## Security hardening

- Added configurable security response headers middleware.
- HSTS is opt-in and HTTPS-only.
- CSP supports report-only rollout before enforcement.
- Added named rate limits for private document downloads, generated reports, and readiness probes.
- Added authentication success/failure/logout operational events without logging raw credentials.
- Added authorization denial operational logging.
- Added panel-aware 419 session-expiry redirects.
- Added generic 403/404/429/500/503 error pages.
- Hardened demo seeding: opt-in local/testing only, production refusal, minimum 12-character demo password.

## Regression tests

- Security header behavior.
- HSTS HTTPS-only behavior.
- CSP report-only behavior.
- Readiness endpoint rate limiting.
- Draft submission transition and double-submit rejection.
- Document verification role guard and rejection-note requirement.
- Four-step approval creation.
- Out-of-order approval rejection.

## Developer experience

- Added Linux/macOS quick-start script.
- Added Windows PowerShell quick-start script.
- Added `composer doctor` environment diagnostics.
- Reworked root README for fast onboarding.
- Added dedicated frontend and backend developer guides.
- Added project structure, business workflow, role/permission, configuration, testing, troubleshooting, production hardening, developer workflow, and ASVS-oriented docs.
- Added root security policy.

## Stability decisions

- No Laravel/Filament/Vite major version bump in this release.
- No lockfile is manually edited to simulate dependency resolution.
- CSP enforcement and MFA remain explicit follow-up work requiring deployed-environment validation.
