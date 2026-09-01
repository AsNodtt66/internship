# Testing & Quality Gates

## Tujuan

Test bukan hanya mengecek halaman dapat dibuka. Untuk workflow multi-role, test harus melindungi authorization, state transition, concurrency/invariant, private documents, dan operational endpoints.

## Quick verification

```bash
composer verify:quick
```

Mengecek syntax/bootstrap/contracts yang tidak memodifikasi database utama.

## UI source audit

```bash
composer verify:ui
```

P5 UI audit memeriksa landmark/accessibility marker dan konsistensi copy custom yang dapat diverifikasi dari source. Ini tidak menggantikan browser, keyboard, screen-reader, atau usability testing.
 Audit juga memeriksa wayfinding wizard (`Kembali`/`Lanjut`, deskripsi tahap, query-string step), semantic timestamp notifikasi, dan terminology admin yang sudah diseragamkan.

## PHPUnit

```bash
php artisan test
```

`phpunit.xml` memakai SQLite `:memory:` untuk test suite.

## Feature regression yang penting

Saat ini test suite mencakup baseline seperti:

```text
Health/readiness
Request ID
Security headers
Rate limiting
Pengajuan access scoping
Private document path safety
Domain audit observer
Submission workflow
Landing accessibility landmarks
Pengajuan status presentation
Document verification role guard
Approval step creation/order guard
```

Tambahkan test ketika bug ditemukan; bug production yang sudah diperbaiki seharusnya menjadi regression test jika realistis.

## Fresh migration test

Pada database disposable:

```bash
php artisan migrate:fresh --env=testing
php artisan test
```

## Formatting

```bash
php vendor/bin/pint --test
```

Untuk memperbaiki otomatis:

```bash
php vendor/bin/pint
```

Review diff setelah auto-format.

## Static analysis

Baseline P2 menyiapkan Larastan secara transitional.

```bash
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G
```

Target: jadikan stage blocking setelah baseline finding direview dan `larastan/larastan` masuk root lockfile melalui Composer resolver yang sah.

## Frontend build

```bash
npm ci
npm run build
```

Build production harus lulus sebelum merge perubahan asset/UI.

## Dependency audits

```bash
composer audit --locked
npm audit --audit-level=high
```

Jangan memperbaiki audit dengan random major update di branch business feature. Buat dependency-upgrade branch terpisah.

## Authorization test pattern

Setiap permission penting idealnya punya:

```text
allowed actor -> PASS
wrong role     -> 403/denied
right role but wrong ownership/scope -> denied
```

## Workflow test pattern

```text
valid current state -> valid next state
invalid current state -> RuntimeException/domain rejection
out-of-order step -> rejected
double submit -> rejected/idempotent as designed
```


## P6 performance regression

```bash
composer verify:performance
php artisan test --filter=ActiveApprovalAggregationTest
php artisan performance:check
```

`ActiveApprovalAggregationTest` menjaga perhitungan tahap approval aktif tetap satu SQL query. `performance:check` memerlukan database yang sudah dimigrasi dan memverifikasi index hot-path P6.

---

## P7 release regression

Source/readiness gate:

```bash
composer verify:release
php artisan release:check
```

Security boundary regression:

```bash
php artisan test --filter=AuthorizationBoundaryTest
php artisan test --filter=PrivateDocumentAuthorizationTest
php artisan test --filter=ApprovalDuplicateGuardTest
```

Historical P7 strict gate (tidak menjadi requirement testing P8):

```bash
php artisan release:check --strict
```

The strict gate is **not** expected to pass on the source-only baseline until P4 dependency modernization has been executed and production-like HTTPS/session/queue settings are present.

See `P7-RELEASE-CANDIDATE.md` for the full CI, k6, restore, browser and rollback matrix.

---

## P8 Playwright E2E + MySQL integration

P8 adalah test baseline utama. Setup lengkap: [P8-PLAYWRIGHT-CI.md](P8-PLAYWRIGHT-CI.md).

Critical browser smoke:

```bash
export APP_ENV=testing
# DB_* wajib menunjuk database disposable
bash scripts/e2e/reset-test-db.sh
bash scripts/e2e/install-playwright.sh chromium
npm run build
npm run test:e2e:critical
```

Full matrix:

```bash
bash scripts/e2e/install-playwright.sh
npm run test:e2e
```

Mandatory CI juga menjalankan migration + PHPUnit pada MySQL 8.4. Ini melengkapi SQLite `:memory:` yang tetap dipakai untuk feedback cepat.

### CI green

Jangan menyebut testing berhasil hanya karena satu job hijau. Target final adalah job `ci_green_gate`, yang bergantung pada backend PHP 8.3/8.4, MySQL integration, build, audits, dan seluruh Playwright browser gates.

## P9 quality gate

Untuk urutan gate kandidat P9, gunakan [P9 Test Strategy](P9-TEST-STRATEGY.md). Panduan tersebut membedakan hasil lokal, coverage CI, mutation testing, accessibility, visual regression, dan bukti GitHub Actions pada SHA yang sama.
