# P7 Release Candidate Validation

P7 is the **proof phase**. P0-P6 added controls and engineering improvements; P7 decides whether those controls are actually strong enough to promote a build to staging and production.

## Current release state

The source is **P7 validation-ready**, but it is not yet valid to label it production-ready until all strict gates pass on a real staging environment.

Known mandatory blocker: the repository still ships the P3/P6 dependency lock baseline (Laravel 12 / Filament 4 / Vite 5). P4 prepared the migration path, but the actual Composer/npm resolver run must still be completed online and committed before the strict P7 gate can pass.

## Release gates

### Gate A — source integrity

```bash
composer verify:release
php scripts/verify.php
composer verify:ui
composer verify:performance
```

### Gate B — clean database

```bash
# TEST DATABASE ONLY
php artisan migrate:fresh --env=testing
php artisan performance:check
php artisan test
```

### Gate C — static quality

```bash
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G
```

### Gate D — dependencies and build

```bash
composer audit --locked
npm ci
npm run build
npm audit --audit-level=high
```

### Gate E — release configuration

Local/non-strict readiness:

```bash
php artisan release:check
```

Staging candidate:

```bash
php artisan release:check --strict
```

Strict mode requires production-like HTTPS/session/queue configuration and the P4 target dependency majors.

### Gate F — security and business regression

Minimum regression suite includes:

- participant isolation and IDOR/direct-record access;
- Kepala Bagian scope isolation;
- Pembimbing scope isolation;
- private-document authentication/authorization;
- path traversal rejection;
- document-verification role guard;
- ordered approval transitions;
- duplicate approval guard;
- extension workflow and completion flow;
- health, rate limits and security headers.

### Gate G — operational proof

Before promotion:

- k6 smoke passes;
- backup → isolated restore → integrity check passes;
- queue worker and scheduler are running;
- staging deployment rehearsal passes;
- rollback rehearsal is documented and tested;
- browser/mobile/accessibility checklist completed;
- critical/high usability findings resolved.

## Release status vocabulary

Use only these labels:

| State | Meaning |
|---|---|
| Development | normal feature work |
| Validation-ready | source contains P7 gates but not all external gates executed |
| RC candidate | clean CI + strict staging check + operational drills pass |
| RC1/RC2 | immutable tagged candidate build |
| Production-ready | approved RC with deployment and rollback evidence |

Do not use “production-ready” based only on syntax checks or unit tests.
