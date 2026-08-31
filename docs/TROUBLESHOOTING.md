# Troubleshooting

## `composer doctor` gagal extension PHP

Contoh:

```text
PHP extension mbstring belum aktif
PHP extension dom belum aktif
```

Aktifkan extension pada `php.ini` atau install paket PHP OS yang sesuai. Pastikan command-line PHP memakai `php.ini` yang benar:

```bash
php --ini
php -m
```

## `could not find driver`

PDO tersedia tetapi driver DB belum aktif.

MySQL:

```text
pdo_mysql
```

SQLite:

```text
pdo_sqlite
sqlite3
```

## `APP_KEY` kosong / encryption error

```bash
php artisan key:generate
```

Jangan generate ulang `APP_KEY` sembarangan pada production yang sudah memiliki encrypted data/session.

## 419 Page Expired

Penyebab umum:

- session kedaluwarsa;
- cookie diblokir;
- domain/HTTPS cookie config salah;
- CSRF token stale setelah tab lama dibiarkan terbuka.

P3 mengarahkan browser kembali ke login panel yang benar (`admin` atau `peserta`). Jika error terus terjadi, cek:

```env
APP_URL
SESSION_DOMAIN
SESSION_SECURE_COOKIE
SESSION_SAME_SITE
```

## 403 setelah menambah Resource/Action

Karena `strictAuthorization()` aktif, resource tanpa policy/method authorization yang benar dapat ditolak.

Periksa:

```text
app/Policies/
```

Jangan mematikan strict authorization hanya untuk menghilangkan 403.

## 429 Too Many Requests

Endpoint private documents/PDF/health memiliki rate limiter. Nilai dapat dikonfigurasi di `.env`, tetapi naikkan hanya setelah memahami traffic dan abuse risk.

## Vite / asset tidak muncul

```bash
rm -rf node_modules
npm ci
npm run build
```

Jika `node_modules` berasal dari OS lain, selalu install ulang. Jangan memindahkan `node_modules` melalui ZIP antar-Windows/Linux.

## `vite: Permission denied` di Linux

Hapus dependency hasil copy dan install ulang:

```bash
rm -rf node_modules
npm ci
```

## Rollup optional dependency / native binary mismatch

Biasanya karena `node_modules` dibuat di platform lain. Solusi sama: hapus `node_modules` dan jalankan `npm ci` pada mesin target.

## `storage` / `bootstrap/cache` permission error

Linux contoh:

```bash
sudo chown -R <app-user>:<app-group> storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Jangan gunakan `chmod -R 777` sebagai solusi permanen.

## Scheduler list gagal karena cache DB belum siap

Untuk inspeksi source/local sebelum DB siap:

```bash
CACHE_STORE=array php artisan schedule:list
```

Production tetap gunakan cache persisten untuk lock `withoutOverlapping()`.

## Queue tidak memproses job terbaru setelah deploy

Worker adalah long-lived process:

```bash
php artisan queue:restart
```

Pastikan Supervisor/systemd menyalakan worker kembali.

## `queue:monitor` menunjukkan busy

Cek:

- jumlah worker;
- job lambat/error;
- DB/Redis latency;
- threshold `QUEUE_MONITOR_MAX`;
- `storage/logs/operations-*.log`.

## Private document 404

Periksa:

- database path value sesuai registry;
- `PRIVATE_DOCUMENTS_DISK`;
- file memang sudah dimigrasi dari legacy public disk;
- permission folder storage;
- user memiliki authorization terhadap record.

## Migration unique constraint gagal

Kemungkinan database lama memiliki duplicate record yang melanggar invariant baru. Jangan menghapus data otomatis. Identifikasi duplicate, tentukan record canonical bersama business owner, backup, rekonsiliasi, lalu jalankan migration kembali.

---

## `php artisan release:check --strict` gagal

Ini normal jika project masih memakai lockfile baseline Laravel 12 / Filament 4 / Vite 5. Jalankan P4 modernization pada environment online terlebih dahulu dan commit lockfile hasil resolver Composer/npm yang sebenarnya.

Strict mode juga akan gagal jika staging masih menggunakan salah satu berikut:

```text
APP_DEBUG=true
APP_URL=http://...
SESSION_SECURE_COOKIE=false
QUEUE_CONNECTION=sync
pending migration
private documents disk salah konfigurasi
```

Jangan “memperbaiki” gate dengan menurunkan requirement hanya agar status hijau. Perbaiki environment/root cause atau dokumentasikan waiver dengan alasan dan reviewer.
