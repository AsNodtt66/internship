# OWASP ASVS 5.0 Verification Baseline

Target for this internal business application: **ASVS Level 2-oriented verification** for controls relevant to the implemented feature set. This document is a verification map, not a certification claim.

## Authentication and session

Verify:

- successful and failed authentication events are logged;
- session ID changes on authentication;
- session cookie is Secure on HTTPS staging/production;
- session cookie is HttpOnly;
- SameSite is explicitly configured;
- session expiration returns users to the correct Filament panel;
- inactive users cannot access panels.

Evidence:

```bash
php artisan release:check --strict
php artisan test --filter=Security
```

Manual browser evidence: inspect `Set-Cookie` on staging over HTTPS.

## Authorization

Verify default-deny behavior for every role and direct URL/resource access:

| Actor | Own data | Other participant | Other unit | Sensitive workflow actions |
|---|---:|---:|---:|---:|
| Peserta | yes | no | no | no |
| Pembimbing | assigned only | no | no | evaluation scope only |
| Kepala Bagian | unit only | unit only | no | scoped actions only |
| PIC/SDM/GM | according to policy | according to policy | according to policy | explicit policy/action guard |

Automated regression:

```bash
php artisan test --filter=AuthorizationBoundaryTest
php artisan test --filter=PrivateDocumentAuthorizationTest
```

## File handling

Private participant/workflow documents must:

- remain outside the public webroot;
- use an allowlisted field/endpoint;
- reject traversal, absolute, control-character and URL-like paths;
- require authentication and authorization to download;
- respond with `Cache-Control: private, no-store`;
- enforce upload size and expected content type in the relevant Filament field;
- use generated/trusted storage paths rather than trusting arbitrary download filenames.

P7 adversarial cases:

```text
../.env
../../storage/logs/laravel.log
/etc/passwd
C:\Windows\system.ini
https://attacker/file.pdf
filename.pdf.exe
oversized PDF
non-PDF content labelled application/pdf
corrupt PDF
```

Important remaining Level-2 hardening item: add an antivirus/malware scanning integration if files come from untrusted external users and organizational risk assessment requires it.

## Data protection

Data classes:

| Class | Examples | Required controls |
|---|---|---|
| Restricted | KTP/KTM, BPJS, transcript, participant documents | private storage, scoped authorization, no public URLs, no log content |
| Confidential | evaluation, scores, approval records | role-scope authorization, audit trail |
| Internal | workflow status, unit assignment | authenticated access |
| Public | landing-page information | public delivery |

Never put restricted values, document contents, passwords, session tokens or full request bodies in operational logs.

## Logging and error handling

Required evidence:

- authentication success/failure events;
- failed authorization events;
- actor ID, action, route/resource ID, timestamp and request ID where useful;
- no sensitive document contents in logs;
- production error responses do not expose stack traces (`APP_DEBUG=false`).

## Configuration

Strict staging gate checks:

```bash
APP_DEBUG=false
APP_URL=https://...
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database   # or Redis/SQS, never sync in production
php artisan release:check --strict
```

## Sign-off

For each control record:

```text
Control:
Automated test:
Manual evidence:
Environment:
Commit/tag:
Result: PASS / FAIL / N/A
Reviewer:
Date:
```
