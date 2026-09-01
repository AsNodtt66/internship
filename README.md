# Internship Management System

Aplikasi pengelolaan PKL/Penelitian berbasis **Laravel 12 + Filament 4**. Aplikasi mencakup portal peserta, verifikasi dokumen, disposisi berjenjang, penetapan pembimbing, evaluasi/penilaian, perpanjangan, notifikasi, surat resmi, audit trail, queue, scheduler, dan health checks.

Baseline ini mencakup hardening P0-P3, kesiapan modernisasi P4, UI dan aksesibilitas P5, performance engineering P6, validasi release candidate P7, quality gate Playwright P8, serta penguatan kualitas test dan CI P9. Setiap perubahan penting harus dapat diuji dan dibuktikan.

> **Fokus P9:** kualitas test dan CI. Staging/production deployment tidak menjadi requirement pipeline karena deployment akhir akan ditangani server perusahaan.

> **Status P4:** source compatibility sudah diterapkan. Lockfile utama tetap baseline P3 sampai `scripts/upgrade/p4/modernize.*` dijalankan pada environment online dan seluruh quality gate lulus. Jangan mengedit lockfile manual.

## Mulai dalam 5 menit

### Linux / macOS

```bash
bash scripts/dev/quick-start.sh
composer dev
```

### Windows PowerShell

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\dev\quick-start.ps1
composer dev
```

Kemudian buka:

- Landing page: `http://127.0.0.1:8000`
- Portal peserta: `http://127.0.0.1:8000/peserta`
- Panel internal/admin: `http://127.0.0.1:8000/admin`
- Liveness: `http://127.0.0.1:8000/up`
- Readiness DB: `http://127.0.0.1:8000/health/ready`

> Demo user **tidak dibuat secara default**. Lihat [Quick Start](docs/QUICK-START.md) bila ingin data demo lokal.

## Requirement

- PHP `>= 8.2`; PHP **8.4** direkomendasikan untuk environment baru.
- Composer 2.x.
- Node.js 22.x + npm.
- MySQL/MariaDB untuk deployment utama, atau SQLite untuk local/testing.
- PHP extensions penting: `mbstring`, `openssl`, `pdo`, `fileinfo`, `dom`, `xml`, `xmlwriter`; plus `pdo_mysql` atau `pdo_sqlite`.

Cek komputer Anda:

```bash
composer doctor
```

## Dokumentasi

Dokumentasi lengkap dimulai dari [Documentation Index](docs/README.md). Pilih panduan berdasarkan pekerjaan Anda:

| Kebutuhan | Dokumen |
| --- | --- |
| Setup lokal | [Quick Start](docs/QUICK-START.md) |
| Memahami kode dan workflow | [Architecture](docs/ARCHITECTURE.md) dan [Business Workflow](docs/BUSINESS-WORKFLOW.md) |
| Mengubah aplikasi dengan aman | [Developer Workflow](docs/DEVELOPER-WORKFLOW.md) dan [Testing](docs/TESTING.md) |
| Menjalankan gate P9 | [P9 Test Strategy](docs/P9-TEST-STRATEGY.md) |
| Menyiapkan rilis | [Release Runbook](docs/RELEASE-RUNBOOK.md) |
| Memahami perkembangan P1-P9 | [Peta Fase](docs/PHASES-P1-P9.md) |

## Arsitektur singkat

Ini adalah **modular Laravel monolith**, bukan frontend SPA + REST API terpisah. Sebagian besar frontend aplikasi dibangun server-side dengan Filament/Livewire.

```text
Browser
  │
  ├── /peserta  ── Filament Peserta Panel
  ├── /admin    ── Filament Admin Panel
  └── /         ── Blade landing page
                    │
                    ▼
              Policy / Gate
                    │
                    ▼
        Application / Workflow Services
                    │
                    ▼
              Eloquent Models
                    │
          ┌─────────┴──────────┐
          ▼                    ▼
       Database        Private Document Storage
```

## Struktur utama

```text
app/
├── Enums/                 # enum domain, mis. RoleSlug
├── Filament/              # UI admin & peserta
├── Http/
│   ├── Controllers/       # PDF, private document, health
│   └── Middleware/        # request ID + security headers
├── Models/                # Eloquent domain models
├── Observers/             # audit trail domain
├── Policies/              # server-side authorization
├── Services/
│   ├── PengajuanWorkflowService.php
│   └── Workflow/          # notification/reminder concern
└── Support/
    ├── Authorization/     # query/data scoping
    └── Documents/         # private-document registry

database/
├── migrations/
└── seeders/

docs/                      # dokumentasi developer & operations
ops/                       # supervisor + backup/restore scripts
scripts/                   # verification + quick-start helpers
tests/                     # unit/feature/regression tests
```



## Testing dan CI

Kualitas proyek diperiksa bertingkat. PHPStan dan PHPUnit menangkap masalah source dan domain. MySQL integration memeriksa perilaku database. Playwright menguji alur pengguna pada browser nyata. Coverage dan mutation testing menguji apakah test suite benar-benar memberi sinyal saat source berubah.

Sebelum membuat commit, jalankan:

```powershell
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --memory-limit=1G --no-progress
php artisan test
npm run build
npm run test:e2e:critical
```

Untuk kandidat yang akan di-push, lanjutkan dengan Chromium penuh, browser lintas platform, dan flake check. Coverage membutuhkan PCOV atau Xdebug; CI memakai PCOV.

```powershell
npm run test:e2e:chromium
npm run test:e2e:cross-browser
npx playwright test --project=chromium --grep @critical --repeat-each=3
```

Baca [P9 Test Strategy](docs/P9-TEST-STRATEGY.md) untuk urutan lengkap, lokasi artefak, coverage, mutation, accessibility, visual regression, dan cara memverifikasi GitHub Actions pada SHA yang sama. Status bukti terbaru ada di [P9 Quality Gate](docs/P9-QUALITY-GATE.md).

## P7 release candidate validation

P7 tetap menyimpan release-validation tools historis, tetapi strict staging/production gate **bukan target CI P8**.

```bash
composer verify:release
php artisan release:check
```

Di staging dengan konfigurasi production-like:

```bash
php artisan release:check --strict
```

Strict mode memeriksa HTTPS, secure session cookie, asynchronous queue, private document storage, database/migration readiness, dan target dependency major P4. Selama lockfile masih Laravel 12 / Filament 4 / Vite 5, strict gate **memang harus gagal**.

Release evidence tambahan:

```bash
BASE_URL=https://staging.example.com k6 run load/k6/public-smoke.js
bash scripts/release/backup-restore-drill.sh
```

Panduan utama: **[P7 Release Candidate](docs/P7-RELEASE-CANDIDATE.md)** dan **[Release Runbook](docs/RELEASE-RUNBOOK.md)**.

## P6 performance engineering

P6 mengurangi query/memory pressure tanpa mengubah business flow: default 5-second polling Filament pada stats/chart yang tidak perlu realtime dimatikan, agregasi dashboard dipindahkan ke SQL, N+1 pada `TugasSaya` dihapus, dan migration menambahkan composite index untuk hot path yang nyata.

Quick checks:

```bash
composer verify:performance
php artisan performance:check   # sesudah migration / butuh DB
```

Observability dapat dituning melalui `PERFORMANCE_DB_WARN_MS`, `PERFORMANCE_REQUEST_WARN_MS`, dan optional `PERFORMANCE_SERVER_TIMING`. Lihat **[P6 Performance Engineering](docs/P6-PERFORMANCE.md)**.

## P5 UI/UX dan accessibility

P5 menyelaraskan landing page, panel peserta, dan panel internal tanpa mengubah business workflow. Fokusnya adalah konsistensi copy, hierarchy informasi, responsive behavior, keyboard focus, reduced motion, status yang tidak bergantung pada warna, dan usability testing yang dapat diulang.

Verification cepat:

```bash
composer verify:ui
```

Dokumentasi:

- [P5 UI/UX](docs/P5-UI-UX.md)
- [Accessibility](docs/ACCESSIBILITY.md)
- [Usability Testing](docs/USABILITY-TESTING.md)
- [UI Copy Guide](docs/UI-COPY-GUIDE.md)

> P5 tidak mengklaim sertifikasi WCAG. Pengujian keyboard, zoom, screen reader, browser/mobile, dan usability test dengan pengguna nyata tetap diperlukan.

## P4 modernization

Source sudah dipersiapkan untuk modernisasi tanpa memalsukan lockfile. Saat ini lockfile mendukung runner PHP 8.3/8.4; jangan menaikkan CI ke PHP 8.5 sebelum dependency resolver menghasilkan graph yang kompatibel.

Pre-check:

```bash
php scripts/upgrade/p4/compatibility-check.php
```

Upgrade otomatis (online workstation/CI):

```bash
bash scripts/upgrade/p4/modernize.sh
```

Windows:

```powershell
.\scripts\upgrade\p4\modernize.ps1
```

Baca **[P4 Modernization Guide](docs/P4-MODERNIZATION.md)** sebelum menjalankan upgrade.

## Development sehari-hari

Setelah setup pertama:

```bash
composer dev
```

Perintah tersebut menjalankan development server, queue listener, log viewer, dan Vite dev server secara bersamaan.

Jika ingin menjalankan secara terpisah:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

## Frontend

Frontend utama menggunakan **Filament + Livewire + Tailwind CSS + Vite**.

File penting:

```text
app/Filament/                         # Resource, Page, Widget, Action
resources/css/filament/admin/theme.css
resources/views/                      # landing, hooks, error pages, PDF/views
vite.config.js
```

Build production asset:

```bash
npm ci
npm run build
```

Jangan edit file di `vendor/`, `node_modules/`, atau hasil build `public/build/` secara manual.

Panduan lengkap: **[Frontend Guide](docs/FRONTEND-GUIDE.md)** dan **[P5 UI/UX](docs/P5-UI-UX.md)**.

## Backend

Backend menggunakan Laravel/Eloquent dengan Policy sebagai authorization source of truth dan `PengajuanWorkflowService` sebagai façade workflow utama.

Command penting:

```bash
php artisan route:list --except-vendor
php artisan schedule:list
php artisan queue:work
php artisan documents:migrate-private
```

Panduan lengkap: **[Backend Guide](docs/BACKEND-GUIDE.md)**.

## Database dan seeding

Migration normal:

```bash
php artisan migrate
```

Seed master data:

```bash
php artisan db:seed
```

Secara default `db:seed` hanya menyiapkan master data penting. Demo users bersifat opt-in untuk `local/testing`:

```env
SEED_DEMO_USERS=true
SEED_DEFAULT_PASSWORD="<buat-password-demo-minimal-12-karakter>"
```

Kemudian:

```bash
php artisan db:seed
```

`UserSeeder` menolak berjalan di production.

## Security baseline

- Filament memakai `strictAuthorization()`.
- Policy/Gate adalah security boundary; visibility menu bukan authorization.
- Dokumen peserta sensitif disimpan pada private disk dan diunduh melalui authenticated + authorized controller.
- File path tampering Filament dicegah.
- Dokumen/PDF endpoints memiliki rate limit.
- Login/register Filament tetap menggunakan rate limiting bawaan framework.
- Security headers default: `nosniff`, frame protection, referrer policy, permissions policy.
- HSTS hanya diaktifkan secara eksplisit pada HTTPS production.
- CSP disiapkan report-only dahulu; jangan enforce tanpa browser validation.
- Authentication failure, authorization denial, queue failure, dan queue congestion masuk operational log.
- Domain audit hanya menyimpan field workflow yang di-allowlist, bukan isi KTP/CV/BPJS/password/token.

Lihat [Production Hardening](docs/PRODUCTION-HARDENING.md) dan [ASVS Mapping](docs/ASVS-MAPPING.md).


## GitHub Actions CI

The repository includes `.github/workflows/ci.yml` for automated project testing on GitHub: PHP 8.3/8.4, MySQL 8.4 integration, frontend build/security audit, and Playwright Chromium/Firefox/WebKit/mobile. The final `CI Green Gate` only passes when every mandatory job succeeds. See `docs/GITHUB-CI.md`.

## Testing & quality gate

Quick check:

```bash
composer verify:quick
```

Full check:

```bash
composer verify
```

CI/release environment sebaiknya menjalankan:

```bash
php artisan test
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G
npm ci
npm run build
composer audit --locked
npm audit --audit-level=high
```

Untuk membuktikan schema dapat dibangun dari nol, gunakan database **testing/disposable**:

```bash
php artisan migrate:fresh --env=testing
php artisan test
```

**Jangan pernah menjalankan `migrate:fresh` pada production.**

## Queue & scheduler production

Queue worker adalah proses jangka panjang dan harus dikelola process manager. Contoh Supervisor tersedia di `ops/supervisor/`.

Saat deploy:

```bash
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

Scheduler host:

```cron
* * * * * cd /var/www/internship-management && php artisan schedule:run >> /dev/null 2>&1
```

## Dokumentasi

Mulai dari **[Documentation Index](docs/README.md)**.

Dokumen paling sering dipakai:

- [Quick Start](docs/QUICK-START.md)
- [Local Development](docs/LOCAL-DEVELOPMENT.md)
- [Frontend Guide](docs/FRONTEND-GUIDE.md)
- [Backend Guide](docs/BACKEND-GUIDE.md)
- [Project Structure](docs/PROJECT-STRUCTURE.md)
- [Business Workflow](docs/BUSINESS-WORKFLOW.md)
- [Roles & Permissions](docs/ROLES-AND-PERMISSIONS.md)
- [Configuration](docs/CONFIGURATION.md)
- [Testing](docs/TESTING.md)
- [Troubleshooting](docs/TROUBLESHOOTING.md)
- [P2 Operations](docs/P2-OPERATIONS.md)
- [Production Hardening](docs/PRODUCTION-HARDENING.md)
- [Backup & Restore](docs/BACKUP-RESTORE.md)
- [Deployment Checklist](docs/DEPLOYMENT-CHECKLIST.md)
- [P7 Release Candidate](docs/P7-RELEASE-CANDIDATE.md)
- [Release Runbook](docs/RELEASE-RUNBOOK.md)
- [Rollback Runbook](docs/ROLLBACK-RUNBOOK.md)
- [ASVS Verification](docs/ASVS-VERIFICATION.md)
- [Load Testing](docs/LOAD-TESTING.md)
- [Upgrade Readiness](docs/P2-UPGRADE-READINESS.md)

## Release history

- `CHANGELOG-P0-P1.md` — authorization, private documents, data integrity, stability.
- `CHANGELOG-P2.md` — CI/CD, observability, queue/scheduler, backup/restore.
- `CHANGELOG-P3.md` — production hardening, regression tests, developer experience/docs.
- `CHANGELOG-P4.md` — modernization readiness.
- `CHANGELOG-P5.md` — UI/UX and accessibility baseline.
- `CHANGELOG-P6.md` — performance engineering.
- `CHANGELOG-P7.md` — release-candidate validation and operational proof gates.

## Aturan kontribusi

1. Jangan edit `vendor/` atau `node_modules/`.
2. Jangan commit `.env`, secret, runtime DB, log, cache, private documents, atau production dumps.
3. Perubahan business state harus punya regression test.
4. Perubahan permission harus punya negative authorization test.
5. Migration harus dapat berjalan dari fresh database dan upgrade database existing.
6. Jangan gabungkan framework major upgrade dengan perubahan business logic dalam satu merge request.

Lihat [Developer Workflow](docs/DEVELOPER-WORKFLOW.md).
