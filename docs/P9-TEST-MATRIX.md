# P9 Test Matrix

Matriks ini memetakan risiko ke lapisan test. Persentase coverage bukan pengganti daftar risiko ini.

| Risiko | Prioritas | Bukti utama | Bukti browser |
| --- | --- | --- | --- |
| Login dan pengguna nonaktif | Critical | Security dan safety feature tests | `e2e/auth/login.spec.mjs` |
| Policy, peran, dan URL langsung | Critical | Authorization boundary tests | `e2e/security/authorization.spec.mjs` |
| Isolasi peserta dan dokumen private | Critical | Private document authorization tests | `e2e/security/private-documents.spec.mjs` |
| Urutan approval dan duplicate guard | Critical | Workflow feature tests | Critical flow dan dashboard role tests |
| Status pengajuan, evaluasi, perpanjangan | Critical | Submission dan evaluation workflow tests | Detail peserta dan role dashboard tests |
| Dashboard scoped data | High | Authorization and aggregation tests | `e2e/roles/dashboard.spec.mjs` |
| Upload, surat, dan validasi form | High | Private document dan workflow tests | Pending perluasan E2E end-to-end |
| Landing, presentasi, filter | Medium | UI feature tests | `e2e/public/landing.spec.mjs` |
| Copy dan accessor sederhana | Low | Unit test bila memiliki aturan domain | Tidak membutuhkan visual snapshot luas |

## Inventaris saat ini

| Kategori | Lokasi |
| --- | --- |
| Unit | `tests/Unit/` |
| Feature dan integration | `tests/Feature/` |
| Security dan authorization | `tests/Feature/Security/`, `e2e/security/` |
| Workflow | `tests/Feature/Workflow/` |
| Performance | `tests/Feature/Performance/` |
| Playwright | `e2e/` |
| Accessibility | `e2e/accessibility/` |
| Visual regression | `e2e/visual/` |

Sebelum menambah test, jawab satu pertanyaan: perubahan production apa yang membuat test tersebut gagal? Bila jawabannya tidak jelas, perbaiki test atau jangan tambahkan.
