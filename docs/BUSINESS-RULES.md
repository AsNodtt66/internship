# Business Rules — Baseline P8

Dokumen ini membekukan aturan yang benar-benar dijalankan source agar workflow dan automated test tidak saling bertentangan.

## Evaluasi akhir dan KKM

- `PengajuanWorkflowService::KKM = 70` adalah **referensi operasional**.
- Nilai rata-rata numerik tetap dihitung/disimpan sebagai informasi dan audit trail.
- Nilai numerik **tidak otomatis** menentukan status akhir.
- PIC memilih hasil akhir yang sah: `selesai` atau `perlu_perpanjangan`, berdasarkan hasil evaluasi yang berlaku.
- Regression test `EvaluationDecisionRuleTest` memastikan nilai numerik di bawah 70 tidak diam-diam mengoverride keputusan manual PIC.

Jika kebijakan perusahaan berubah menjadi keputusan otomatis berdasarkan KKM, perubahan harus dilakukan sekaligus pada:

1. dokumen ini;
2. `PengajuanWorkflowService`;
3. UI/copy terkait;
4. regression tests.

## Lifecycle data historis

- User/Peserta/Pengajuan historis tidak boleh di-hard-delete melalui UI.
- akses akun dicabut dengan `is_active=false`;
- User dan Peserta mempunyai soft-delete capability sebagai safety net;
- Pengajuan menggunakan `SoftDeletes` sesuai kolom `deleted_at` yang sudah ada;
- Role sistem bukan data CRUD bebas.

## Role sistem

Slug berikut merupakan identifier sistem dan immutable:

```text
peserta
pic
staff_sdm
kabag_sdm
gm
kepala_bagian
pembimbing_lapangan
```

Nama tampilan boleh diedit PIC, tetapi slug tidak boleh diubah/dihapus.
