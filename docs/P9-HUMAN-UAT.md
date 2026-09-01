# Human UAT Checklist

Dokumen ini melengkapi test otomatis. Jalankan pada environment yang mewakili penggunaan sebenarnya, dengan akun dan dokumen uji yang tidak memuat data pribadi.

Daftar email akun demo ada di [Quick Start](QUICK-START.md#akun-demo-per-role). Seeder meminta operator menetapkan password lokal sendiri.

## Cara mencatat

Untuk setiap skenario, catat peran penguji, tanggal, hasil (lulus/gagal), langkah reproduksi bila gagal, serta screenshot atau nomor tiket. Jangan menandai lulus hanya karena halaman dapat dibuka.

## Peserta

- Buat pengajuan baru. Isi data wajib, unggah dokumen yang diizinkan, lalu pastikan ringkasan dan status awal sesuai dengan input.
- Kirim form dengan satu field wajib kosong dan dengan tipe/ukuran berkas yang tidak diizinkan. Pastikan pesan kesalahan menjelaskan tindakan yang perlu dilakukan.
- Buka kembali pengajuan yang sudah dikirim. Pastikan peserta hanya melihat pengajuannya sendiri dan dokumen dapat diakses melalui jalur yang berwenang.
- Terima permintaan perbaikan dokumen, unggah penggantinya, lalu periksa apakah status kembali ke antrean verifikasi.
- Periksa notifikasi pada perubahan status dan pada penetapan pembimbing, termasuk teks, tautan, dan waktu tampilnya.
- Ajukan perpanjangan pada pengajuan yang memenuhi syarat. Pastikan periode lama tidak berubah dan riwayat tetap terbaca.

## PIC dan verifikator

- Buka antrean pengajuan, cari satu peserta, lalu verifikasi dokumen valid dan tolak satu dokumen dengan catatan yang jelas.
- Ulangi tindakan verifikasi dengan dua tab atau dua pengguna uji. Pastikan tidak terbentuk approval ganda atau transisi status ganda.
- Periksa filter, pencarian, pagination, dan jumlah status pada dashboard saat data kosong maupun berisi.
- Coba membuka URL detail pengajuan yang bukan cakupan unit atau peran penguji. Akses harus ditolak tanpa membocorkan dokumen atau metadata sensitif.

## GM, Kabag, dan pimpinan terkait

- Selesaikan approval berurutan sesuai kewenangan masing-masing. Pastikan peran berikutnya baru dapat bertindak setelah langkah sebelumnya selesai.
- Tolak pengajuan pada setiap tahap yang mendukung penolakan. Pastikan catatan terlihat oleh peserta dan status akhir konsisten.
- Periksa bahwa tindakan yang tidak tersedia bagi peran tersebut tidak dapat dilakukan melalui URL langsung atau request yang diulang.

## Pembimbing dan evaluasi

- Tetapkan pembimbing internal/lapangan pada pengajuan yang sudah disetujui. Periksa nama, status, dan notifikasi penerima.
- Buat formulir evaluasi, isi nilai dan catatan, lalu simpan. Uji validasi nilai kosong, batas nilai, dan perubahan data sebelum finalisasi.
- Periksa hasil evaluasi di sisi peserta dan internal. Pastikan data yang tampil sesuai hak akses dan hasil akhir mengarahkan workflow dengan benar.

## Surat dan dokumen terproteksi

- Terbitkan surat balasan atau surat keterangan pada state yang mengizinkannya. Periksa isi, nama berkas, dan pengunduhan oleh penerima sah.
- Salin URL dokumen lalu buka dengan pengguna lain atau sesi tanpa login. Sistem harus menolak akses.
- Coba unggah berkas kosong, ekstensi tidak diizinkan, dan berkas pada batas ukuran. Catat hasil validasi.

## Aksesibilitas dan perangkat

- Jalankan alur utama hanya dengan keyboard: masuk, navigasi menu, form pengajuan, unggah, dan konfirmasi. Fokus harus terlihat dan urutan tab masuk akal.
- Uji pada lebar 375 px, desktop, dan zoom 200%. Tidak boleh ada konten utama yang terpotong atau kontrol yang sulit dijangkau.
- Aktifkan reduced motion. Animasi tidak boleh menghalangi penggunaan atau menyembunyikan status.
- Periksa kontras teks status, error form, dan tombol utama. Informasi status tidak boleh hanya bergantung pada warna.

## Penutupan UAT

UAT dapat ditutup bila semua skenario yang relevan terhadap role dan kebijakan organisasi dicatat lulus, atau setiap kegagalan memiliki keputusan tertulis: diperbaiki, diterima sebagai risiko, atau dikeluarkan dari rilis. Sertakan nama environment dan SHA aplikasi pada catatan akhir.
