# OWASP ASVS-Oriented Mapping

Dokumen ini bukan sertifikasi ASVS. Ini hanya mapping engineering agar security review berikutnya memiliki titik awal yang jelas.

## V2 — Validation & Business Logic

Implemented/partial:

- server-side workflow precondition checks;
- approval sequencing;
- database uniqueness untuk invariant tertentu;
- rate limiting pada endpoint sensitif tertentu;
- regression tests state transition.

Remaining:

- abuse-case review lengkap per action;
- concurrency tests lebih luas untuk semua mutating workflow.

## V3 — Web Frontend Security

Implemented/partial:

- CSRF middleware Laravel/Filament;
- secure response headers;
- custom generic error pages;
- CSP report-only capability.

Remaining:

- tune dan enforce CSP setelah browser validation;
- automated header scan dari deployed environment.

## V5 — File Handling

Implemented/partial:

- private storage;
- authorization sebelum download;
- path tampering/path traversal protection;
- upload type/size rules pada form yang relevan;
- private-document migration command.

Remaining depending on risk:

- malware scanning;
- CDR;
- dedicated object storage lifecycle policy.

## V6/V7 — Authentication & Session

Implemented/partial:

- framework password hashing;
- Filament login/register throttling;
- panel-level access checks;
- disabled-account check;
- auth success/failure/logout operational events.

Remaining:

- MFA untuk privileged users;
- centralized session revocation policy bila requirement muncul;
- deployed cookie/TLS verification.

## V8 — Authorization

Implemented:

- model policies;
- Filament strict authorization;
- direct URL regression smoke tests;
- relationship-based scope via `PengajuanAccess`;
- private document Gate/Policy path.

Remaining:

- full role-action matrix automation untuk setiap custom action/page.

## V13 — Configuration

Implemented/partial:

- `.env.example` only;
- secret/runtime files excluded;
- production checklist;
- safe demo seed defaults;
- explicit security header configuration.

Remaining:

- external secret manager integration per deployment platform;
- automated config policy checks in infrastructure repository.

## V14 — Data Protection

Implemented/partial:

- private documents;
- minimal audit payloads;
- source-only release artifact;
- backup/restore runbook.

Remaining:

- organizational data retention/deletion policy;
- encryption-at-rest assurance from infrastructure;
- privacy impact review for production data.

## V15 — Secure Coding & Architecture

Implemented/partial:

- modular monolith boundaries;
- CI quality gates;
- dependency audits;
- row locking on high-risk approval transition;
- no hand-edited dependency lock upgrade.

Remaining:

- make Larastan blocking after baseline cleanup;
- complete supported Vite upgrade;
- Laravel 13 upgrade branch.

## V16 — Logging & Error Handling

Implemented/partial:

- request correlation ID;
- operations log channel;
- auth/authz/queue/scheduler events;
- domain audit allowlist;
- custom public error pages;
- `APP_DEBUG=false` deployment rule.

Remaining:

- centralized collection/SIEM;
- alert routing/on-call policy;
- immutable/retained log storage defined by infrastructure.
