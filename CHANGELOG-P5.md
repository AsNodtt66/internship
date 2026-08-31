# Changelog P5 — UI/UX, Accessibility, Copy

## Scope

P5 berfokus pada kualitas antarmuka tanpa mengubah authorization boundary maupun alur bisnis inti.

## Perubahan utama

- Palet primary panel diselaraskan dengan portal publik.
- Warna sukses dipisahkan dari warna primary.
- Custom hover movement dihapus; reduced-motion dihormati.
- Focus treatment custom diperjelas.
- Status pengajuan dipusatkan di `PengajuanStatusPresenter`.
- Copy status admin/peserta dibuat konsisten.
- Dashboard peserta diubah menjadi hierarchy yang lebih jelas: status → periode → dokumen → penempatan → timeline → action → notifikasi.
- Timeline sekarang mempunyai state teks dan `aria-current="step"` untuk tahap aktif.
- Tabel dokumen peserta dibuat responsive-scroll, mempunyai caption, column scope, dan action label yang lebih jelas.
- Landing page mendapat skip link, landmark `main`, accessible nav label, formal copy `Anda`, dan copy marketing yang lebih faktual.
- Form pengajuan diperjelas, termasuk IPK/nilai yang mengikuti jenjang pendidikan.
- Login menjelaskan `email atau NIP` serta memakai autocomplete username.
- Action perpanjangan dan tanda tangan mempunyai confirmation copy yang menjelaskan konsekuensi.
- Navigation order peserta dibuat deterministic.
- Admin dashboard mempunyai heading/subheading yang relevan per role.
- Ditambah `composer verify:ui` dan regression test landing accessibility.
- Wizard pengajuan mendapat deskripsi per tahap, tombol `Kembali`/`Lanjut`, dan menyimpan tahap aktif pada query string untuk wayfinding yang lebih baik.
- Halaman Jadwal Kegiatan dan Notifikasi dirapikan dengan heading semantik, empty state informatif, elemen `<time>`, unread state non-color-only, dan action copy yang konsisten.
- Sisa copy admin `Upload`/`Approval` yang terlihat pengguna diseragamkan menjadi `Unggah`/`Persetujuan`.

## Dokumentasi

Ditambahkan:

- `docs/P5-UI-UX.md`
- `docs/ACCESSIBILITY.md`
- `docs/USABILITY-TESTING.md`
- `docs/UI-COPY-GUIDE.md`

## Verification

Source-level:

```bash
composer verify:ui
composer verify:quick
```

Dengan environment lengkap:

```bash
php artisan test
php vendor/bin/pint --test
npm ci
npm run build
```

P5 tidak mengklaim sertifikasi WCAG. Browser, keyboard, zoom, screen-reader, dan usability test dengan pengguna nyata tetap diperlukan.
