# Patch: Surat Keterangan Selesai PKL / Surat Perpanjangan PKL (Langkah 17-18)

## Masalah yang ditemukan
Membandingkan kode dengan flowchart AS-IS yang kamu kirim, seluruh alur
sudah terimplementasi (pengajuan -> verifikasi dokumen -> disposisi
GM/Kabag SDM/Staff SDM -> penempatan & Pembimbing Lapangan -> Surat
Balasan -> PKL berjalan -> evaluasi & penilaian) KECUALI langkah
terakhir (17-18):

> Lulus -> Menerbitkan Surat Keterangan Selesai PKL.
> Belum memenuhi standar -> Menerbitkan Surat Perpanjangan PKL.
> Peserta menerima Surat Keterangan Selesai PKL atau Surat Perpanjangan PKL.

Sebelum patch ini, `inputPenilaian()` di `PengajuanWorkflowService` hanya
mengubah kolom `status` pengajuan menjadi `selesai` / `perlu_perpanjangan`
dan mengirim notifikasi teks — TIDAK ADA dokumen/surat resmi yang
diterbitkan dan bisa diunduh peserta, padahal flowchart AS-IS secara
eksplisit mensyaratkan peserta menerima salah satu dari dua surat
tersebut (sama seperti Surat Balasan di awal alur, yang SUDAH ada
modelnya: `SuratBalasan`).

## Yang ditambahkan
1. **Migration** `create_surat_keterangans_table` — tabel baru
   `surat_keterangans` (pengajuan_id, jenis: selesai|perpanjangan,
   nomor_surat, file_path, generated_by, generated_at). Sengaja dibuat
   tabel terpisah dari `surat_balasans` (bukan reuse) karena beda
   payload bisnis (dua jenis surat) dan beda titik dalam alur.
2. **Model** `app/Models/SuratKeterangan.php` — relasi ke `Pengajuan`
   dan `User` (generated_by), plus helper `isSelesai()`.
3. **`Pengajuan::suratKeterangan()`** — relasi `hasOne` baru.
4. **`PengajuanWorkflowService::terbitkanSuratKeterangan()`** — method
   baru dengan aturan bisnis:
   - `jenis = 'selesai'`: hanya boleh saat status pengajuan `selesai`.
   - `jenis = 'perpanjangan'`: hanya boleh setelah ada `Perpanjangan`
     berstatus `disetujui` dari Kepala Bagian Tujuan.
   - Satu pengajuan hanya boleh punya 1 surat (dicegah lewat relasi
     `hasOne` + pengecekan `exists()`).
   - Otomatis mencatat `RiwayatStatus` (jejak audit) dan mengirim
     `Notifikasi` ke peserta.
5. **2 Action baru di tabel Pengajuan** (`PengajuansTable.php`), hanya
   muncul untuk role `pic`:
   - "Terbitkan Surat Keterangan Selesai" (upload PDF + nomor surat).
   - "Terbitkan Surat Perpanjangan" (upload PDF + nomor surat).
6. **Panel Peserta**: tombol download surat ditambahkan di 3 tempat
   yang sudah ada pola sama untuk Surat Balasan — `ViewPengajuan.php`
   (header action), `PesertaQuickActions` widget, dan `Dashboard.php`
   beserta 2 file blade-nya.
7. **`PengajuanTimelineService`**: label tahap terakhir diganti dari
   "Selesai" -> "Surat Keterangan / Perpanjangan", dan logikanya
   sekarang mengecek keberadaan `suratKeterangan` (bukan cuma status),
   jadi tahap ini `sedang_diproses` selama nilai sudah keluar tapi
   surat belum diterbitkan PIC, baru `selesai` setelah suratnya ada.

## Cara pakai
Salin/overwrite file-file di paket ini ke project (struktur folder
sama persis), lalu:

```bash
php artisan migrate
```

(Tidak perlu `migrate:fresh` — ini migration baru murni, tidak
mengubah tabel lain.)

## Perlu dikonfirmasi ke PIC/Kabag SDM
Sama seperti catatan di `README-PATCH.md` sebelumnya soal arah logika
KKM: dokumen tulisan tangan pada flowchart AS-IS sedikit ambigu.
Patch ini mengasumsikan Surat Perpanjangan diterbitkan SETELAH Kepala
Bagian Tujuan menyetujui permohonan perpanjangan (bukan otomatis begitu
nilai < KKM), karena itu selaras dengan alur `ajukanPerpanjangan()` /
`putuskanPerpanjangan()` yang sudah ada di service. Kalau ternyata PIC
harus bisa menerbitkan Surat Perpanjangan langsung begitu hasil evaluasi
`perlu_perpanjangan` (tanpa menunggu approval Kepala Bagian), tinggal
longgarkan pengecekan `$adaPerpanjanganDisetujui` di
`terbitkanSuratKeterangan()`.
