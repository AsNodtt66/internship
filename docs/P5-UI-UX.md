# P5 UI/UX Professional Pass

P5 merapikan pengalaman pengguna **tanpa mengubah business workflow atau authorization boundary**. Fokusnya adalah konsistensi visual, bahasa yang mudah dipahami, responsivitas, feedback status, dan aksesibilitas dasar yang dapat diverifikasi.

## Sasaran

- Satu bahasa desain untuk landing page, panel peserta, dan panel internal.
- Bahasa Indonesia konsisten pada label/action yang dilihat pengguna.
- Status pengajuan menjelaskan **apa yang sedang terjadi dan apa yang perlu dilakukan berikutnya**.
- Informasi penting tidak disampaikan hanya dengan warna.
- Custom interaction dapat digunakan dengan keyboard dan tetap terbaca pada layar kecil.
- Motion dekoratif tidak dipaksakan saat pengguna memilih `prefers-reduced-motion`.

## Design tokens

Theme utama berada di:

```text
resources/css/filament/admin/theme.css
```

Palet P5:

```text
Primary blue   #1B5A96
Primary dark   #0E2C4B
Primary light  #2B73B9
Accent gold    #DDA53C
Success green  #15803D
Danger red     #B91C1C
Muted text     #64748B
```

Aturan penggunaan:

- biru = navigasi, primary action, informasi aktif;
- hijau = berhasil/selesai;
- kuning/oranye = menunggu/perlu perhatian;
- merah = ditolak/gagal/perlu perbaikan kritis;
- **selalu sertai warna dengan label teks atau icon/state yang bermakna**.

## Status pengajuan

Copy status dipusatkan di:

```text
app/Support/Ui/PengajuanStatusPresenter.php
```

Jangan membuat `match ($status)` baru pada page/widget bila kebutuhan hanya label, description, atau Filament color. Gunakan presenter tersebut agar admin dan peserta tidak melihat istilah berbeda untuk state yang sama.

## Dashboard peserta

Dashboard peserta sekarang mengutamakan urutan informasi:

1. status + penjelasan;
2. periode;
3. status dokumen;
4. penempatan;
5. perkembangan tahapan;
6. aksi yang relevan;
7. pemberitahuan terbaru.

Timeline memberikan state teks:

```text
Selesai
Sedang diproses
Belum diproses
Perlu tindak lanjut
```

Tahap aktif memakai `aria-current="step"`.

## Landing page

Landing page memiliki:

- `lang="id"`;
- skip link ke `main`;
- landmark `header`, `nav`, `main`, `footer`;
- accessible name untuk navigasi;
- visible keyboard focus;
- reduced-motion handling;
- copy formal `Anda` secara konsisten.

Landing page adalah informasi publik, bukan tempat menaruh aturan bisnis yang hanya hidup di template. Bila ketentuan berubah sering, pindahkan sumber data ke configuration/master data.

## Form

Pedoman form:

- label menjelaskan data yang diminta, bukan nama kolom database;
- helper text hanya dipakai bila pengguna benar-benar membutuhkan konteks;
- format dan ukuran file disebutkan sebelum upload;
- error message harus menjelaskan perbaikan yang diperlukan;
- jangan mengandalkan placeholder sebagai label;
- jangan memblokir paste pada password/OTP;
- gunakan autocomplete yang sesuai pada data standar seperti nama, email, telepon, dan organisasi.

P5 juga memperbaiki input `IPK/Nilai Terakhir`:

- SMK: skala `0–100`;
- D3/D4/S1/S2/S3: skala `0–4`.

## Wayfinding wizard

Wizard pengajuan tetap memvalidasi langkah secara berurutan, tetapi sekarang setiap tahap mempunyai deskripsi singkat tentang tujuan langkah tersebut. Tombol navigasi menggunakan `Kembali` dan `Lanjut`, serta tahap aktif disimpan pada query string `?tahap=...`.

Konfigurasi utama:

```php
Wizard::make([...])
    ->persistStepInQueryString('tahap')
    ->previousAction(fn (Action $action) => $action->label('Kembali'))
    ->nextAction(fn (Action $action) => $action->label('Lanjut'));
```

Jangan menjadikan wizard `skippable()` untuk proses pengajuan ini karena tiap langkah mempunyai data wajib dan urutan validasi yang memang perlu dilalui. Filament tetap melakukan validasi saat pengguna bergerak ke tahap berikutnya.

## Jadwal dan pemberitahuan

Halaman `Jadwal Kegiatan` membedakan informasi periode, pembimbing, evaluasi, penilaian, dan keputusan perpanjangan dengan heading serta copy tindak lanjut yang eksplisit.

Halaman `Notifikasi` menggunakan:

- jumlah pemberitahuan belum dibaca dalam teks;
- state `Sudah dibaca` / action `Tandai dibaca`;
- elemen `<time datetime="...">`;
- `aria-live="polite"` untuk perubahan daftar yang tidak mendesak;
- layout action yang tetap usable pada mobile.

## Responsiveness

Custom table wajib diuji minimal pada:

```text
360 px
768 px
1280 px
```

Tabel dokumen peserta memakai horizontal overflow container yang focusable, caption aksesibel, dan heading column dengan `scope="col"`.

## Yang belum diklaim

P5 **bukan sertifikasi WCAG**. Conformance membutuhkan pengujian manual dan automated browser tooling pada build yang benar-benar berjalan, termasuk:

- keyboard-only walkthrough;
- zoom 200% dan 400%;
- screen reader (NVDA/JAWS/VoiceOver sesuai target pengguna);
- contrast check pada rendered state;
- focus order dan modal focus trap;
- error validation pada form nyata;
- mobile browser test.

Lihat [ACCESSIBILITY.md](ACCESSIBILITY.md) dan [USABILITY-TESTING.md](USABILITY-TESTING.md).
