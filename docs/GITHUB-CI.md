# GitHub Actions CI

Project testing on GitHub is driven by `.github/workflows/ci.yml`. No staging or production deployment is performed by this workflow.

## Mandatory gates

1. PHP syntax, Pint, project source audits, and `composer audit`.
2. PHPUnit on PHP 8.4 and PHP 8.5 using SQLite for the fast suite.
3. MySQL 8.4 migration-from-zero, performance/index verification, and the PHPUnit suite.
4. `npm ci`, production Vite build, and `npm audit --audit-level=high`.
5. Playwright Chromium using a deterministic MySQL testing database.
6. Playwright Firefox, WebKit, and mobile Chromium.
7. `CI Green Gate`, which fails unless every mandatory job succeeds.

## Playwright in CI

The repository intentionally pins the Playwright runner version in `.playwright-version`. Because Playwright was introduced while package registry access was unavailable in the build workspace, CI installs that exact version with `npm install --no-save` after `npm ci`. Once dependency modernization is performed on an online developer machine, move `@playwright/test` into `devDependencies` and commit the regenerated `package-lock.json`.

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
npm install --no-save --ignore-scripts @playwright/test@$(cat .playwright-version)
npm run build
npx playwright install --with-deps
npx playwright test
```

Use a disposable testing database only. Never run `migrate:fresh` against company or production data.
