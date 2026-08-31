# Production Hardening

Baseline P3 menambah kontrol produksi tanpa mengubah business flow.

## 1. Browser security headers

Middleware:

```text
app/Http/Middleware/SecurityHeaders.php
```

Default:

```text
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
```

HSTS hanya dipasang untuk HTTPS request jika `SECURITY_HSTS_ENABLED=true`.

CSP sengaja tidak di-enforce default. Untuk Filament/Livewire, mulai report-only dan browser-test seluruh action/modal/upload sebelum enforce.

## 2. Rate limiting

Named rate limiters:

- `private-documents`;
- `generated-reports`;
- `health`.

Login/register tetap menggunakan rate limiter bawaan Filament.

## 3. Authentication & authorization logging

Operations log mencatat:

- login success;
- login failure tanpa menyimpan password/email/NIP mentah;
- logout;
- authorization denial;
- queue failures;
- queue busy threshold;
- scheduler outcome.

Jangan menambahkan request body sensitif ke log.

## 4. Error handling

Custom 403/404/429/500/503 pages menghindari detail exception/config tampil kepada end user.

Production wajib:

```env
APP_ENV=production
APP_DEBUG=false
```

## 5. Demo seeding

Demo accounts sekarang opt-in dan hanya diizinkan pada `local/testing`.

Production deploy tidak boleh bergantung pada `UserSeeder`.

## 6. Session & cookies

Di HTTPS production:

```env
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Gunakan TLS end-to-end atau pastikan trusted proxy configuration benar bila TLS terminasi di reverse proxy.

## 7. Secrets

Secret hanya melalui environment/secret manager:

```text
APP_KEY
DB_PASSWORD
MAIL credentials
external service tokens
```

Jangan simpan di repository, artifact ZIP, log, screenshot, atau database seed file.

## 8. Private files

Dokumen sensitif:

- di luar public webroot;
- path divalidasi;
- authorization per record;
- response download tidak bergantung pada URL storage publik.

Gap yang masih harus dipertimbangkan untuk threat model lebih tinggi:

- antivirus/malware scanning;
- CDR untuk PDF/DOCX;
- dedicated object storage/private bucket;
- content inspection pipeline.

## 9. Queue/scheduler

Production harus punya process manager dan monitoring. Worker timeout harus lebih kecil daripada queue `retry_after`.

Scheduler hanya membutuhkan satu trigger per menit, semua jadwal ada di source control.

## 10. Backup

Backup dianggap layak hanya setelah restore drill berhasil. Jangan restore drill ke production.

## 11. Remaining P3/P4 security roadmap

Belum dipaksakan di baseline ini:

- mandatory MFA untuk role privileged;
- enforced CSP;
- centralized SIEM/error tracker;
- malware scanning upload;
- automated secret scanning/SAST tambahan;
- full penetration test;
- Laravel/Vite major upgrade.

Alasan: kontrol tersebut perlu environment/infrastructure decision dan tidak boleh dipalsukan hanya di source code.
