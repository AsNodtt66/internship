# P9 Visual Regression

Snapshot visual hanya digunakan untuk halaman stabil dan bernilai tinggi. P9 dimulai dari landing page dan login, bukan screenshot seluruh aplikasi.

```powershell
npm run test:e2e:visual
```

```bash
npm run test:e2e:visual
```

Viewport desktop, reduced motion, locale, zona waktu, dan masking teks tahun footer diatur di test agar perubahan screenshot memiliki arti. Baseline hanya berjalan pada proyek Chromium dan path snapshot tidak bergantung pada OS, sehingga Windows dan Linux CI membandingkan PNG yang sama. PNG baseline harus dibuat atau diperbarui dengan keputusan sadar:

```powershell
npx playwright test e2e/visual --project=chromium --update-snapshots
```

```bash
npx playwright test e2e/visual --project=chromium --update-snapshots
```

Tinjau PNG satu per satu sebelum commit. Perubahan baseline bukan tanda pass otomatis. Landing dan login peserta telah direview secara visual, lalu `npm run test:e2e:visual` lulus sebagai bagian dari 37 test Chromium penuh.
