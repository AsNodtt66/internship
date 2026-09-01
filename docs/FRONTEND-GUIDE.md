# Frontend Guide


> **P4:** gunakan PHP 8.3/8.4 dan Node 22.12+ untuk lockfile saat ini. Ikuti [P4 Modernization](P4-MODERNIZATION.md) hanya setelah dependency resolver menghasilkan graph yang mendukung target baru.

## Penting: frontend project ini bukan SPA terpisah

Aplikasi menggunakan **Filament**, yaitu server-driven UI di atas Laravel/Livewire. Artinya sebagian besar layar ditulis dalam PHP (`app/Filament`) dan dirender oleh Laravel. Vite/Tailwind menangani styling dan asset build, bukan menjadi aplikasi React/Vue terpisah.

## Entry points

```text
/admin    -> AdminPanelProvider
/peserta  -> PesertaPanelProvider
/         -> resources/views/landing.blade.php
```

Provider:

```text
app/Providers/Filament/AdminPanelProvider.php
app/Providers/Filament/PesertaPanelProvider.php
```

## Di mana mengubah UI?

### CRUD/master data

Cari resource di:

```text
app/Filament/Resources/
```

Resource biasanya terdiri dari:

```text
<Resource>/
├── <Resource>Resource.php
├── Pages/
├── Schemas/
└── Tables/
```

### Portal peserta

```text
app/Filament/Peserta/
```

### Dashboard/widget

```text
app/Filament/Widgets/
app/Filament/Peserta/Widgets/
```

### Halaman custom

```text
app/Filament/Pages/
app/Filament/Peserta/Pages/
```

### Styling

Theme utama:

```text
resources/css/filament/admin/theme.css
```

Kedua panel menggunakan theme yang sama agar visual konsisten. P5 menetapkan biru korporat sebagai primary, hijau untuk success state, dan emas sebagai accent. Jangan membuat palet baru per halaman.

### Blade views

```text
resources/views/
```

Berisi landing page, hooks, PDF/document views, custom error pages, dan partial lain.

## Asset development

```bash
npm ci
npm run dev
```

Vite akan watch perubahan asset.

## Production build

```bash
npm run build
```

Hasil `public/build/` adalah generated artifact. Jangan edit manual dan jangan menjadikannya source of truth.

## Tambah menu/resource

1. Buat/ubah Filament Resource/Page.
2. Pastikan authorization Policy tersedia.
3. Visibility menu hanya untuk UX; jangan jadikan `canView()`/navigation visibility satu-satunya security control.
4. Tambahkan test untuk role yang boleh dan tidak boleh mengakses.
5. Jalankan browser smoke test pada admin dan peserta panel.

## Custom actions

Action yang mengubah data harus memiliki:

- server-side authorization;
- validation;
- transaction bila menyentuh beberapa record/state;
- idempotency/double-submit guard bila relevan;
- regression test untuk state transition penting.

Untuk action berisiko tinggi, Filament mendukung action rate limiting. Jangan menambahkan rate limit tanpa alasan ke action biasa karena dapat mengganggu UX.

## Upload file

Jangan menggunakan `disk('public')` untuk KTP/KTM, CV, BPJS, transkrip, proposal privat, evaluasi, atau dokumen internal.

Gunakan registry/private disk yang sudah tersedia dan route download terotorisasi.

## Security headers dan CSP

Middleware P3 memasang browser security headers yang kompatibel dengan baseline sekarang. CSP belum di-enforce default. Mulai dengan `SECURITY_CSP_REPORT_ONLY`, observasi browser/report, baru promote ke `SECURITY_CSP` setelah seluruh Filament/Livewire flow dites.

## Checklist sebelum merge UI

```text
[ ] Desktop layout benar
[ ] Mobile/responsive tidak overflow
[ ] Loading/empty state jelas
[ ] Error message manusiawi
[ ] Form label/helper text jelas
[ ] Keyboard focus masih usable
[ ] Authorization tidak hanya di UI
[ ] npm run build PASS
[ ] existing action/menu tidak hilang
```


## P5 UI conventions

### Status copy

Untuk label/description/color status pengajuan gunakan:

```php
App\Support\Ui\PengajuanStatusPresenter
```

Jangan menambah `match ($status)` baru hanya untuk presentasi UI.

### Bahasa UI

Gunakan bahasa Indonesia pada copy pengguna:

```text
Unduh      bukan Download
Unggah     bukan Upload
Persetujuan bukan Approval
Aksi Cepat bukan Quick Action
```

Lihat [UI Copy Guide](UI-COPY-GUIDE.md).

### Accessibility

Custom Blade harus menjaga semantic HTML, keyboard focus, target click/touch, reduced motion, dan tidak menggunakan warna sebagai satu-satunya penanda status.

Jalankan:

```bash
composer verify:ui
```

Manual checklist lebih lengkap: [Accessibility Guide](ACCESSIBILITY.md).

### Breakpoints minimum untuk review

```text
360 px   mobile sempit
768 px   tablet
1280 px  desktop
```

Tabel yang tidak dapat direflow harus berada dalam horizontal scroll region yang jelas dan tetap dapat digunakan keyboard.

### Wizard forms

Untuk wizard panjang, berikan deskripsi singkat pada setiap step dan gunakan label navigasi yang konsisten. Resource pengajuan saat ini memakai `Kembali`, `Lanjut`, dan `persistStepInQueryString('tahap')`. Jangan mengaktifkan free-skip jika langkah berikutnya bergantung pada validasi langkah sebelumnya.

### Notifikasi dan waktu

Untuk daftar pemberitahuan custom:

- sediakan state terbaca dalam teks, bukan opacity/warna saja;
- gunakan elemen `<time datetime="...">` untuk timestamp;
- gunakan `aria-live="polite"` hanya untuk pembaruan non-mendesak yang memang perlu diumumkan;
- tombol `Tandai dibaca` harus tetap memiliki target yang mudah digunakan pada mobile.

## P6 — performa widget dashboard

Filament stats/chart widget memiliki polling periodik bawaan. Pada dashboard project ini, data statistik tidak membutuhkan realtime 5 detik, sehingga P6 menonaktifkan polling pada stats/chart utama.

Saat membuat widget baru:

```php
protected ?string $pollingInterval = null;
```

Gunakan polling hanya jika requirement memang membutuhkan update live. Jika iya, pilih interval yang masuk akal berdasarkan frekuensi perubahan data dan biaya query; jangan otomatis menggunakan interval paling cepat.

Untuk widget agregasi, hindari pola:

```php
Model::with('relation')->get()->groupBy(...)->map->count();
```

pada dataset yang dapat membesar. Lebih baik lakukan `COUNT/GROUP BY` di database dan kirim hasil agregat kecil ke Livewire/Chart.js.

---

## P8 browser regression

Setiap perubahan Filament/Blade/Tailwind yang memengaruhi navigasi, form, modal, responsive behavior, atau authorization UI harus diuji minimal dengan:

```bash
npm run build
npm run test:e2e:critical
```

Sebelum merge perubahan UI besar, jalankan `npm run test:e2e`. Playwright memakai browser nyata; jangan mengganti test backend policy dengan browser assertions.

P9 menambahkan `npm run test:e2e:a11y` dan `npm run test:e2e:visual` untuk halaman yang bernilai tinggi. Rincian scope dan aturan snapshot ada di [P9 Accessibility Testing](P9-ACCESSIBILITY-TESTING.md) dan [P9 Visual Regression](P9-VISUAL-REGRESSION.md).
