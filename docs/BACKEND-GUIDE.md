# Backend Guide


> **P4:** untuk menjalankan modernisasi dependency, gunakan PHP 8.4/8.5 dan Node 22.12+ lalu ikuti [P4 Modernization](P4-MODERNIZATION.md). Baseline P3 tetap dapat di-install dari lockfile yang disertakan.

## Gambaran

Backend adalah Laravel monolith. Jangan membuat service/repository baru hanya karena ingin menambah layer; gunakan abstraction ketika ada concern yang nyata dan berulang.

## Request flow

```text
HTTP / Filament Action
       │
       ▼
Authentication
       │
       ▼
Policy / Gate
       │
       ▼
Validation
       │
       ▼
Workflow/Application Service
       │
       ▼
Eloquent + DB transaction
       │
       ├── Notification
       ├── Audit log
       └── Private storage
```

## Authorization

Source of truth:

```text
app/Policies/
app/Support/Authorization/PengajuanAccess.php
```

Aturan:

- Controller memakai `Gate::authorize()`/policy.
- Filament resource memakai policy + `strictAuthorization()`.
- Query dashboard/resource yang bersifat scoped menggunakan `PengajuanAccess`.
- Jangan duplikasi role check dengan string literal di banyak tempat.

## Workflow

Façade utama:

```text
app/Services/PengajuanWorkflowService.php
```

Concern yang sudah diekstrak:

```text
app/Services/Workflow/
├── ExtensionReminderService.php
└── WorkflowNotificationService.php
```

Saat menambah workflow:

1. definisikan precondition status/role;
2. lakukan state transition di service, bukan view;
3. gunakan transaction + row lock pada transition yang rawan concurrency;
4. catat riwayat/audit bila state penting berubah;
5. kirim notification setelah state valid;
6. tambahkan feature regression test.

## Models

```text
app/Models/
```

Model menjaga relationship/cast. Business orchestration besar jangan ditaruh di model event tersembunyi karena sulit diuji dan ditelusuri.

## Database

Migration:

```text
database/migrations/
```

Invariant yang benar-benar wajib harus ditegakkan database bila memungkinkan, mis. `UNIQUE`, foreign key, nullable/non-nullable.

## Audit trail

```text
app/Observers/DomainAuditObserver.php
```

Hanya field workflow aman yang masuk `changes`. Jangan perluas allowlist ke password, token, isi dokumen, alamat, atau data identitas tanpa security/privacy review.

## Request ID

```text
app/Http/Middleware/RequestId.php
```

Setiap response membawa `X-Request-ID` dan ID sama masuk log context. Gunakan ID ini untuk korelasi incident antara reverse proxy dan aplikasi.

## Security headers

```text
app/Http/Middleware/SecurityHeaders.php
config/security.php
```

HSTS tidak diaktifkan default karena local development menggunakan HTTP. Aktifkan hanya ketika production benar-benar HTTPS.

## Rate limiter

Named limiter berada di `AppServiceProvider`:

```text
private-documents
generated-reports
health
```

Login/register mengikuti limiter bawaan Filament.

## Private documents

```text
app/Support/Documents/PrivateDocumentRegistry.php
app/Http/Controllers/PrivateDocumentController.php
```

Controller wajib authenticate + authorize sebelum file diberikan.

## Console & scheduler

```text
app/Console/Commands/
routes/console.php
```

Lihat jadwal:

```bash
CACHE_STORE=array php artisan schedule:list
```

Pada production gunakan cache persisten untuk `withoutOverlapping()`.

## Queue

Default database queue. Jalankan worker:

```bash
php artisan queue:work --tries=3 --timeout=80
```

`retry_after` harus lebih besar dari worker timeout.

## Error handling

Custom error pages terdapat di `resources/views/errors/`. Exception configuration berada di `bootstrap/app.php`.

Authorization denials dicatat ke operations log dengan metadata minimal, tidak dengan payload request.

## Menambah endpoint/controller baru

Checklist:

```text
[ ] Authentication dibutuhkan?
[ ] Policy/Gate?
[ ] Route model binding aman?
[ ] Rate limiting perlu?
[ ] Validation?
[ ] Sensitive output/file?
[ ] Audit/log perlu?
[ ] Feature test positive + negative?
```

## P6 — query dan performance

Hot-path query harus mengikuti aturan berikut:

1. hindari query di dalam loop jika dapat diekspresikan dengan `whereHas`, `whereDoesntHave`, aggregate, eager loading, atau batch;
2. gunakan `chunkById()` / `lazyById()` untuk batch besar yang memproses banyak row;
3. gunakan composite index hanya setelah melihat pola filter/sort nyata;
4. gunakan `php artisan performance:check` setelah migration;
5. pantau `performance.slow_database_request` dan `performance.slow_request` di operations log;
6. jangan masukkan query bindings berisi PII ke log performance.

Audit source cepat:

```bash
composer verify:performance
```

Panduan lengkap: [P6-PERFORMANCE.md](P6-PERFORMANCE.md).

---

## P8 backend regression

Perubahan policy, workflow service, migration, atau relationship harus lulus SQLite fast suite **dan** MySQL integration suite di CI. Business rule yang dibekukan ada di [BUSINESS-RULES.md](BUSINESS-RULES.md).

Untuk bug authorization/workflow, tambahkan PHPUnit regression terlebih dahulu; tambah Playwright bila bug juga bergantung pada route/browser/Livewire interaction.

P9 memprioritaskan coverage dan mutation testing pada policy, workflow, authorization support, serta private document support. Jalankan dan baca hasilnya melalui [P9 Coverage](P9-COVERAGE.md) dan [P9 Mutation Testing](P9-MUTATION-TESTING.md).
