# Quick Start


> **P4:** untuk menjalankan modernisasi dependency, gunakan PHP 8.4/8.5 dan Node 22.12+ lalu ikuti [P4 Modernization](P4-MODERNIZATION.md). Baseline P3 tetap dapat di-install dari lockfile yang disertakan.

Tujuan dokumen ini: membawa developer dari source code bersih sampai aplikasi berjalan tanpa perlu mengetahui seluruh arsitektur terlebih dahulu.

## 1. Requirement minimum

```text
PHP       >= 8.2 (8.4 direkomendasikan)
Composer  2.x
Node.js   22.x
npm       sesuai Node 22
Database  SQLite local/test atau MySQL/MariaDB
```

Cek otomatis:

```bash
composer doctor
```

Jika Composer belum tersedia, jalankan langsung:

```bash
php scripts/dev/doctor.php
```

## 2A. Setup otomatis Linux/macOS

```bash
bash scripts/dev/quick-start.sh
```

## 2B. Setup otomatis Windows PowerShell

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\dev\quick-start.ps1
```

Script melakukan:

1. membuat `.env` dari `.env.example` bila belum ada;
2. `composer install`;
3. generate `APP_KEY`;
4. membuat SQLite file bila konfigurasi memilih SQLite;
5. migration;
6. seed master roles/bagian;
7. `npm ci`;
8. production asset build.

Script **tidak** membuat demo users secara default.

## 3. Setup manual

```bash
cp .env.example .env
composer install
php artisan key:generate
```

### SQLite local

`.env`:

```env
DB_CONNECTION=sqlite
```

Linux/macOS:

```bash
mkdir -p database
touch database/database.sqlite
```

PowerShell:

```powershell
New-Item -ItemType File database/database.sqlite -Force
```

Kemudian:

```bash
php artisan migrate
php artisan db:seed
npm ci
npm run build
```

### MySQL local

Buat database kosong, contoh `internship_management`, lalu `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internship_management
DB_USERNAME=root
DB_PASSWORD=your-local-password
```

Kemudian:

```bash
php artisan migrate
php artisan db:seed
npm ci
npm run build
```

## 4. Jalankan development

Paling mudah:

```bash
composer dev
```

Atau terminal terpisah:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

Buka:

```text
http://127.0.0.1:8000/
http://127.0.0.1:8000/peserta
http://127.0.0.1:8000/admin
```

## 5. Demo users lokal (opsional)

Jangan gunakan akun demo pada production.

Isi `.env`:

```env
SEED_DEMO_USERS=true
SEED_DEFAULT_PASSWORD="<buat-password-demo-minimal-12-karakter>"
```

Password minimal 12 karakter. Lalu:

```bash
php artisan db:seed
```

Seeder demo hanya diizinkan pada `local` atau `testing`.

## 6. Verifikasi setup

```bash
composer verify:quick
php artisan route:list --except-vendor
CACHE_STORE=array php artisan schedule:list
```

Full test:

```bash
php artisan test
php vendor/bin/pint --test
npm run build
```

## 7. Reset database local/testing

Hanya untuk database disposable:

```bash
php artisan migrate:fresh --seed
```

Jangan jalankan command tersebut pada database yang memiliki data nyata.

---

## Release-readiness commands

Developer/source check:

```bash
composer verify:release
php artisan release:check
```

P7 strict release mode bukan requirement P8. Untuk fase saat ini, target utama adalah seluruh mandatory testing job sampai `ci_green_gate` hijau.

## 8. Jalankan project testing dengan Playwright

Gunakan database khusus testing. Jangan gunakan database development yang berisi data penting.

Linux/macOS:

```bash
export APP_ENV=testing
# isi DB_* ke database disposable
bash scripts/e2e/reset-test-db.sh
npm run build
bash scripts/e2e/install-playwright.sh chromium
bash scripts/e2e/run.sh --project=chromium --grep @critical
```

Untuk semua browser:

```bash
bash scripts/e2e/install-playwright.sh
bash scripts/e2e/run.sh
```

Windows PowerShell:

```powershell
$env:APP_ENV='testing'
php artisan migrate:fresh --force
php artisan db:seed --class=TestingSeeder --force
npm run build
.\scripts\e2e\install-playwright.ps1
.\scripts\e2e\run.ps1 --project=chromium --grep '@critical'
```

Lihat [P8 Playwright & CI](P8-PLAYWRIGHT-CI.md).
