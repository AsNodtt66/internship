# Configuration

`.env.example` adalah template. `.env` aktual tidak boleh masuk Git/ZIP source.

## Application

```env
APP_NAME="SI-PKL PG Krebet Baru"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
```

Production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
```

## Database

### SQLite local/testing

```env
DB_CONNECTION=sqlite
```

### MySQL/MariaDB

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internship_management
DB_USERNAME=app_user
DB_PASSWORD=<secret-manager-value>
```

Gunakan akun database aplikasi dengan hak minimum yang diperlukan. Jangan memakai root DB pada production.

## Session

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Production HTTPS sebaiknya:

```env
SESSION_SECURE_COOKIE=true
```

`SESSION_ENCRYPT=true` dapat dipertimbangkan setelah diuji pada deployment target. Jangan mengubah setting session tanpa regression test login/Livewire.

## Queue

```env
QUEUE_CONNECTION=database
QUEUE_AFTER_COMMIT=true
QUEUE_MONITOR_TARGET=database:default
QUEUE_MONITOR_MAX=100
QUEUE_FAILED_RETENTION_HOURS=168
```

## Cache

Local dapat menggunakan database/array sesuai kebutuhan. Production disarankan memakai backend persisten yang sesuai kapasitas, misalnya Redis atau database bila beban rendah.

`withoutOverlapping()` membutuhkan cache lock persisten pada production.

## Private documents

```env
PRIVATE_DOCUMENTS_DISK=documents
```

Jangan ubah disk sensitif menjadi public disk.

## Operations logging

```env
LOG_OPERATIONS_LEVEL=info
LOG_OPERATIONS_DAYS=30
```

## P3 security headers

```env
SECURITY_HEADERS_ENABLED=true
SECURITY_FRAME_OPTIONS=SAMEORIGIN
SECURITY_REFERRER_POLICY=strict-origin-when-cross-origin
SECURITY_PERMISSIONS_POLICY="camera=(), microphone=(), geolocation=()"
```

### HSTS

Default off:

```env
SECURITY_HSTS_ENABLED=false
```

Aktifkan hanya bila domain production **selalu HTTPS**:

```env
SECURITY_HSTS_ENABLED=true
SECURITY_HSTS_MAX_AGE=31536000
SECURITY_HSTS_INCLUDE_SUBDOMAINS=true
```

Jangan aktifkan `preload` tanpa review domain/subdomain karena dampaknya sulit dibatalkan dengan cepat.

### CSP

Mulai dari report-only:

```env
SECURITY_CSP_REPORT_ONLY="default-src 'self'; ..."
```

Setelah seluruh Filament/Livewire/browser flow tervalidasi, barulah pindahkan policy yang sama ke:

```env
SECURITY_CSP="..."
```

CSP kosong berarti header CSP tidak dipasang.

## Rate limits

```env
SECURITY_DOCUMENT_DOWNLOADS_PER_MINUTE=60
SECURITY_REPORTS_PER_MINUTE=30
SECURITY_HEALTH_PER_MINUTE=120
```

Sesuaikan berdasarkan traffic nyata. Nilai terlalu kecil dapat merusak UX; nilai terlalu besar kehilangan manfaat anti-abuse.

## Local demo seed

```env
SEED_DEMO_USERS=false
SEED_DEFAULT_PASSWORD=
```

Jika diaktifkan pada local/testing, password minimal 12 karakter. Demo-user seeder menolak production.

## P4 session serialization

Target Laravel 13 menggunakan:

```env
SESSION_SERIALIZATION=json
```

JSON mengurangi risiko PHP object deserialization. Mengubah deployment existing dari PHP serialization ke JSON akan mengakhiri session aktif; lakukan pada maintenance/release window dan komunikasikan bahwa user perlu login ulang.


## P6 performance observability

```env
PERFORMANCE_DB_WARN_MS=500
PERFORMANCE_REQUEST_WARN_MS=1500
PERFORMANCE_SERVER_TIMING=false
PERFORMANCE_PREVENT_LAZY_LOADING=false
```

Threshold hanya menghasilkan operational warning, bukan membatalkan request. `PERFORMANCE_SERVER_TIMING` sebaiknya dipakai sementara di staging untuk profiling browser. Lihat [P6-PERFORMANCE.md](P6-PERFORMANCE.md).
