# Upgrade Filament 4 → Filament 5

## Urutan

Filament 4 sudah mendukung Illuminate/Laravel 13, sehingga P4 sengaja melakukan Laravel 13 terlebih dahulu dan membuktikan backend tetap stabil sebelum menaikkan Filament.

Filament 5 membutuhkan:

- PHP 8.2+.
- Laravel 11.28+.
- Livewire 4+.
- Tailwind CSS 4+.

Project sudah menggunakan Tailwind 4 dan tidak memiliki dependency plugin Filament pihak ketiga di root `composer.json`.

## Custom Livewire surface

Application source tidak memiliki folder `app/Livewire` atau custom Livewire component langsung yang teridentifikasi pada audit P4. Mayoritas UI menggunakan Filament Resource/Page/Widget. Ini menurunkan risiko migrasi Livewire 4, tetapi tidak menghilangkan kebutuhan UAT browser.

## Official upgrade flow

```bash
composer require filament/upgrade:'^5.0' -W --dev
vendor/bin/filament-v5
```

Review output upgrade tool, lalu:

```bash
composer require filament/filament:'^5.0' -W --no-update
composer update --with-all-dependencies
```

Setelah selesai:

```bash
php artisan optimize:clear
php artisan filament:upgrade
php artisan test
npm run build
```

Package upgrade helper boleh dihapus setelah migration selesai dan commit sudah diverifikasi:

```bash
composer remove filament/upgrade --dev
```

## UAT wajib per panel

### Admin

- login/logout.
- dashboard role PIC / GM / SDM / Kepala Bagian / Pembimbing.
- Resource list/create/edit/view.
- custom Actions dan modal.
- policy denied state.
- table filters/search/pagination.

### Peserta

- login/register.
- profile.
- PKL/Penelitian submission.
- document upload/download.
- notifikasi.
- jadwal.
- perpanjangan.

### Security regression

- direct URL yang tidak berhak → 403.
- peserta A tidak dapat membuka data peserta B.
- pembimbing hanya scope assignment.
- private document tetap lewat controller terotorisasi.

## Referensi

- https://filamentphp.com/docs/5.x/upgrade-guide
- https://filamentphp.com/docs/5.x/introduction/version-support-policy
