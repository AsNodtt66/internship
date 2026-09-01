# P9 Quality Gate

Dokumen ini adalah catatan bukti untuk kualitas P9. Status di bawah hanya menyatakan pekerjaan yang benar-benar sudah dijalankan pada checkout ini. Ia bukan klaim kesiapan rilis.

## Keputusan saat ini

**Siap untuk commit kandidat lokal, belum siap dinyatakan rilis.** Semua gate lokal yang dapat dijalankan lulus. Coverage dan mutation tetap BLOCKED secara lokal karena driver tidak tersedia, sehingga statusnya bergantung pada job CI ber-PCOV pada SHA kandidat yang sama.

## Bukti yang tersedia

| Area | Status | Bukti |
| --- | --- | --- |
| Analisis statis | PASS | `php vendor/bin/phpstan analyse --memory-limit=1G --no-progress` menghasilkan 0 error tanpa baseline atau ignore. |
| PHPUnit | PASS | `php artisan test`: 44 test, 140 assertion, 3,26 detik. |
| Chromium kritikal | PASS | 44 eksekusi `@critical` dalam tiga pengulangan, 1,9 menit tanpa retry. |
| Chromium penuh | PASS | `npm run test:e2e:chromium`: 37 test, 1,3 menit. |
| Firefox, WebKit, mobile Chrome | PASS | `npm run test:e2e:cross-browser`: 91 test, 4 skip visual Chromium-only, 3,2 menit. |
| Coverage PHP | BLOCKED lokal | `composer test:coverage` berhenti pada `No code coverage driver available`; job CI PCOV wajib menghasilkan artefak. |
| Mutation testing | BLOCKED lokal | Infection berhenti karena PCOV/phpdbg/Xdebug tidak tersedia; job CI PCOV wajib menghasilkan MSI. |
| Accessibility dan visual | PASS | Axe Chromium lulus 12 test; baseline landing dan login peserta direview lalu Chromium penuh lulus. |
| GitHub Actions pada SHA kandidat | PENDING | Belum ada kandidat yang di-push. |

## Urutan kerja yang wajib

1. Jalankan gate lokal pada [P9 Test Strategy](P9-TEST-STRATEGY.md).
2. Simpan report Playwright dan trace bila ada test gagal.
3. Commit hanya saat seluruh gate lokal yang dapat dijalankan sudah hijau.
4. Push commit tersebut, lalu periksa setiap job GitHub Actions pada SHA yang sama. Status branch atau pipeline lain tidak cukup.

## Cara memperbarui dokumen ini

Setelah sebuah gate selesai, catat perintah, SHA, tanggal, ringkasan hasil, dan lokasi artefaknya. Gunakan `PASS`, `FAIL`, atau `PENDING/BLOCKED`. Jangan mengubah status menjadi PASS hanya karena konfigurasi atau script sudah ada.
