# Roles & Permissions

Role didefinisikan melalui `App\Enums\RoleSlug`.

```text
peserta
gm
kabag_sdm
staff_sdm
pic
kepala_bagian
pembimbing_lapangan
```

## Prinsip

- Role menentukan kategori aktor.
- Policy menentukan apakah aktor boleh melakukan aksi pada resource tertentu.
- `PengajuanAccess` membatasi query/data visibility berdasarkan relasi bisnis.
- Menu/sidebar visibility hanya UX, bukan pengganti authorization.

## Ringkasan akses

| Aktor | Panel | Scope utama |
|---|---|---|
| Peserta | `/peserta` | pengajuan/data miliknya sendiri |
| PIC | `/admin` | operasional PKL/Penelitian sesuai policy |
| GM | `/admin` | tahap disposisi/monitoring yang menjadi kewenangannya |
| Kabag SDM | `/admin` | tahap disposisi dan data yang diizinkan policy |
| Staff SDM | `/admin` | tahap disposisi/administrasi sesuai policy |
| Kepala Bagian | `/admin` | pengajuan pada bagian yang menjadi tanggung jawabnya |
| Pembimbing Lapangan | `/admin` bila memiliki akun | pengajuan peserta yang ditugaskan kepadanya |

## Policy source of truth

```text
app/Policies/
```

Jangan membuat controller seperti:

```php
if ($user->role->slug === '...') { ... }
```

di banyak tempat jika rule yang sama sudah bisa direpresentasikan oleh Policy/Gate.

## Direct URL rule

Pengguna yang tidak melihat menu tetap harus ditolak bila mengetik URL resource langsung. Karena itu kedua panel memakai Filament `strictAuthorization()`.

## Dokumen sensitif

Download dokumen harus melewati:

```text
authentication -> Policy/Gate -> safe-path validation -> file response
```

## Perubahan permission

Setiap perubahan wajib punya:

- positive test: role yang benar dapat mengakses;
- negative test: role lain tidak dapat mengakses;
- scope test bila data hanya boleh terlihat berdasarkan bagian/assignment/ownership.
