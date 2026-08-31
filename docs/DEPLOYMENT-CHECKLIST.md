# Deployment Checklist

Sebelum production:

- [ ] `.env` dibuat di server, bukan dari repository.
- [ ] `APP_ENV=production` dan `APP_DEBUG=false`.
- [ ] database backup dibuat dan restore drill pernah diuji.
- [ ] duplicate data direkonsiliasi sebelum migration unique constraints.
- [ ] `php artisan migrate --force` berhasil.
- [ ] private document disk writable oleh application user dan tidak berada di public webroot.
- [ ] migrasi legacy `documents:migrate-private` sudah diverifikasi bila diperlukan.
- [ ] scheduler aktif dan `php artisan schedule:list` sesuai.
- [ ] queue worker/supervisor aktif bila queue digunakan.
- [ ] `php artisan test`, Pint, dan frontend build lulus di CI/release environment.
- [ ] health/error/log monitoring aktif.


## P2 operational gates

- [ ] `/up` liveness dan `/health/ready` readiness dipantau dari luar host.
- [ ] Reverse proxy mempertahankan atau menghasilkan `X-Request-ID`.
- [ ] `operations` logs dikirim/dirotasi sesuai kebijakan.
- [ ] migration `audit_logs` sudah berjalan dan storage growth dimonitor.
- [ ] queue worker menggunakan process manager dan `--timeout` lebih kecil dari `retry_after`.
- [ ] `QUEUE_AFTER_COMMIT=true` kecuali ada alasan teruji untuk menonaktifkannya.
- [ ] `queue:monitor` threshold sesuai kapasitas.
- [ ] restore drill terakhir terdokumentasi dan berhasil.
- [ ] CI backend PHP 8.4/8.5, migration-from-zero, Pint, dan frontend build hijau.
- [ ] Larastan/npm audit findings sudah ditinjau sebelum mengubah stage menjadi blocking.

## P3 security & release gates

- [ ] `APP_DEBUG=false` diverifikasi dari runtime production.
- [ ] `SESSION_SECURE_COOKIE=true` bila production memakai HTTPS (seharusnya ya).
- [ ] Security headers terlihat dari external probe/browser.
- [ ] HSTS hanya diaktifkan setelah semua domain/subdomain target benar-benar HTTPS.
- [ ] CSP dimulai report-only dan tidak di-enforce sebelum Filament/Livewire smoke test lulus.
- [ ] Rate limit dokumen/report sesuai traffic aktual dan 429 dimonitor.
- [ ] Demo seeding tidak aktif (`SEED_DEMO_USERS=false`).
- [ ] Authentication/authorization failure masuk operations logging tanpa raw credential.
- [ ] Custom error pages tidak menampilkan stack trace/config.
- [ ] `composer doctor`, PHPUnit, Pint, migration-from-zero, frontend build, dan dependency audits hijau di release environment.
- [ ] Private document migration/permission diverifikasi dari akun dengan scope berbeda.
- [ ] Rollback plan untuk code + database migration terdokumentasi sebelum deploy.

## P4 / Laravel 13 deployment note

Jika release pertama yang mengaktifkan `SESSION_SERIALIZATION=json` sedang dideploy:

- anggap semua session aktif dapat invalid;
- jangan mengubah setting ini diam-diam di tengah jam kerja kritis;
- restart queue worker setelah dependency deploy;
- jalankan `php artisan optimize:clear` sebelum `php artisan optimize`;
- lakukan smoke login `/admin` dan `/peserta`;
- verifikasi private document authorization dan workflow approval.


## P6 post-migration performance check

Setelah `php artisan migrate --force`:

```bash
php artisan performance:check
```

Pantau `operations.log` untuk `performance.slow_request` dan `performance.slow_database_request`. Jangan menurunkan threshold hanya untuk menghilangkan warning; gunakan profiling/EXPLAIN untuk menemukan akar masalah.

---

## P7 promotion gate

Before production promotion, attach evidence for all of the following:

```text
[ ] normal CI green
[ ] P4 dependency modernization committed with real lockfiles
[ ] php artisan release:check --strict PASS on staging
[ ] full PHPUnit / Pint / PHPStan PASS
[ ] composer audit / npm audit reviewed
[ ] npm production build PASS
[ ] k6 staging smoke PASS
[ ] private-document authorization regression PASS
[ ] backup -> isolated restore drill PASS
[ ] queue + scheduler verified
[ ] browser/mobile/accessibility matrix completed
[ ] rollback rehearsal completed
[ ] no unresolved Critical/High issue
```

Canonical operational sequence: `RELEASE-RUNBOOK.md`. Rollback: `ROLLBACK-RUNBOOK.md`.
