# Patch: Lanjutan Aplikasi Internship & Research Management System
PT PG Rajawali I Unit PG Krebet Baru

Paket ini HANYA berisi file yang baru dibuat atau diubah (tanpa `vendor/`).
Cara pakai: extract lalu salin/overwrite folder `app/` dan `database/` ke
project Laravel kamu, lalu jalankan langkah di bawah.

## 1. Bug yang diperbaiki
- `app/Models/Evaluasi.php` — file sebelumnya terpotong (relasi `pembimbing()`
  dan `formulirPenilaians()` hilang, class tidak ditutup) -> fatal error saat
  diakses. Sudah dilengkapi.
- `app/Models/RiwayatStatus.php` — nama tabel di model (`riwayat_statuses`)
  tidak sama dengan migration (`riwayat_status`), dan `fillable` tidak
  sesuai kolom migration (`status_sebelumnya`, `status_baru`, `keterangan`).
  Sudah disamakan.

## 2. Fitur baru — mengikuti flowchart AS-IS
- `app/Services/PengajuanWorkflowService.php`: mesin alur kerja utama.
  Meng-encode seluruh siklus: ajukan -> verifikasi dokumen -> rekap nomor
  agenda & mulai disposisi berjenjang (Staff SDM -> Kabag SDM -> GM) ->
  diteruskan ke Kepala Bagian tujuan -> terbitkan Surat Balasan -> tetapkan
  Pembimbing Lapangan -> jadwalkan evaluasi -> input nilai (KKM) -> keputusan
  perpanjangan. Setiap transisi otomatis mencatat `RiwayatStatus` dan
  mengirim `Notifikasi`.
- 6 Relation Manager baru di halaman edit Pengajuan:
  Dokumen Persyaratan, Disposisi/Approval, Penugasan Pembimbing,
  Surat Balasan, Evaluasi & Formulir Penilaian, Perpanjangan.
- Action baru di tabel Pengajuan: "Ajukan" dan "Rekap No. Agenda & Mulai
  Approval".
- Resource `Notifikasi` (kotak masuk notifikasi per user + badge unread).
- Widget dashboard `PengajuanStatsWidget` (ringkasan status, ter-scope per
  role).

## 3. Kontrol akses & branding
- `app/Policies/PengajuanPolicy.php` + scoping query di `PengajuanResource`:
  Kepala Bagian hanya melihat pengajuan ke bagiannya; Pembimbing Lapangan
  hanya melihat peserta bimbingannya.
- Menu "Pengguna" dibatasi untuk role `gm`/`kabag_sdm`; menu "Role" dibatasi
  untuk `gm` saja.
- `AdminPanelProvider`: nama brand, warna, pengelompokan navigasi
  (Pengajuan PKL/Penelitian, Master Data, Pengaturan). `brandLogo()` dan
  `favicon()` menunjuk ke `public/images/logo-rni.png` &
  `favicon-rni.png` — **file ini belum ada**, tambahkan logo resmi
  perusahaan di path tersebut (atau hapus baris tersebut bila belum ada).
- `.env` / `.env.example`: `APP_NAME` diganti menjadi
  `"SI-PKL PG Krebet Baru"`.

## 4. Seeder demo
- `BagianSeeder` (baru): struktur bagian dasar (SDM, Akuntansi dan
  Keuangan, Tanaman, Instalasi/Teknik, Pengolahan, Quality Control,
  Umum dan Sekretariat). Sesuaikan dengan struktur organisasi resmi.
- `UserSeeder` (diperbarui): 1 akun demo untuk setiap aktor pada flowchart
  (GM, Kabag SDM, Staff SDM, PIC, Kepala Bagian tujuan, Pembimbing
  Lapangan, Peserta). Password semua akun demo: `password`.
- `DatabaseSeeder`: urutan jalan `RoleSeeder -> BagianSeeder -> UserSeeder`.

## 5. Langkah setelah menyalin file
```bash
composer dump-autoload
php artisan migrate:fresh --seed
php artisan filament:upgrade   # generate ulang aset panel jika perlu
```
Lalu login ke `/admin` dengan salah satu akun demo di atas.

## 6. Perlu dikonfirmasi ke PIC/Kabag SDM
Pada foto flowchart AS-IS ada catatan tulisan tangan soal "nilai di bawah
KKM -> surat tidak diterbitkan" yang arah logikanya agak ambigu.
`PengajuanWorkflowService::inputNilaiEvaluasi()` saat ini memakai logika
standar (nilai >= KKM = lulus/selesai, nilai < KKM = direkomendasikan
perpanjangan). Konstanta `PengajuanWorkflowService::KKM` (default 70) dan
arah keputusannya mudah dibalik kalau hasil konfirmasi berbeda.
