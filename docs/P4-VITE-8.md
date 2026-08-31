# Upgrade Vite 5 → Vite 8

## Mengapa sekarang

Vite 5 sudah di luar supported branches. P4 menargetkan Vite 8 dan Laravel Vite Plugin 3.

Vite 8 menggunakan Rolldown/Oxc sebagai toolchain utama sehingga production build wajib dibandingkan, bukan sekadar mengubah versi di `package.json`.

## Requirement Node

Gunakan salah satu:

```text
Node 20.19+
Node 22.12+  ← direkomendasikan untuk project ini
```

CI project sudah menggunakan Node 22.x.

## Audit konfigurasi project

`vite.config.js` project sederhana dan menggunakan:

- `defineConfig()`.
- `laravel-vite-plugin`.
- `@tailwindcss/vite`.
- ESM (`package.json` mempunyai `"type": "module"`).

Tidak ditemukan konfigurasi project-level `esbuild`, `rollupOptions`, custom Rollup plugin, atau transform API yang perlu migrasi manual ke Rolldown/Oxc.

## Upgrade

```bash
npm install --save-dev \
  vite@'^8.0.0' \
  laravel-vite-plugin@'^3.1' \
  concurrently@'^10.0.3'
```

Kemudian:

```bash
npm run build
npm run dev
```

## Browser UAT

Periksa minimal:

- style Filament admin.
- style portal peserta.
- modal/action Livewire.
- upload component.
- chart/widget dashboard.
- icon/fonts/logo.
- error pages.
- landing page.
- HMR pada local development.

## Jangan lakukan

- jangan menyalin `node_modules` dari OS lain.
- jangan edit `package-lock.json` manual.
- jangan memakai `npm install --force` untuk menyembunyikan peer dependency conflict.
- jangan mengaktifkan compatibility workaround Vite 8 tanpa bug yang terukur.

## Referensi

- https://vite.dev/releases
- https://vite.dev/guide/migration
- https://vite.dev/blog/announcing-vite8
- https://github.com/laravel/vite-plugin
