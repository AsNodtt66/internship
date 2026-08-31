# Architecture

## Bentuk aplikasi

Aplikasi dipertahankan sebagai **Laravel modular monolith**. Pemisahan dilakukan berdasarkan responsibility, bukan membuat abstraction baru tanpa kebutuhan.

```text
Filament / HTTP
      ↓
Policy / Gate
      ↓
Application workflow services
      ↓
Eloquent models + database constraints
      ↓
Database / private filesystem
```

## Authorization

`App\Support\Authorization\PengajuanAccess` memusatkan scope pembacaan Pengajuan:

- PIC/Kabag SDM/Staff SDM/GM: scope administratif.
- Kepala Bagian: hanya Pengajuan pada bagian yang dipimpinnya.
- Pembimbing Lapangan: hanya Pengajuan yang ditugaskan kepadanya.
- Peserta: hanya Pengajuan miliknya.

`PengajuanPolicy` mendelegasikan aturan view ke komponen tersebut. Resource dan widget yang menampilkan kumpulan Pengajuan menggunakan scope yang sama untuk mengurangi policy/query drift.

## Workflow

`PengajuanWorkflowService` tetap menjadi façade kompatibilitas untuk UI existing. Concern yang jelas lintas fitur mulai diekstrak ke `App\Services\Workflow`.

Perubahan P1 sengaja kecil: behavior dikunci dahulu melalui authorization, transaction, database invariant, dan verification sebelum service besar dipecah lebih jauh.

## Documents

Dokumen sensitif disimpan pada disk private (`documents`). Database hanya menyimpan relative path. Akses download:

```text
request -> auth middleware -> route model binding -> Gate/Policy -> safe path -> private disk response
```

Field dokumen yang diperlakukan sensitif terdaftar di `PrivateDocumentRegistry`.

## Database invariant

Migration P1 menegakkan uniqueness yang sudah diasumsikan oleh relasi model:

- `pesertas.user_id`
- `penugasan_pembimbings.pengajuan_id`
- `surat_balasans.pengajuan_id`
- `evaluasis.pengajuan_id`
- `surat_keterangans.pengajuan_id`
- `approval_workflows (pengajuan_id, urutan)`

Migration berhenti bila menemukan duplicate existing agar data production tidak dimodifikasi diam-diam.

## P2 operational boundary

P2 tidak mengubah modular-monolith menjadi microservices. Concern operasional ditempatkan di boundary terpisah:

```text
HTTP request
  -> RequestId middleware
  -> existing controller / Filament
  -> Policy / workflow
  -> domain models
       -> DomainAuditObserver -> audit_logs

Queue / Scheduler
  -> Laravel worker/scheduler
  -> operations log channel
  -> failed_jobs / queue monitor
```

`audit_logs` menyimpan jejak perubahan domain yang dibatasi allowlist non-PII. Application log, audit log, dan business timeline tetap merupakan tiga concern berbeda.

Liveness `/up` hanya membuktikan Laravel berhasil bootstrap. Readiness `/health/ready` juga membuktikan koneksi database dapat menerima query minimal.
