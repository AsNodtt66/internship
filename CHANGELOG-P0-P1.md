# Changelog P0/P1 — 31 Agustus 2026

## P0 — Security & runtime stability

- Mengaktifkan Filament `strictAuthorization()` pada panel admin dan peserta.
- Menambahkan/melengkapi Policy untuk Pengajuan, ApprovalWorkflow, Bagian, Peserta, Pembimbing Lapangan, User, Role, Notifikasi, dan FormFieldDefinition.
- Menambahkan `PengajuanAccess` sebagai satu aturan scope untuk Policy, Resource, dan dashboard widgets.
- Menutup data leak dashboard untuk daftar peserta aktif/selesai dan recent activity.
- Menambahkan authorization eksplisit pada custom Filament Actions sensitif.
- Memindahkan desain dokumen sensitif ke private disk dan authenticated download controller.
- Menambahkan registry field dokumen private dan validasi safe relative path untuk menolak absolute/path traversal.
- Menambahkan command `documents:migrate-private` untuk migrasi file legacy dari public disk.
- Memulihkan method workflow yang sebelumnya dipanggil UI/command tetapi tidak tersedia.
- Memulihkan action keputusan perpanjangan PIC.
- Memperbaiki mismatch kontrak pengajuan perpanjangan peserta.
- Memperkuat approval dengan transaction + row lock + validasi urutan/role.
- Menambahkan guard actor pada perbaikan/verifikasi dokumen, penetapan pembimbing, evaluasi, penilaian, dan pembatalan/penolakan.
- Menjadwalkan reminder perpanjangan pukul 08:00 dengan `withoutOverlapping()`.

## P1 — Integrity, maintainability, repository hygiene

- Menambahkan `RoleSlug` enum dan helper role pada model User.
- Mengekstrak `ExtensionReminderService` dan `WorkflowNotificationService` tanpa memutus façade workflow lama.
- Menambahkan unique database constraints untuk invariant one-to-one dan urutan approval.
- Migration menolak data duplicate existing alih-alih menghapus/menggabungkan data secara diam-diam.
- Menambahkan smoke verification scripts dan unit regression tests untuk access scoping + private document paths.
- Merapikan file root legacy/temp dan memindahkan catatan lama ke `docs/legacy`.
- Menambahkan `.gitignore` source-only untuk secrets, dependencies, build/runtime files, dan private documents.
- Mengganti README generik Laravel dengan dokumentasi proyek.

## Sengaja tidak dilakukan pada baseline ini

- Tidak upgrade Laravel/Filament/Vite.
- Tidak rewrite menjadi microservices/CQRS/repository-heavy architecture.
- Tidak mengubah dependency lock karena environment audit tidak memiliki Composer/network dependency installation yang dapat direproduksi penuh.
- Tidak menghapus business feature existing hanya untuk merapikan kode.
