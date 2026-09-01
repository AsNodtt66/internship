# Peta Fase P1-P9

Fase menjelaskan alasan perubahan dibuat dan tempat mencari keputusan lama. Untuk implementasi atau operasi hari ini, gunakan dokumen berdasarkan tugas di [Documentation Index](README.md).

| Fase | Fokus | Dokumen utama | Status yang perlu diingat |
| --- | --- | --- | --- |
| P1 | Integritas data, maintainability, dan kebersihan repository | [Changelog P0/P1](../CHANGELOG-P0-P1.md), [Security P0-P1](SECURITY-P0-P1.md) | P0 dan P1 tercatat dalam satu changelog karena dikerjakan sebagai baseline hardening. |
| P2 | Keandalan delivery dan operasi | [Changelog P2](../CHANGELOG-P2.md), [P2 Operations](P2-OPERATIONS.md) | Membahas keputusan stabilitas dan kesiapan operasional, bukan bukti deployment aktif. |
| P3 | Hardening produksi dan pengalaman developer | [Changelog P3](../CHANGELOG-P3.md), [Production Hardening](PRODUCTION-HARDENING.md) | Gunakan bersama konfigurasi environment aktual. |
| P4 | Kesiapan modernisasi dependency | [Changelog P4](../CHANGELOG-P4.md), [P4 Modernization](P4-MODERNIZATION.md) | Kompatibilitas source tidak sama dengan upgrade dependency yang sudah diterapkan. |
| P5 | UI, aksesibilitas, dan copy | [Changelog P5](../CHANGELOG-P5.md), [P5 UI/UX](P5-UI-UX.md), [Accessibility](ACCESSIBILITY.md) | Tidak menyatakan sertifikasi WCAG tanpa audit yang sesuai. |
| P6 | Kinerja dan observability | [Changelog P6](../CHANGELOG-P6.md), [P6 Performance](P6-PERFORMANCE.md) | Hasil benchmark harus dibaca bersama data environment tempat test dijalankan. |
| P7 | Validasi release candidate | [Changelog P7](../CHANGELOG-P7.md), [P7 Release Candidate](P7-RELEASE-CANDIDATE.md) | Rilis tetap membutuhkan bukti environment, backup, dan persetujuan yang relevan. |
| P8 | Quality gate otomatis dan Playwright | [Changelog P8](../CHANGELOG-P8.md), [P8 Playwright and CI](P8-PLAYWRIGHT-CI.md) | CI harus diperiksa pada SHA yang sama dengan kandidat. |
| P9 | Kualitas test, coverage, mutation, aksesibilitas, visual regression, dan reliabilitas CI | [Changelog P9](../CHANGELOG-P9.md), [P9 Test Strategy](P9-TEST-STRATEGY.md), [P9 Quality Gate](P9-QUALITY-GATE.md) | Status P9 saat ini belum menyatakan kandidat rilis siap. |

## Cara membaca peta ini

1. Pilih fase untuk memahami konteks perubahan lama.
2. Ikuti tautan dokumen utama untuk prosedur dan keputusan teknis.
3. Periksa changelog untuk perubahan source yang terkait.
4. Untuk kondisi hari ini, jalankan gate atau baca bukti terbaru. Ringkasan fase tidak menggantikan hasil verifikasi.

## Batas dokumentasi

Dokumentasi ini tidak menyimpan kredensial, data peserta, atau hasil test yang tidak dapat direproduksi. Bila sebuah bukti belum ada, statusnya ditulis sebagai pending atau blocked. Itu lebih berguna daripada status hijau yang tidak dapat diperiksa.
