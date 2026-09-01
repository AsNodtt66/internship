# P9 Coverage

Coverage dipakai untuk menemukan gap pada kode yang berubah dan domain berisiko tinggi. Ia bukan target kosmetik untuk seluruh repository.

## Menjalankan coverage

CI memakai PCOV. Lokal membutuhkan PCOV atau Xdebug aktif.

```powershell
composer test:coverage
```

```bash
composer test:coverage
```

Laporan ditulis ke `coverage/clover.xml`, `coverage/html/`, dan `coverage/text.txt`. Artefak ini tidak di-commit.

## Aturan P9

- Overall coverage tidak boleh turun setelah baseline diukur.
- Perubahan code harus mempunyai test yang relevan; target changed executable lines adalah 80% bila data coverage sudah tersedia.
- Policy, workflow, authorization, dan private documents diprioritaskan sebelum presentation code.
- Tidak ada angka baseline yang diklaim sebelum job ber-PCOV selesai.

## Kondisi saat ini

`php -m` pada mesin lokal tidak memuat PCOV maupun Xdebug. `composer test:coverage` menjalankan 44 test lalu berhenti dengan `No code coverage driver available`; statusnya **BLOCKED**, bukan PASS. Coverage dan patch coverage baru dapat dinilai setelah job CI ber-PCOV menghasilkan artefak Clover, HTML, dan teks pada SHA kandidat yang sama.
