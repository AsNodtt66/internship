# Changelog P8 — Automated Quality Gate & Playwright

## Safety blockers

- aktifkan `SoftDeletes` pada User, Peserta, dan Pengajuan;
- hapus destructive User/Peserta delete actions dari UI dan policy;
- tambah `is_active` management pada User;
- cegah PIC self-demotion/self-deactivation pada edit user;
- jadikan core Role slug immutable dan non-deletable;
- hapus create/delete Role dari Filament UI;
- bekukan business rule evaluasi: keputusan akhir manual PIC, KKM sebagai referensi.

## Automated testing

- tambah `TestingSeeder` yang hanya boleh berjalan pada `APP_ENV=testing`;
- tambah lifecycle/role/business-rule PHPUnit regressions;
- tambah Playwright config dengan local Laravel `webServer`;
- tambah reusable authenticated storage states untuk seluruh role;
- tambah Chromium/Firefox/WebKit/mobile projects;
- tambah direct URL, IDOR, private-document, inactive-login, dashboard, keyboard/responsive browser tests;
- pin Playwright version pada `.playwright-version` tanpa memalsukan `package-lock.json`.

## CI

- tambah MySQL 8.4 integration suite;
- tambah Playwright Chromium mandatory gate;
- tambah Playwright cross-browser mandatory gate;
- tambah JUnit/HTML/trace artifacts;
- hapus staging strict gate dari mandatory scope;
- `npm audit` sekarang blocking untuk severity high+;
- tambah final `ci_green_gate`.

## Documentation

- `docs/P8-PLAYWRIGHT-CI.md`;
- `docs/BUSINESS-RULES.md`;
- update README, Quick Start, Testing, Frontend Guide, Backend Guide, documentation index.
