# Local Development


> **P4:** gunakan PHP 8.3/8.4 dan Node 22.12+ untuk lockfile saat ini. Ikuti [P4 Modernization](P4-MODERNIZATION.md) hanya setelah dependency resolver menghasilkan graph yang mendukung target baru.

## Development command

```bash
composer dev
```

Menjalankan empat proses:

- Laravel development server;
- queue listener;
- Laravel Pail/log viewer;
- Vite development server.

Jika salah satu tooling tidak tersedia, jalankan proses secara terpisah.

## Workflow perubahan kode

```text
1. Pull branch terbaru
2. composer install (jika composer.lock berubah)
3. npm ci (jika package-lock.json berubah)
4. php artisan migrate (jika migration baru)
5. implementasi kecil
6. test terkait
7. composer verify:quick
8. full test/build sebelum push
```

## Setelah mengubah config/.env

```bash
php artisan config:clear
```

## Setelah mengubah route

```bash
php artisan route:clear
php artisan route:list --except-vendor
```

## Setelah mengubah Filament/Blade/CSS

Development:

```bash
npm run dev
```

Production check:

```bash
npm run build
```

## Setelah mengubah queue job/listener

Local worker harus direstart agar source baru dipakai. Production:

```bash
php artisan queue:restart
```

## Database

Migration baru harus:

- reversible bila realistis;
- tidak menghapus data existing diam-diam;
- memiliki constraint sesuai invariant domain;
- diuji pada fresh database;
- diuji terhadap upgrade database existing bila migration melakukan transformasi.

## File storage

Dokumen sensitif menggunakan disk `documents`. Jangan membuat symlink public untuk disk tersebut.

Untuk legacy deployment:

```bash
php artisan documents:migrate-private
```

Backup dan validasi terlebih dahulu.
