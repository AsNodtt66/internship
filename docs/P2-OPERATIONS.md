# P2 Operations & Reliability

Dokumen ini menjelaskan baseline operasional setelah hardening P0/P1.

## Health checks

- `GET /up` — liveness Laravel. Menjawab apakah aplikasi berhasil bootstrap.
- `GET /health/ready` — readiness minimal. Menjalankan query `SELECT 1` dan hanya mengembalikan `ready` atau `unavailable`, tanpa detail error database.

Gunakan `/up` untuk liveness probe dan `/health/ready` untuk readiness/load-balancer check.

## Correlation / request ID

Semua HTTP request melewati `App\Http\Middleware\RequestId`.

- Header valid `X-Request-ID` dari reverse proxy dipertahankan.
- Nilai tidak aman/aneh diganti UUID baru.
- ID ditambahkan ke Laravel log context.
- Response selalu membawa `X-Request-ID`.
- Context dibersihkan setelah request agar tidak bocor pada proses long-lived.

Saat investigasi incident, cari request ID yang sama di reverse-proxy, application, dan operations logs.

## Logs

Log aplikasi tetap menggunakan channel utama Laravel. Operasional queue/scheduler menggunakan channel:

```text
storage/logs/operations-YYYY-MM-DD.log
```

Retention default 30 hari dan dapat diubah melalui:

```env
LOG_OPERATIONS_DAYS=30
LOG_OPERATIONS_LEVEL=info
```

Jangan log password, token, isi KTP/CV/BPJS, atau payload dokumen.

## Audit trail domain

Perubahan domain penting dicatat di tabel `audit_logs` oleh `DomainAuditObserver`.

Dicatat:

- actor ID bila tersedia;
- event (`created`, `updated`, `deleted`);
- model + record ID;
- request ID;
- source (`web`/`console`);
- perubahan field workflow yang sudah di-allowlist.

Tidak dicatat sebagai payload audit:

- file sensitif;
- password/token;
- alamat/profile peserta;
- isi dokumen.

Audit log bukan pengganti backup dan bukan application debug log.

## Queue

Default project menggunakan database queue. `QUEUE_AFTER_COMMIT=true` mencegah queued job/notification dilepas sebelum database transaction yang melahirkannya committed.

Contoh worker production tersedia di:

```text
ops/supervisor/internship-worker.conf.example
```

Worker menggunakan timeout 80 detik, sedangkan default `retry_after` database adalah 90 detik. Timeout harus selalu lebih pendek dari `retry_after` agar job tidak diproses ganda akibat worker timeout.

Saat deployment:

```bash
php artisan queue:restart
```

Process manager harus menyalakan worker kembali.

## Queue monitoring

Scheduler menjalankan:

```text
queue:monitor database:default --max=100
```

setiap menit. Konfigurasi:

```env
QUEUE_MONITOR_TARGET=database:default
QUEUE_MONITOR_MAX=100
```

Ketika threshold terlewati, event `QueueBusy` ditulis ke operations log.

Failed jobs dipangkas setelah 168 jam secara default:

```env
QUEUE_FAILED_RETENTION_HOURS=168
```

Sebelum mengurangi retention, pastikan alerting dan incident response sudah berjalan.

## Scheduler

Production harus menjalankan satu scheduler trigger per menit:

```cron
* * * * * cd /var/www/internship-management && php artisan schedule:run >> /dev/null 2>&1
```

Command reminder tetap `withoutOverlapping()` dan sekarang mencatat success/failure ke operations log.

## CI

`.gitlab-ci.yml` menyediakan:

1. PHP syntax + application bootstrap.
2. Backend tests pada PHP 8.3 dan PHP 8.4.
3. Migration-from-zero pada SQLite disposable database.
4. Pint.
5. Larastan stage transitional.
6. Frontend production build pada Node 22.16.
7. `composer audit`.
8. `npm audit`.

`npm audit` dan Larastan sementara `allow_failure` karena inherited Vite 5 dan static-analysis baseline belum direview. Keduanya harus dibuat blocking setelah dependency/tool baseline diselesaikan.
