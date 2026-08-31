# UI Copy Guide

Tujuan copy aplikasi adalah **jelas, ringkas, dan operasional**. Hindari jargon framework, bahasa campuran, atau kalimat promosi yang tidak membantu pengguna menyelesaikan tugas.

## Istilah utama

Gunakan:

```text
Unduh                    bukan Download
Unggah                   bukan Upload (pada label UI)
Persetujuan              bukan Approval
Aksi Cepat               bukan Quick Action
Pemberitahuan            untuk komunikasi kepada pengguna
PIC                      boleh digunakan karena istilah domain internal
Bagian Tujuan            konsisten di form/tabel
Dokumen Perlu Revisi     bukan Dokumen Ditolak jika masih dapat diperbaiki
Sedang Berjalan          untuk status kegiatan aktif
```

Nama class, key database, command, atau istilah teknis di dokumentasi developer tetap boleh berbahasa Inggris bila itu nama resmi teknologi.

## Tone

- Portal peserta: formal tetapi tidak kaku; gunakan `Anda`.
- Panel internal: singkat dan operasional.
- Error: jelaskan masalah + apa yang dapat dilakukan.
- Confirmation: jelaskan konsekuensi action sebelum pengguna mengonfirmasi.

## Pola action

Baik:

```text
Kirim Pengajuan
Unggah Perbaikan
Tandatangani & Lanjutkan
Unduh Surat Balasan
Lihat Detail Pengajuan
```

Hindari:

```text
Submit
Process
OK
Yes
Download File
Execute Action
```

## Notification

Gunakan judul hasil yang spesifik:

```text
Perbaikan dokumen tersimpan
Permohonan perpanjangan diajukan
Disposisi berhasil ditandatangani
```

Body menjelaskan next step bila ada.

## Empty state

Empty state menjawab:

1. apa yang belum ada;
2. apakah kondisi itu normal;
3. apa yang dapat dilakukan sekarang.

Jangan menampilkan `No data` tanpa konteks.
