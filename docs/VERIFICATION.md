# Verification

## Quick verification

```bash
php scripts/verify.php
```

Mencakup:

- syntax PHP source;
- authorization policy smoke checks;
- workflow method-contract checks;
- Laravel route bootstrap;
- Laravel scheduler bootstrap.

Quick verification menggunakan cache `array` untuk inspeksi scheduler agar source-only checkout tidak membutuhkan database cache yang sudah dimigrasi.

## Environment diagnostics

```bash
composer doctor
# atau bila Composer script belum dapat dipanggil:
php scripts/dev/doctor.php
```

Doctor memeriksa versi PHP, extension penting, PDO driver, file dependency manifests, permission runtime directories, dan tooling Node/npm/Composer bila dapat dideteksi.

## Full development verification

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate

php artisan config:clear
php artisan test
php vendor/bin/pint --test
npm run build
composer audit --locked
npm audit --audit-level=high
```

## Migration-from-zero

Gunakan database testing disposable saja:

```bash
php artisan migrate:fresh --env=testing --force
php artisan migrate:status --env=testing
php artisan test
```

`migrate:fresh` menghapus seluruh tabel pada koneksi aktif. Jangan pernah menjalankannya ke production.

## Static analysis

P2 menyediakan `phpstan.neon.dist`. Inherited `composer.lock` belum memiliki Larastan, jadi jangan hand-edit lockfile.

Pada disposable CI/branch:

```bash
bash scripts/ci/install-larastan.sh
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G
```

Setelah findings direview, tambahkan Larastan ke root `require-dev` menggunakan Composer dan commit `composer.json` + `composer.lock` bersama.

## P3 regression coverage

P3 menambahkan tests untuk:

- browser security headers;
- HTTPS-only HSTS;
- CSP report-only rollout;
- readiness endpoint rate limiting;
- draft submission state transition;
- duplicate submission rejection;
- PIC-only document verification;
- required rejection reason;
- exact four-step approval creation;
- out-of-order approval rejection.

## CI

`.gitlab-ci.yml` mengotomasi:

- environment doctor + PHP syntax/application bootstrap;
- PHP 8.4 / PHP 8.5 test matrix;
- Pint;
- migration-from-zero;
- route/scheduler bootstrap;
- Larastan transitional analysis;
- Node 22 frontend build;
- Composer security audit;
- npm security audit.

## P3 artifact-build verification yang berhasil di sandbox

```text
PASS 242 PHP files syntax check
PASS Laravel application bootstrap
PASS route bootstrap
PASS scheduler bootstrap (3 scheduled tasks)
PASS policy smoke checks
PASS workflow contract smoke checks
PASS GitLab CI YAML parse
PASS shell script syntax
PASS local Markdown link validation
```

## Checks yang membutuhkan release workstation/CI lengkap

Sandbox build tidak memiliki PHP extensions `dom`, `mbstring`, dan `xmlwriter`, serta tidak memiliki Composer CLI/network resolver. Source-only checkout juga tidak membawa `node_modules`.

Karena itu pada sandbox:

```text
BLOCKED full PHPUnit
BLOCKED Pint
BLOCKED migration-from-zero via PHPUnit environment
BLOCKED Larastan dependency resolution
BLOCKED Composer audit
BLOCKED clean npm build without dependency installation
```

PHPUnit/Pint berhenti karena extension runtime yang hilang, bukan karena assertion test yang gagal. CI disediakan untuk membuktikan checks tersebut pada environment lengkap.

## Release checks

Source artifact final tidak boleh memuat:

```text
.env
vendor/
node_modules/
database/database.sqlite
public/build/
private documents
logs/cache/session runtime
*.lnk
production backup/dump
```

## P4 modernization verification — 31 Agustus 2026

Source-level verification pada artifact P4:

```text
PASS 243 PHP files syntax
PASS P4 compatibility scan
PASS authorization policy smoke
PASS workflow contract smoke
PASS Laravel 12 bootstrap after dual-version source changes
PASS 62 application routes
PASS scheduler bootstrap (3 tasks)
PASS vite.config.js JavaScript syntax
PASS GitLab CI YAML parse
PASS documentation internal-link scan
```

Dependency-major resolution belum dapat dijalankan di audit container karena container tidak mempunyai akses DNS ke Packagist/npm registry (`EAI_AGAIN`) dan Composer CLI tidak tersedia. Karena itu lockfile P3 sengaja dipertahankan; dependency major tidak boleh dianggap selesai sampai `scripts/upgrade/p4/modernize.*` berhasil pada online workstation/CI dan menghasilkan lockfile valid.

Full PHPUnit/Pint di audit container juga masih diblokir extension PHP `dom`, `mbstring`, `xml`, dan `xmlwriter`. CI project menginstal extension tersebut dan tetap menjadi source of truth untuk full regression suite.
