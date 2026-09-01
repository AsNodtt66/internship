# P9 Test Strategy

Panduan ini menjelaskan kualitas yang diperiksa P9, cara menjalankannya, dan bukti yang harus disimpan. Ia ditujukan untuk maintainer yang perlu memahami hasil test tanpa menebak konteks dari workflow CI.

## Prinsip

- Jalankan test pada database disposable. Jangan gunakan database kerja atau data pengguna.
- Jangan menghapus baseline, menambah ignore PHPStan, atau menurunkan ambang test untuk membuat gate hijau.
- Pisahkan hasil lokal, hasil CI, dan UAT. Ketiganya menjawab risiko yang berbeda.
- Simpan artefak saat browser test gagal. Artefak membantu memperbaiki sebab, bukan sekadar mengulang test.

## Jalur cepat sebelum membuat commit

Jalankan dari root repository. Di Windows, gunakan PowerShell dan pastikan MySQL test serta dependency proyek telah disiapkan sesuai [Quick Start](QUICK-START.md).

```powershell
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --memory-limit=1G --no-progress
php artisan test
npm run build
npm run test:e2e:critical
npm run test:e2e:chromium
npm run test:e2e:cross-browser
```

`test:e2e:chromium` menjalankan suite yang mencakup test aksesibilitas dan visual yang terdaftar pada proyek Chromium. Jalankan perintah khusus di bawah saat Anda sedang memperbaiki area tersebut agar umpan balik lebih cepat.

## Cakupan PHP dan mutation testing

Coverage membutuhkan driver PHP. Di CI driver yang dipakai adalah PCOV. Untuk menjalankannya secara lokal, aktifkan PCOV atau Xdebug terlebih dahulu, lalu:

```powershell
composer test:coverage
composer test:mutation
```

Output coverage ditulis ke `coverage/`; output Infection ke `infection-log/`. Folder tersebut diabaikan Git agar laporan mesin tidak tercampur dengan source. Kegagalan karena driver coverage tidak tersedia adalah `BLOCKED`, bukan PASS.

## Browser, accessibility, dan visual regression

Playwright menyediakan report HTML, JUnit, screenshot, video, dan trace sesuai konfigurasi proyek. Bukti lokal berada di `playwright-report/` dan `test-results/`. CI mengunggah direktori tersebut sebagai artifact ketika job selesai atau gagal.

```powershell
npm run test:e2e:a11y
npm run test:e2e:visual
```

Test accessibility memakai axe dan memeriksa pelanggaran serious serta critical pada halaman yang dipilih. Test visual memakai snapshot yang disetujui. Perbarui snapshot hanya setelah perubahan tampilan ditinjau secara sengaja, bukan untuk menerima perbedaan yang belum dipahami.

```powershell
npx playwright test e2e/visual --project=chromium --update-snapshots
```

Tinjau setiap PNG baru sebelum memasukkannya ke commit.

## Flake check

Test kritikal harus stabil, bukan hanya lolos sekali. Jalankan tiga kali berturut-turut sebelum kandidat dibuat:

```powershell
npx playwright test --project=chromium --grep @critical --repeat-each=3
```

Jika satu iterasi gagal, simpan artefak dan perbaiki penyebabnya. Jangan menambah retry lokal sebagai pengganti perbaikan.

## GitHub Actions

Workflow CI menjalankan source quality, PHPUnit, integrasi MySQL, build frontend, Chromium, browser lintas platform, coverage PCOV, dan mutation testing. Job Chromium juga menjalankan flake check `@critical` tiga kali, Axe, dan visual regression sebagai langkah bernama terpisah. Job `CI Green Gate` hanya lulus jika semua dependency wajib lulus.

Sesudah push, ambil SHA commit lokal:

```powershell
git rev-parse HEAD
```

Lalu buka run GitHub Actions yang memakai SHA tersebut. Periksa job dan artifact pada run itu saja. Run pada commit lain, status branch yang lama, atau check yang belum selesai bukan bukti kandidat ini.

## Status dan pelaporan

| Status | Arti |
| --- | --- |
| PASS | Perintah selesai dengan exit code 0 dan bukti tersedia. |
| FAIL | Perintah selesai gagal; sertakan test atau job yang gagal. |
| PENDING/BLOCKED | Belum dijalankan atau tidak dapat dijalankan; jelaskan kebutuhan untuk melanjutkan. |

Ringkasan kondisi saat ini ada di [P9 Quality Gate](P9-QUALITY-GATE.md).
