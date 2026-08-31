# P6 — Performance Engineering

P6 mengoptimalkan hot path yang sudah terbukti dari source audit tanpa mengubah aturan bisnis, authorization, atau hasil workflow.

## Ringkasan perubahan

### 1. Polling dashboard dikurangi

Filament `StatsOverviewWidget` dan `ChartWidget` melakukan refresh periodik secara default. Widget statistik/chart aplikasi ini tidak membutuhkan data real-time per 5 detik, sehingga polling dinonaktifkan dan data direfresh saat navigasi/re-render yang memang diperlukan.

Dampak: dashboard yang dibiarkan terbuka tidak terus menerus menembakkan query agregasi ke database.

### 2. Agregasi dipindahkan ke SQL

Sebelumnya beberapa widget mengambil seluruh `pengajuans` beserta relationship lalu melakukan `groupBy()` / `count()` di PHP. P6 menggantinya dengan `COUNT`, `SUM(CASE ...)`, `GROUP BY`, join, dan satu aggregate query.

Hot path yang diperbaiki:

- distribusi pengajuan per bagian;
- top universitas;
- grafik pengajuan bulanan;
- statistik GM;
- funnel workflow;
- statistik PIC/Pembimbing/Kepala Bagian;
- hitung tahap approval aktif.

### 3. N+1 pada Tugas Saya dihapus

Resolver lama melakukan query tambahan untuk setiap kandidat `ApprovalWorkflow`. P6 menggunakan `whereHas()` + `whereDoesntHave()` sehingga database menentukan tahap approval aktif dalam query utama.

### 4. Index hot-path

Migration:

```text
database/migrations/2026_08_31_160000_add_performance_indexes.php
```

menambahkan index terukur untuk filter/sort yang sering digunakan:

```text
pengajuans(status, created_at)
pengajuans(status, tanggal_selesai)
pengajuans(bagian_tujuan_id, status)
pengajuans(peserta_id, created_at)
approval_workflows(status, urutan, pengajuan_id)
riwayat_status(created_at)
riwayat_status(pengajuan_id, created_at)
notifikasis(user_id, created_at)
perpanjangans(pengajuan_id, status, created_at)
dokumen_persyaratans(pengajuan_id, jenis_dokumen)
dokumen_persyaratans(pengajuan_id, status_verifikasi)
```

Index tidak ditambah secara membabi buta. Setiap index mempunyai write/storage cost, jadi gunakan `EXPLAIN` pada data produksi/staging sebelum menambah index baru.

## Performance observability

Environment variables:

```env
PERFORMANCE_DB_WARN_MS=500
PERFORMANCE_REQUEST_WARN_MS=1500
PERFORMANCE_SERVER_TIMING=false
PERFORMANCE_PREVENT_LAZY_LOADING=false
```

`PERFORMANCE_DB_WARN_MS` mencatat warning jika total waktu database pada satu request melewati threshold. Binding query tidak dicatat agar PII peserta tidak disalin ke log operasi.

`PERFORMANCE_REQUEST_WARN_MS` mencatat request web yang lambat ke `operations.log`.

`PERFORMANCE_SERVER_TIMING=true` dapat digunakan sementara di staging untuk melihat durasi aplikasi pada browser DevTools melalui header `Server-Timing`. Jangan dianggap sebagai APM lengkap.

## Pemeriksaan cepat

Sesudah migration:

```bash
php artisan migrate
php artisan performance:check
composer verify:performance
```

Full quality gate:

```bash
composer verify
```

## Profiling query MySQL / MariaDB

Gunakan data staging yang representatif. Jangan melakukan eksperimen berat langsung pada production saat jam sibuk.

Contoh:

```sql
EXPLAIN
SELECT *
FROM pengajuans
WHERE status = 'berjalan'
ORDER BY created_at DESC
LIMIT 20;
```

Untuk workflow approval:

```sql
EXPLAIN
SELECT pengajuan_id, MIN(urutan)
FROM approval_workflows
WHERE status = 'menunggu'
GROUP BY pengajuan_id;
```

Periksa terutama:

- index yang dipilih;
- estimasi rows;
- full table scan yang tidak diharapkan;
- temporary/filesort pada query besar;
- perubahan plan setelah ukuran data bertambah.

## Performance budget

Threshold observability adalah alarm awal, bukan SLA final. Sebelum release candidate, ukur pada staging dengan data representatif dan tetapkan SLO berdasarkan kebutuhan pengguna.

Baseline engineering P6:

| Area | Guardrail |
|---|---|
| Request lambat | warning pada `>= 1500 ms` |
| DB cumulative/request | warning pada `>= 500 ms` |
| Dashboard chart/stats | tidak polling setiap 5 detik |
| Active approval aggregation | satu SQL query |
| Large dashboard distributions | aggregate di DB, bukan load-all ke PHP |
| Batch reminder | `chunkById(100)` |

Jangan mengoptimalkan hanya berdasarkan angka lokal kosong. Gunakan data staging yang mendekati volume nyata.

## Lazy loading detector

Untuk memburu N+1 tambahan secara sengaja pada local/testing:

```env
PERFORMANCE_PREVENT_LAZY_LOADING=true
```

Aktifkan hanya ketika sedang melakukan audit karena code lama mungkin masih memiliki lazy-load yang sah. Default tetap `false` agar baseline stabil.

## PDF dan file

PDF saat ini dibuat on-demand. Jika volume report menjadi tinggi, jangan langsung menambahkan cache global untuk dokumen sensitif. Opsi selanjutnya adalah queue-based generation dengan private storage dan authorization yang sama seperti dokumen lainnya.

## Yang sengaja tidak dilakukan

P6 tidak:

- menambahkan Redis sebagai requirement wajib;
- meng-cache authorization result lintas user;
- meng-cache dokumen sensitif;
- memperkenalkan Elasticsearch;
- memindahkan aplikasi ke microservices;
- menambahkan index untuk setiap kolom;
- mengubah pagination menjadi infinite scroll tanpa kebutuhan UX.

Semua itu hanya layak jika profiling menunjukkan kebutuhan nyata.
