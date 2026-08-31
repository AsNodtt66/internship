# Security Baseline P0/P1

## Authorization

Authorization mengikuti deny-by-default pada resource sensitif. `strictAuthorization()` aktif di Filament sehingga policy yang hilang tidak dimaksudkan sebagai izin implisit.

Visibility/sidebar tetap dipakai untuk UX, tetapi bukan kontrol keamanan.

Custom action mutatif penting memiliki authorization eksplisit dan workflow service melakukan guard kembali untuk operasi bernilai tinggi.

## Sensitive documents

CV, KTP/KTM, transkrip, BPJS, proposal, surat internal, hasil evaluasi/penilaian, dan dokumen sejenis harus private.

Kontrol yang diterapkan:

- private filesystem disk;
- download melalui controller terautentikasi;
- Gate/Policy per model induk;
- allowlist field Pengajuan;
- safe relative path (absolute path dan `..` traversal ditolak);
- response `Cache-Control: private, no-store`;
- `X-Content-Type-Options: nosniff`;
- FileUpload sensitif menggunakan tipe/ukuran terkontrol dan path tampering protection Filament.

## Secret/repository hygiene

`.env` tidak boleh masuk source package atau Git. Final source artifact juga tidak membawa dependencies, runtime DB, logs, cache, atau private documents.

Jika credential nyata pernah tersebar melalui repository/package lama, rotasi credential tersebut di environment terkait.

## Remaining hardening after P0/P1

- antivirus/CDR untuk upload dokumen bila threat model membutuhkannya;
- centralized security/audit logging;
- CI dependency/security scanning;
- rate limiting khusus endpoint berisiko;
- CSP/header hardening terukur;
- periodic access-control regression test pada database testing nyata.
