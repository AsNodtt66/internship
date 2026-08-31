# P8 — Automated Quality Gate & Playwright E2E

## Scope

P8 sengaja fokus pada **project testing dan CI green**. Deployment staging/production perusahaan berada di luar scope pipeline ini.

Quality layers:

```text
source audit
  -> PHPUnit fast suite (SQLite)
  -> MySQL integration suite
  -> Pint
  -> frontend build
  -> dependency audit
  -> Playwright Chromium
  -> Playwright Firefox/WebKit/mobile
  -> ci_green_gate
```

## Mengapa Playwright

Aplikasi adalah Laravel + Filament/Livewire. Playwright menjalankan browser nyata terhadap Laravel lokal melalui `webServer`, sehingga tidak membutuhkan staging URL.

Version Playwright dipin pada `.playwright-version`. Karena artifact source P8 tidak memalsukan `package-lock.json`, installer E2E memasang versi tersebut dengan `--no-save --package-lock=false`.

## Prasyarat lokal

- aplikasi dapat di-install dari `composer.lock` dan `package-lock.json`;
- database **khusus testing** tersedia;
- `APP_ENV=testing`;
- jangan pernah arahkan command reset E2E ke database berisi data nyata.

## Linux/macOS

```bash
cp .env.testing.example .env
composer install
npm ci
php artisan key:generate

# Atur DB_* ke database testing, contoh internship_testing.
export APP_ENV=testing
export DB_CONNECTION=mysql
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=internship_testing
export DB_USERNAME=root
export DB_PASSWORD='password-local-testing'

bash scripts/e2e/reset-test-db.sh
npm run build
bash scripts/e2e/install-playwright.sh
bash scripts/e2e/run.sh --project=chromium --grep @critical
```

Full browser matrix:

```bash
bash scripts/e2e/run.sh
```

## Windows PowerShell

```powershell
Copy-Item .env.example .env
composer install
npm ci
php artisan key:generate

$env:APP_ENV='testing'
# Isi DB_* testing di .env atau environment variables.
php artisan migrate:fresh --force
php artisan db:seed --class=TestingSeeder --force
npm run build

.\scripts\e2e\install-playwright.ps1
.\scripts\e2e\run.ps1 --project=chromium --grep '@critical'
```

> `TestingSeeder` menolak berjalan bila `APP_ENV` bukan `testing`.

## Test accounts

`TestingSeeder` membuat akun deterministic hanya pada database testing. Password fixture berada di source test dan **bukan credential production**.

Role yang dibuat:

- PIC;
- GM;
- Kabag SDM;
- Staff SDM;
- Kepala Bagian;
- Pembimbing Lapangan;
- Peserta A;
- Peserta B;
- satu akun inactive untuk negative login test.

Runtime IDs ditulis ke `storage/framework/testing/e2e-fixtures.json`. File ini di-ignore dan tidak boleh di-commit.

## Test groups

```text
e2e/
├── auth.setup.mjs              authenticated storage states
├── auth/                       login negative tests
├── public/                     landing/accessibility smoke
├── roles/                      dashboard per role
├── security/                   direct URL, IDOR, private documents
├── accessibility/              keyboard/responsive smoke
└── helpers/
```

Authenticated state ditulis ke `playwright/.auth/*.json`. Jangan commit folder ini karena berisi session cookies test.

## Perintah cepat

Sesudah Playwright terpasang:

```bash
npm run test:e2e:critical
npm run test:e2e:chromium
npm run test:e2e:cross-browser
npm run test:e2e
```

## CI Green Definition

Job `ci_green_gate` hanya berjalan bila seluruh mandatory job berikut berhasil:

```text
syntax_and_bootstrap
backend_tests PHP 8.4/8.5
migration_from_zero
mysql_integration
performance_regression
frontend_build
composer_audit
npm_audit
p8_release_source_audit
playwright_chromium
playwright_cross_browser
```

`larastan` masih informational sampai dependency Larastan benar-benar masuk `composer.lock`. Ia sengaja tidak dipakai untuk memberikan green palsu maupun memblokir P8 dengan dependency yang belum reproducible.

## Debugging Playwright

Pada kegagalan CI, periksa artifacts:

- `playwright-report/`;
- `test-results/playwright-junit.xml`;
- trace pada first retry;
- screenshot hanya saat failure;
- video retained hanya saat failure.

Untuk lokal:

```bash
npx playwright show-report
npx playwright test --project=chromium --headed
npx playwright test --project=chromium --debug
```

## Aturan menambah test

Setiap bug authorization/workflow yang diperbaiki harus meninggalkan regression test pada layer terendah yang tepat:

- Policy/service invariant -> PHPUnit;
- browser navigation/Livewire interaction -> Playwright;
- database-specific locking/FK/index -> MySQL integration;
- responsive/keyboard -> Playwright browser project.

Jangan mengganti backend security test dengan Playwright. Keduanya melindungi lapisan berbeda.
