# P9 Accessibility Testing

Axe memberi pemeriksaan otomatis untuk pelanggaran serious dan critical. Hasil hijau bukan sertifikasi WCAG dan tidak menggantikan pemeriksaan manusia.

```powershell
npm run test:e2e:a11y
```

```bash
npm run test:e2e:a11y
```

Target awal adalah landing page dan login admin. Keyboard smoke juga melindungi skip link landing serta dashboard peserta. Perluasan berikutnya harus memprioritaskan form pengajuan, dokumen, approval, dan evaluasi.

Sebelum rilis, periksa juga keyboard, focus order, label, validation error, modal focus trap, zoom 200%, dan reflow mobile. Detail prinsip aksesibilitas ada di [Accessibility](ACCESSIBILITY.md).

Status eksekusi Axe P9: **PASS**, 12 test Chromium termasuk setup autentikasi. Scan memfilter pelanggaran `serious` dan `critical`; landing page tidak lagi memakai kombinasi warna teks yang gagal kontras. Hasil yang sama juga tercakup dalam matrix lintas-browser.
