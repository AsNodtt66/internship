# Usability Testing Plan

Dokumen ini digunakan untuk menguji apakah workflow dapat dipahami pengguna nyata. **Dogfooding oleh developer tidak menggantikan user research** karena developer sudah mengetahui struktur dan istilah internal aplikasi.

Basis review menggunakan heuristic umum seperti:

- visibility of system status;
- match between system and real world;
- user control and freedom;
- consistency and standards;
- error prevention;
- recognition rather than recall;
- clear recovery from errors;
- help/documentation.

Referensi: https://www.nngroup.com/articles/ten-usability-heuristics/

## Peserta uji

Minimal representasikan kelompok berikut:

```text
2–3 peserta PKL/magang yang belum pernah memakai sistem
1 PIC
1 Kepala Bagian
1 Pembimbing Lapangan
1 pengguna approval internal (GM/Kabag SDM/Staf SDM)
```

Jika belum bisa mendapatkan semua role, prioritaskan peserta + PIC karena keduanya memiliki workflow paling panjang.

## Aturan moderator

- Berikan **tujuan**, bukan langkah tombol-per-tombol.
- Jangan membantu kecuali peserta benar-benar buntu.
- Minta peserta berpikir keras (`think aloud`) bila nyaman.
- Catat kebingungan terhadap istilah, bukan hanya error teknis.
- Jangan menganggap task sukses bila moderator harus menunjukkan lokasi menu.

## Task peserta

### U1 — Membuat akun

> Anda ingin mendaftar PKL. Buat akun peserta sampai Anda tiba di halaman masuk.

Amati:

- apakah field NIM/NISN, institusi, jurusan dipahami;
- password/confirmation errors;
- apakah peserta mengerti bahwa setelah registrasi harus masuk.

### U2 — Membuat pengajuan

> Buat pengajuan PKL untuk periode yang ditentukan moderator dan pilih bagian tujuan yang sesuai.

Amati:

- wizard wayfinding;
- ketentuan yang dibaca/dilewati;
- field IPK/nilai;
- periode dan durasi;
- upload file;
- kemampuan memahami tombol final `Kirim Pengajuan`.

### U3 — Memahami status

> Tanpa bantuan moderator, jelaskan status pengajuan sekarang dan apa yang seharusnya Anda lakukan berikutnya.

Task dianggap sukses hanya jika pengguna memahami **state + next step**.

### U4 — Memperbaiki dokumen

> PIC menyatakan satu dokumen perlu revisi. Temukan catatan, buka berkas, lalu unggah perbaikan.

### U5 — Menemukan surat/informasi akhir

> Cari surat balasan atau surat keterangan yang sudah diterbitkan dan buka dokumennya.

## Task PIC

```text
P1 Verifikasi dokumen pengajuan baru
P2 Temukan pengajuan yang perlu tindakan
P3 Tetapkan pembimbing sesuai workflow
P4 Proses permohonan perpanjangan
P5 Temukan riwayat/status setelah action selesai
```

## Task approval internal

```text
A1 Temukan pengajuan yang memang menjadi giliran Anda
A2 Buka detail tanpa kehilangan orientasi
A3 Tandatangani disposisi
A4 Pastikan action berhasil dan task hilang dari antrean
```

## Task Kepala Bagian

Tambahkan skenario memasukkan calon pembimbing pada tahap persetujuan terakhir. Uji apakah istilah dan helper text cukup untuk menyelesaikan task tanpa penjelasan moderator.

## Task Pembimbing Lapangan

```text
B1 Temukan peserta yang ditugaskan
B2 Lihat informasi kegiatan yang relevan
B3 Isi penilaian/evaluasi
B4 Pastikan hasil tersimpan
```

## Data yang dicatat

Untuk setiap task:

```text
Success      : berhasil / berhasil dengan bantuan / gagal
Time         : durasi task
Error        : jumlah salah klik / salah input / dead end
Backtrack    : berapa kali pengguna kembali karena tidak yakin
Comment      : kutipan/kebingungan penting
Severity     : blocker / major / minor / cosmetic
```

Setelah task, tanyakan satu pertanyaan sederhana:

> Seberapa mudah atau sulit tugas ini dilakukan? 1 = sangat sulit, 7 = sangat mudah.

## Prioritas temuan

```text
P0 UX blocker : pengguna tidak dapat menyelesaikan task utama
P1 major      : task selesai hanya dengan bantuan / risiko salah keputusan tinggi
P2 moderate   : friction berulang, wording/wayfinding membingungkan
P3 minor      : kosmetik atau polish yang tidak menghalangi task
```

## Definition of done temuan UX

Temuan dianggap selesai jika:

1. root cause ditulis;
2. perubahan dibuat sekecil mungkin;
3. regression test/source check ditambah jika realistis;
4. task yang sama diuji ulang;
5. perubahan tidak membuka authorization/data-scope baru.
