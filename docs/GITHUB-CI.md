# GitHub Actions CI

Project testing on GitHub is driven by `.github/workflows/ci.yml`. No staging or production deployment is performed by this workflow.

## Mandatory gates

1. PHP syntax, Pint, project source audits, and `composer audit`.
2. PHPUnit on PHP 8.4 and PHP 8.5 using SQLite for the fast suite.
3. MySQL 8.4 migration-from-zero, performance/index verification, and the PHPUnit suite.
4. `npm ci`, production Vite build, and `npm audit --audit-level=high`.
5. PHP coverage dengan PCOV dan artefak Clover, HTML, serta teks.
6. Infection mutation testing untuk policy, authorization support, document support, dan service workflow.
7. Playwright Chromium menggunakan database MySQL test yang deterministik, termasuk flake check `@critical`, Axe, dan visual regression.
8. Playwright Firefox, WebKit, dan mobile Chromium.
9. `CI Green Gate`, yang gagal bila satu gate wajib pun gagal.

## Playwright in CI

Playwright tercantum sebagai development dependency dan dikunci oleh `package-lock.json`. CI hanya menjalankan `npm ci`; tidak ada instalasi package ad-hoc setelah lockfile diverifikasi.

CI keeps workers at one through `playwright.config.mjs`, stores traces on first retry, screenshots only on failure, failure videos, JUnit output, and the HTML report.

## Testing accounts

`Database\\Seeders\\TestingSeeder` is testing-only and refuses to run outside `APP_ENV=testing`. It writes runtime fixture IDs into `storage/framework/testing/e2e-fixtures.json`; Playwright tests do not hard-code database primary keys.

## Local equivalent

```bash
cp .env.testing.example .env
php artisan key:generate --force
php artisan migrate:fresh --force
php artisan db:seed --class=TestingSeeder --force
npm ci
npm run build
npx playwright install --with-deps
npx playwright test
```

Use a disposable testing database only. Never run `migrate:fresh` against company or production data.
