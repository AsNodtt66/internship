# P4 Modernization — Laravel 13, Vite 8, Filament 5

Dokumen ini adalah entry point upgrade dependency setelah baseline P0–P3 stabil.

## Status artifact ini

Source code sudah diberi **compatibility hardening** untuk Laravel 13 dan sudah dapat tetap bootstrap pada Laravel 12. Dependency utama di `composer.lock` / `package-lock.json` sengaja **belum dipalsukan** pada artifact source-only ini karena lockfile harus dihasilkan oleh Composer/npm resolver yang mempunyai akses Packagist/npm registry.

Artinya ada dua keadaan yang jelas:

1. **Sebelum menjalankan modernizer** — install tetap reproducible dengan Laravel 12 + Filament 4 + Vite 5 sesuai lockfile P3.
2. **Sesudah menjalankan modernizer pada mesin online** — manifest dan lockfile harus berpindah ke PHP 8.4+, Laravel 13, Vite 8, Filament 5/Livewire 4 dan seluruh quality gate harus hijau.

Jangan mengedit versi di lockfile secara manual.

## Target P4

| Layer | Baseline P3 | Target P4 |
|---|---|---|
| PHP | 8.2+, CI 8.4/8.5 | **8.4+**, 8.5 direkomendasikan |
| Laravel | 12.x | **13.x** |
| Filament | 4.x | **5.x** |
| Livewire | 3.x transitif | **4.x** transitif Filament 5 |
| Vite | 5.x | **8.x** |
| Laravel Vite Plugin | 1.x | **3.x** |
| PHPUnit | 11.x | **12.x** |

## Requirement workstation

- PHP 8.4 atau 8.5.
- Composer 2.x.
- Node.js **22.12+** direkomendasikan. Vite 8 juga mendukung Node 20.19+.
- npm.
- Git working tree bersih.
- koneksi ke Packagist dan npm registry.
- PHP extensions dari `docs/QUICK-START.md`.

Periksa terlebih dahulu:

```bash
composer doctor
php scripts/upgrade/p4/compatibility-check.php
```

## Jalur otomatis

### Linux / macOS

```bash
bash scripts/upgrade/p4/modernize.sh
```

### Windows PowerShell

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\upgrade\p4\modernize.ps1
```

Script berhenti pada error dan membuat backup manifest:

```text
composer.pre-p4.json
composer.pre-p4.lock
package.pre-p4.json
package.pre-p4.lock
```

Backup tersebut hanya safety net lokal dan jangan di-commit.

## Tahap yang dijalankan

```text
Compatibility scan
      ↓
PHP 8.4 baseline
      ↓
Laravel 13 + PHPUnit 12
      ↓
backend test + Pint
      ↓
Vite 8 + laravel-vite-plugin 3
      ↓
frontend production build
      ↓
Filament 5 upgrade tool
      ↓
Livewire 4 via dependency resolver
      ↓
full regression tests
      ↓
dependency audits
      ↓
P4 compatibility verification
```

Detail masing-masing upgrade:

- [P4-LARAVEL-13.md](P4-LARAVEL-13.md)
- [P4-VITE-8.md](P4-VITE-8.md)
- [P4-FILAMENT-5.md](P4-FILAMENT-5.md)

## Source compatibility yang sudah diterapkan

### Request forgery middleware

Laravel 13 mengganti `VerifyCsrfToken` menjadi `PreventRequestForgery`. Panel memakai fallback dual-version selama fase transisi sehingga source dapat bootstrap pada Laravel 12 dan 13.

### QueueBusy event

Laravel 13 mengganti property `QueueBusy::$connection` menjadi `$connectionName`. Listener operational log membaca property baru bila tersedia dan fallback ke property Laravel 12.

### Cache unserialization

`config/cache.php` sekarang menetapkan:

```php
'serializable_classes' => false,
```

agar Laravel 13 tidak meng-unserialize PHP objects dari cache tanpa allow-list eksplisit.

### Session serialization

`config/session.php` menargetkan:

```env
SESSION_SERIALIZATION=json
```

Perubahan dari PHP serialization ke JSON akan mengakhiri session user yang sedang aktif saat pertama kali Laravel 13 deployment menggunakan setting tersebut. Jadwalkan sebagai deployment event yang disengaja.

## Verifikasi setelah upgrade

```bash
php scripts/upgrade/p4/compatibility-check.php --expect-modern
php artisan optimize:clear
php artisan migrate:fresh --env=testing
php artisan test
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --memory-limit=1G
npm run build
composer audit --locked
npm audit --audit-level=high
```

Lalu lakukan UAT browser minimal:

- `/admin` login/logout.
- `/peserta` login/register/profile.
- create/edit pengajuan.
- upload + authorized download private document.
- approval berurutan.
- penugasan pembimbing.
- evaluasi/penilaian.
- perpanjangan.
- PDF/surat.
- dashboard per role.
- error 403/419/429.

## Rollback

Jangan rollback database dengan `migrate:fresh`.

Jika upgrade dependency gagal sebelum deploy:

```bash
git restore composer.json composer.lock package.json package-lock.json
rm -rf vendor node_modules
composer install
npm ci
```

Jika sudah deploy, rollback harus mengikuti release deployment runbook dan database compatibility review. Lihat `docs/DEPLOYMENT-CHECKLIST.md`.

## Referensi resmi

- Laravel 13 Upgrade Guide: https://laravel.com/docs/13.x/upgrade
- Laravel 13 skeleton composer.json: https://github.com/laravel/laravel/blob/13.x/composer.json
- Filament 5 Upgrade Guide: https://filamentphp.com/docs/5.x/upgrade-guide
- Vite supported versions: https://vite.dev/releases
- Vite 8 migration: https://vite.dev/guide/migration
- PHP supported versions: https://www.php.net/supported-versions.php
