# P2 Upgrade Readiness

## Versi baseline yang diwarisi

Lockfile baseline P2 saat dibuat:

```text
Laravel Framework : 12.64.0
Filament           : 4.12.1
PHPUnit            : 11.5.56
Laravel Pint       : 1.29.3
Vite               : 5.4.21
Tailwind CSS       : 4.3.3
```

## Runtime target

CI menguji PHP 8.4 dan PHP 8.5. Untuk production baru, PHP 8.4 adalah baseline konservatif; PHP 8.5 dapat dipakai setelah semua integration tests lulus pada infrastructure target.

`composer.json` masih mempertahankan `php: ^8.2` agar lockfile inherited tidak diubah secara manual. Naikkan constraint hanya melalui Composer pada branch upgrade dan commit lockfile hasil resolver.

## Laravel lifecycle

Per 31 Agustus 2026, Laravel 12 sudah melewati masa bug-fix reguler (13 Agustus 2026) tetapi masih mendapat security fixes sampai 24 Februari 2027. Karena itu baseline ini tetap aman untuk hardening jangka pendek, tetapi upgrade Laravel 13 harus masuk roadmap aktif.

## Laravel 13

Jangan mencampur upgrade Laravel 13 dengan perubahan domain/security. Gunakan branch khusus:

1. CI P2 harus hijau.
2. Buat branch upgrade.
3. Ikuti official upgrade guide dan ubah dependency melalui Composer.
4. Jalankan migration-from-zero, PHPUnit, Pint, Larastan, route/schedule bootstrap, serta smoke test Filament.
5. Review diff dependency lockfile.
6. Merge hanya bila behavior bisnis tidak berubah.

## Vite

Vite 5 sudah menjadi technical debt. Dokumentasi Vite saat baseline ini dibuat menyatakan branch yang didukung adalah 8.2 (regular patches), 7.3/8.1 (important + security fixes), dan 6.4/8.0 (security backports); semua versi sebelum itu tidak didukung. Jangan mengedit `package-lock.json` manual.

Upgrade melalui npm pada branch terpisah:

```bash
npm install --save-dev vite@latest laravel-vite-plugin@latest
npm run build
npm audit
```

Lalu lakukan browser smoke test pada kedua panel Filament dan landing page.

## Filament

Filament 4 masih dipertahankan di baseline ini. Policy support resmi menyatakan bug fixes 4.x sampai 15 Januari 2027 dan security fixes sampai 15 Januari 2028, jadi upgrade ini tidak lebih mendesak daripada Laravel/Vite. Upgrade Filament dilakukan terakhir setelah framework/runtime stabil karena perubahan major dapat menyentuh Livewire, resources, actions, forms, dan authorization integration.

## Larastan transitional setup

Inherited `composer.lock` belum mengandung Larastan. Agar source lockfile tidak dipalsukan, CI menggunakan:

```text
scripts/ci/install-larastan.sh
```

secara disposable dan stage Larastan belum blocking.

Target berikutnya:

1. jalankan Larastan di environment Composer yang memiliki network;
2. review findings;
3. generate baseline hanya untuk debt yang benar-benar diterima;
4. tambahkan `larastan/larastan` ke root `require-dev` dengan Composer;
5. commit `composer.json` + `composer.lock` bersama;
6. ubah job Larastan menjadi blocking.
