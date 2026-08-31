# Accessibility Guide

## Target

Target engineering proyek adalah **WCAG 2.2 Level AA** untuk workflow utama. Target ini adalah arah pengembangan dan verification baseline, bukan klaim sertifikasi.

Referensi utama:

- WCAG 2.2: https://www.w3.org/TR/WCAG22/
- Understanding WCAG 2.2: https://www.w3.org/WAI/WCAG22/Understanding/

## Prioritas untuk aplikasi ini

### Keyboard dan focus

Periksa:

- semua action dapat dicapai dengan `Tab` / `Shift+Tab`;
- focus order mengikuti urutan visual/logis;
- focus tidak tertutup sticky header atau modal;
- focus indicator terlihat jelas;
- modal mengembalikan focus ke pemicu setelah ditutup.

### Target pointer

Action custom dibuat cukup besar untuk penggunaan touch. Jangan membuat icon-only action kecil tanpa accessible name dan ruang klik yang memadai.

### Status dan notifikasi

Jangan menyampaikan state hanya melalui warna.

Contoh benar:

```text
[ikon] Dokumen Perlu Revisi
Ada dokumen yang perlu diperbaiki sebelum proses dilanjutkan.
```

Bukan hanya badge merah tanpa teks yang bermakna.

### Authentication

- password manager harus dapat mengisi field;
- paste tidak boleh diblokir;
- jangan menambah puzzle/cognitive test tanpa alternatif yang aksesibel;
- field username memakai autocomplete yang sesuai.

### Form error

Setelah submit gagal:

1. pengguna harus tahu bahwa ada error;
2. field bermasalah harus dapat ditemukan;
3. pesan menjelaskan cara memperbaiki;
4. data lain yang sudah benar jangan hilang.

### File upload

Sebelum upload tampilkan:

- jenis dokumen;
- format yang diterima;
- ukuran maksimum;
- apakah wajib/opsional;
- konteks penggunaan bila tidak jelas.

### Tables

Custom table harus memiliki header yang benar. Tabel lebar di mobile ditempatkan dalam scroll region yang diberi accessible name.

## Manual accessibility smoke test

Jalankan pada landing, `/peserta`, dan `/admin`:

```text
[ ] Navigasi seluruh page hanya dengan keyboard
[ ] Tidak ada keyboard trap
[ ] Focus indicator selalu terlihat
[ ] Zoom browser 200% masih usable
[ ] Zoom/reflow kecil tidak memotong primary action
[ ] Form error terbaca dan dapat diperbaiki
[ ] Modal dapat dibuka/ditutup dengan keyboard
[ ] Tooltip/icon-only action mempunyai accessible name
[ ] Status tidak bergantung pada warna
[ ] Link tab baru memberi konteks bila dibuat custom
```

## Automated/source checks

```bash
composer verify:ui
```

Full regression:

```bash
composer verify
```

Source checks tidak menggantikan browser/manual accessibility testing.
