# Dokumentasi

Halaman ini adalah titik masuk dokumentasi proyek. Mulai dari tugas yang sedang Anda lakukan, bukan dari nama fase. Setiap dokumen berusaha menjawab satu kebutuhan dengan jelas dan menunjuk ke dokumen lanjutan bila diperlukan.

## Mulai di sini

| Jika Anda perlu | Baca |
| --- | --- |
| Menjalankan aplikasi lokal | [Quick Start](QUICK-START.md) |
| Memahami konfigurasi dan variabel lingkungan | [Configuration](CONFIGURATION.md) |
| Menyiapkan cara kerja harian | [Local Development](LOCAL-DEVELOPMENT.md) |
| Mengatasi masalah setup | [Troubleshooting](TROUBLESHOOTING.md) |
| Memahami struktur kode | [Project Structure](PROJECT-STRUCTURE.md) |

## Mengubah aplikasi dengan aman

- [Architecture](ARCHITECTURE.md), batas modul dan arah dependensi.
- [Backend Guide](BACKEND-GUIDE.md), model, service, policy, dan database.
- [Frontend Guide](FRONTEND-GUIDE.md), Filament, Livewire, Blade, dan Vite.
- [Business Workflow](BUSINESS-WORKFLOW.md), alur pengajuan sampai evaluasi dan perpanjangan.
- [Roles and Permissions](ROLES-AND-PERMISSIONS.md), batas akses tiap peran.
- [Business Rules](BUSINESS-RULES.md), aturan yang tidak boleh berubah tanpa keputusan produk.

## Menguji dan meninjau kualitas

Mulai dari [P9 Test Strategy](P9-TEST-STRATEGY.md). Dokumen tersebut memberi urutan test lokal, coverage, mutation testing, Playwright, axe, visual regression, flake check, dan verifikasi SHA di GitHub Actions.

- [P9 Quality Gate](P9-QUALITY-GATE.md), status bukti saat ini.
- [P9 Baseline](P9-BASELINE.md), versi runtime dan hasil awal yang terukur.
- [P9 Test Matrix](P9-TEST-MATRIX.md), risiko domain dan lapisan test.
- [P9 Coverage](P9-COVERAGE.md), driver, artefak, dan aturan ratchet.
- [P9 Mutation Testing](P9-MUTATION-TESTING.md), scope Infection dan cara menilai mutant.
- [P9 Flaky Tests](P9-FLAKY-TESTS.md), pengulangan test kritikal dan artefak debug.
- [P9 Accessibility Testing](P9-ACCESSIBILITY-TESTING.md) dan [P9 Visual Regression](P9-VISUAL-REGRESSION.md).
- [P9 Differential Review](P9-DIFFERENTIAL-REVIEW.md) dan [P9 Ponytail Audit](P9-PONYTAIL-AUDIT.md).
- [Testing](TESTING.md), test unit dan feature.
- [P8 Playwright and CI](P8-PLAYWRIGHT-CI.md), database E2E dan artefak browser.
- [Accessibility](ACCESSIBILITY.md), akses keyboard, kontras, dan kebutuhan pengguna.
- [GitHub CI](GITHUB-CI.md), job, artifact, dan cara membaca hasil CI.

## Operasi dan rilis

- [Release Runbook](RELEASE-RUNBOOK.md)
- [Deployment Checklist](DEPLOYMENT-CHECKLIST.md)
- [Rollback Runbook](ROLLBACK-RUNBOOK.md)
- [Backup and Restore](BACKUP-RESTORE.md)
- [Production Hardening](PRODUCTION-HARDENING.md)
- [P7 Release Candidate](P7-RELEASE-CANDIDATE.md)

Dokumen rilis menjelaskan prosedur. Dokumen tersebut tidak membuktikan bahwa environment tertentu sudah siap. Bukti rilis harus berasal dari run yang benar-benar dilakukan pada SHA dan environment yang dimaksud.

## Peta fase P1 sampai P9

[Peta fase P1-P9](PHASES-P1-P9.md) merangkum tujuan, dokumen utama, dan batas status tiap fase. Gunakan sebagai orientasi historis, bukan sebagai pengganti runbook di atas.

## Dokumen referensi

- [Security P0-P1](SECURITY-P0-P1.md)
- [ASVS Mapping](ASVS-MAPPING.md)
- [Performance P6](P6-PERFORMANCE.md)
- [UI/UX P5](P5-UI-UX.md)
- [Modernization P4](P4-MODERNIZATION.md)
- [References](REFERENCES.md)

## Riwayat perubahan

- [P0/P1](../CHANGELOG-P0-P1.md)
- [P2](../CHANGELOG-P2.md)
- [P3](../CHANGELOG-P3.md)
- [P4](../CHANGELOG-P4.md)
- [P5](../CHANGELOG-P5.md)
- [P6](../CHANGELOG-P6.md)
- [P7](../CHANGELOG-P7.md)
- [P8](../CHANGELOG-P8.md)
- [P9](../CHANGELOG-P9.md)

Dokumen lama yang hanya diperlukan untuk konteks tersimpan di [legacy](legacy/).
