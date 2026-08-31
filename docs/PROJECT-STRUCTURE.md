# Project Structure

## Root

```text
app/                Laravel application code
bootstrap/          application bootstrap/middleware/exception config
config/             runtime configuration
database/           migrations, seeders, factories
docs/               developer & operations documentation
ops/                production operational examples/scripts
public/             web root
resources/          Blade views and CSS source
routes/             HTTP + console/scheduler routes
scripts/            development/verification helpers
storage/            runtime logs/cache/private local files (not source data)
tests/              PHPUnit tests
```

## `app/Filament`

Server-driven UI untuk panel internal dan peserta.

```text
app/Filament/
├── Pages/                   custom internal pages
├── Resources/               admin CRUD/workflow resources
├── Widgets/                 dashboard widgets
└── Peserta/
    ├── Pages/
    ├── Resources/
    └── Widgets/
```

## `app/Policies`

Authorization server-side. Bila resource/action baru menyentuh model sensitif, policy harus tersedia sebelum mengandalkan visibility UI.

## `app/Services`

Business/application orchestration. `PengajuanWorkflowService` dipertahankan sebagai façade agar UI tidak mengetahui detail persistence dan transition.

## `app/Support`

Concern lintas layer yang bukan business service penuh:

```text
Authorization/PengajuanAccess.php
Documents/PrivateDocumentRegistry.php
```

## `app/Observers`

Audit perubahan domain yang aman. Observer bukan tempat business workflow utama.

## `resources`

```text
resources/css/filament/admin/theme.css
resources/views/landing.blade.php
resources/views/errors/
resources/views/filament/
```

## `ops`

File di sini adalah contoh/runbook untuk operator server, bukan runtime dependency aplikasi.

## Yang tidak boleh menjadi source

```text
.env
vendor/
node_modules/
public/build/
storage/logs/*
private uploaded documents
production database dump
database/database.sqlite runtime copy
```


## UI support

```text
app/Support/Ui/
└── PengajuanStatusPresenter.php  # label, description, dan tone status lintas panel
```

## P6 performance components

```text
app/Http/Middleware/RequestPerformance.php     slow request / Server-Timing
app/Console/Commands/PerformanceCheck.php      index/config verification
config/performance.php                         thresholds
database/migrations/*performance_indexes.php   hot-path composite indexes
scripts/performance-audit.php                  source-level regression guard
tests/Feature/Performance/                     query-count regression tests
```
