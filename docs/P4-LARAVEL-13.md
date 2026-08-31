# Upgrade Laravel 12 → Laravel 13

## Target

P4 menargetkan Laravel `^13.17`, PHP `^8.4`, Tinker `^3.0`, dan PHPUnit `^12.5.12`.

Laravel 13 resmi menyebut dependency utama yang perlu dinaikkan sebagai `laravel/framework ^13.0`, `laravel/tinker ^3.0`, dan `phpunit/phpunit ^12.0`.

## Project-specific impact audit

### High impact: request forgery protection

Project sebelumnya mereferensikan `VerifyCsrfToken` pada kedua Filament panel. Laravel 13 mengganti middleware utama menjadi `PreventRequestForgery` dan menambah origin verification melalui `Sec-Fetch-Site`.

Source P4 sudah menggunakan dual-version fallback selama transisi.

Setelah semua environment permanen berada pada Laravel 13 dan P3 rollback window ditutup, fallback `VerifyCsrfToken` boleh dibersihkan pada change terpisah.

### Medium: cache serialized classes

Project tidak mempunyai kebutuhan yang teridentifikasi untuk menyimpan arbitrary PHP object dalam cache. Karena itu:

```php
'serializable_classes' => false,
```

adalah default yang dipilih.

Jika nanti ada use-case object cache, gunakan allow-list class eksplisit. Jangan mengganti menjadi `true` secara global.

### Medium: database upsert

Laravel 13 mewajibkan `uniqueBy` tidak kosong pada `upsert()`. Compatibility scan source saat P4 tidak menemukan pemakaian `upsert()` yang terkena perubahan ini.

### Low: QueueBusy event

Operational logging sudah dual-compatible terhadap `$connection` (Laravel 12) dan `$connectionName` (Laravel 13).

### Low: session serialization

Target P4 memakai JSON. Konsekuensi deployment: session aktif lama dapat invalid dan user perlu login ulang.

### PHP 8.5 polyfill helpers

Audit tidak menemukan custom helper `array_first()` / `array_last()` di application source. Jika helper seperti itu ditambahkan kemudian, gunakan `Illuminate\Support\Arr` untuk menghindari konflik fungsi global.

## Upgrade commands manual

```bash
composer require php:'^8.4' \
  laravel/framework:'^13.17' \
  laravel/tinker:'^3.0' \
  barryvdh/laravel-dompdf:'^3.1.2' \
  --no-update

composer require --dev \
  phpunit/phpunit:'^12.5.12' \
  laravel/pint:'^1.27' \
  --no-update

composer config minimum-stability stable
composer config prefer-stable true
composer update --with-all-dependencies
```

`barryvdh/laravel-dompdf` 3.1.2 dipilih karena release tersebut secara eksplisit menambahkan Laravel 13 compatibility dan pengujian PHP 8.5.

## Verification gate

```bash
php artisan --version
php artisan optimize:clear
php artisan route:list --except-vendor
CACHE_STORE=array php artisan schedule:list
php artisan test
php vendor/bin/pint --test
composer audit --locked
```

Jangan lanjut ke Filament 5 jika gate ini gagal.
