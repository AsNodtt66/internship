# Developer Workflow

## Branching

Gunakan branch kecil berdasarkan tujuan:

```text
fix/<issue>
feat/<feature>
security/<control>
refactor/<scope>
upgrade/<dependency>
```

Jangan gabungkan major dependency upgrade dengan perubahan business workflow.

## Sebelum coding

```bash
composer install
npm ci
php artisan migrate
composer verify:quick
```

## Selama coding

- cari capability existing sebelum membuat abstraction baru;
- jaga perubahan kecil dan terarah;
- jangan menghapus fitur existing tanpa requirement eksplisit;
- authorization dan validation tetap server-side;
- gunakan enum/constants yang ada untuk role/state bila tersedia;
- jangan menyalin business rule ke UI dan service sekaligus.

## Sebelum commit

```bash
composer verify:quick
php artisan test
php vendor/bin/pint --test
npm run build
```

Jika mengubah dependency:

```bash
composer audit --locked
npm audit --audit-level=high
```

## Review checklist

```text
[ ] Tidak ada .env/secret/data dump
[ ] Existing feature tidak hilang
[ ] Authorization server-side ada
[ ] Negative test ada bila permission berubah
[ ] Migration aman untuk data existing
[ ] State transition punya test
[ ] Upload sensitif tetap private
[ ] Logging tidak membawa PII/secret
[ ] README/docs diperbarui bila developer workflow berubah
[ ] Build/test hijau
```

## Bug fixing

Gunakan pola:

```text
reproduce -> root cause -> smallest correct fix -> regression test -> verify
```

Hindari refactor luas saat memperbaiki satu bug kecuali refactor tersebut benar-benar diperlukan untuk menghilangkan root cause.
