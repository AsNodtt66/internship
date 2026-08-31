# Business Workflow

Dokumen ini merangkum workflow yang saat ini diimplementasikan. Jika kebijakan organisasi berubah, update dokumen **dan** regression test bersama perubahan kode.

## 1. Peserta membuat pengajuan

Status awal:

```text
draft
```

Peserta melengkapi data dan dokumen, lalu submit:

```text
draft -> diajukan
```

## 2. PIC memverifikasi dokumen

Setiap dokumen:

```text
menunggu -> lengkap
menunggu -> tidak_lengkap
```

Jika ada dokumen tidak lengkap:

```text
pengajuan -> dokumen_ditolak
```

Peserta memperbaiki dokumen. Setelah seluruh dokumen bermasalah diperbaiki, pengajuan kembali ke antrean PIC.

Jika semua lengkap:

```text
pengajuan -> verifikasi_dokumen
```

## 3. PIC memberi nomor agenda dan memulai disposisi

```text
verifikasi_dokumen -> proses_approval
```

Sistem membuat empat langkah berurutan:

```text
1. GM
2. Kepala Bagian SDM
3. Staff SDM
4. Kepala Bagian Tujuan
```

Tahap harus diproses berurutan. Step berikutnya tidak dapat diproses sebelum step aktif selesai.

## 4. Kepala Bagian Tujuan memberi catatan calon pembimbing

Pada tahap terakhir, Kepala Bagian Tujuan juga mengisi catatan/rekomendasi calon Pembimbing Lapangan.

## 5. PIC menetapkan Pembimbing Lapangan

PIC memilih pembimbing dari data master. Pembimbing tidak wajib memiliki akun login.

Setelah penetapan dan surat balasan diterbitkan:

```text
pengajuan -> berjalan
```

## 6. Evaluasi

PIC membuat formulir evaluasi, menetapkan jadwal, lalu hasil penilaian dicatat sesuai proses bisnis.

KKM saat ini:

```text
70
```

Jika nilai memenuhi KKM, proses dapat menuju selesai. Bila nilai di bawah KKM, workflow dapat menuju permohonan perpanjangan sesuai aturan yang diimplementasikan.

## 7. Perpanjangan

Perpanjangan **tidak** mengubah tanggal pengajuan lama secara langsung. Bila disetujui, periode perpanjangan dibuat sebagai pengajuan baru agar histori setiap periode tetap terpisah.

## 8. Completion

Dokumen penilaian/surat keterangan diterbitkan sesuai state akhir workflow.

## Invariant penting

- satu pengajuan hanya memiliki satu record untuk entity one-to-one tertentu seperti penugasan/evaluasi/surat tertentu;
- approval sequence unik per `(pengajuan_id, urutan)`;
- approval diproses berurutan;
- peserta tidak dapat mengakses pengajuan peserta lain;
- Kepala Bagian/Pembimbing dibatasi berdasarkan assignment/bagian;
- dokumen sensitif tidak disajikan melalui public storage URL.

## Jika mengubah workflow

Wajib update minimal:

1. `PengajuanWorkflowService` atau service terkait;
2. Filament action/page yang memicu transition;
3. Policy/scoping bila akses berubah;
4. database constraint bila invariant berubah;
5. feature regression tests;
6. dokumen ini dan changelog.
