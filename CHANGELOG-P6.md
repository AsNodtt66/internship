# Changelog P6 — Performance Engineering

## Added

- `config/performance.php` untuk threshold observability.
- `RequestPerformance` middleware untuk slow-request logging dan optional `Server-Timing`.
- cumulative database query-time warning via `DB::whenQueryingForLongerThan()`.
- `performance:check` Artisan command.
- `composer verify:performance` source audit.
- hot-path composite index migration.
- regression test yang menjaga active approval aggregation tetap satu query.
- dokumentasi `docs/P6-PERFORMANCE.md`.

## Optimized

- default 5-second polling dinonaktifkan pada Stats/Chart dashboard yang tidak memerlukan realtime refresh.
- GM department/university distribution sekarang dihitung dengan SQL aggregation, bukan load semua Pengajuan ke PHP.
- monthly chart menggunakan satu portable aggregate query.
- GM/general dashboard status counts digabung menjadi aggregate queries.
- `hitungTahapAktif()` dihitung di database.
- `TugasSaya` tidak lagi melakukan N+1 resolver per approval candidate.
- GM pending approval action menggunakan eager-loaded filtered approval relationship.
- bulk-delete Bagian menggunakan satu `loadCount()` untuk seluruh selection.
- extension reminder menghindari query penilaian kedua di dalam transaction.
- workflow notification role lookup di-cache selama lifecycle service.

## Compatibility

Tidak ada perubahan business state, role permission, private-document model, atau public route contract yang disengaja pada P6.
