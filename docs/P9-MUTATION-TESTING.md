# P9 Mutation Testing

Infection dijalankan pada policy, authorization support, document support, dan service workflow yang dipilih. Cakupan ini sengaja sempit agar hasilnya dapat ditindaklanjuti.

```powershell
composer test:mutation
```

```bash
composer test:mutation
```

Konfigurasi berada di `infection.json5`. Laporan ditulis ke `infection-log/` dan tidak di-commit.

## Cara membaca hasil

Mutant yang lolos bukan otomatis bug. Periksa apakah perubahan operator, role check, status transition, atau duplicate guard tersebut realistis. Tambahkan test hanya untuk gap perilaku yang berarti. Mutant yang tidak reachable atau absurd dicatat alasannya, bukan dikejar untuk skor sempurna.

Eksekusi lokal mencapai pemeriksaan prerequisite lalu berhenti karena tidak ada generator coverage PCOV, phpdbg, atau Xdebug. MSI dan Covered Code MSI berstatus **BLOCKED** sampai job CI ber-PCOV menyelesaikan Infection dan mengunggah lognya.
