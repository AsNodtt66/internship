# P9 Flaky Tests

Critical test harus lolos berulang, bukan hanya sekali.

```powershell
npx playwright test --project=chromium --grep @critical --repeat-each=3
```

```bash
npx playwright test --project=chromium --grep @critical --repeat-each=3
```

## Kontrol konfigurasi

- CI memakai satu worker.
- `forbidOnly` aktif pada CI.
- `failOnFlakyTests` aktif.
- Trace dibuat pada retry pertama; screenshot dan video disimpan pada kegagalan.
- Tidak ada `waitForTimeout()` sebagai sinkronisasi normal.

## Riwayat P9

| Temuan | Penyebab | Perbaikan |
| --- | --- | --- |
| Login peserta gagal pada setup | Label input peserta berbeda dengan panel admin | Locator berbasis label menerima kedua nama yang terlihat pengguna. |
| Detail pengajuan peserta HTTP 500 | Halaman mengakses evaluasi yang belum ada | Akses data evaluasi dibuat aman untuk kondisi belum ada evaluasi. |

Hasil kandidat lokal: **PASS**, 44 eksekusi kritikal pada tiga pengulangan dalam 1,9 menit. Tidak ada retry maupun flaky result.
