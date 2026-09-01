# P9 Baseline

Baseline ini dicatat sebelum kandidat P9 dibuat. Nilai yang belum diukur sengaja ditulis pending agar tidak berubah menjadi klaim tanpa bukti.

## Runtime

| Komponen | Nilai lokal |
| --- | --- |
| PHP | 8.3.22 |
| Laravel | 12.64.0 |
| Filament | 4.12.1 |
| Livewire | 3.8.2 |
| PHPUnit | 11.5.56 |
| Node.js | 24.18.0 |
| Vite | 8.2.2 |
| Playwright | 1.62.0 |
| Database test lokal | SQLite, disposable |

## Hasil yang sudah tersedia

| Gate | Hasil |
| --- | --- |
| PHPStan | PASS, 0 error tanpa baseline atau ignore. |
| PHPUnit | PASS, 44 test dan 140 assertion dalam 3,26 detik. |
| Chromium kritikal | PASS, 44 test selama tiga pengulangan dalam 1,9 menit. |
| Full Chromium | PASS, 37 test dalam 1,3 menit. |
| Firefox, WebKit, mobile | PASS, 91 test dan 4 skip visual yang disengaja dalam 3,2 menit. |
| Coverage | BLOCKED lokal, PCOV/Xdebug tidak aktif; job CI PCOV wajib menghasilkan artefak. |
| Mutation score | BLOCKED lokal oleh driver coverage yang sama. |
| CI duration | PENDING, belum ada SHA kandidat. |

## Flake yang ditemukan

Locator login peserta awalnya hanya menerima label admin. Halaman peserta memakai `Email address`; locator sekarang menerima kedua label aksesibel. Detail pengajuan peserta juga sempat menghasilkan HTTP 500 ketika evaluasi belum ada. Keduanya ditutup dengan test browser dan rerun kritikal. Scan Axe juga sempat membaca warna transisi animasi; scan sekarang memakai reduced motion sehingga menilai warna akhir, bukan frame sementara.

Lihat [P9 Flaky Tests](P9-FLAKY-TESTS.md) untuk cara memverifikasi kestabilan sesudah perubahan berikutnya.
