# Backup & Restore Drill

## Aturan keselamatan

1. Jangan pernah menguji restore ke production.
2. Restore drill hanya ke database kosong yang namanya jelas mengandung `restore_drill`.
3. Backup harus terenkripsi saat disimpan di storage di luar server aplikasi.
4. Simpan checksum terpisah dan verifikasi sebelum restore.
5. Backup dianggap dapat dipercaya hanya setelah restore drill berhasil.

## MySQL / MariaDB backup

Script:

```text
ops/backup/mysql-backup.sh
```

Script tidak membaca `.env` secara langsung dan tidak menerima password sebagai argumen command line. Berikan environment variable dari secret manager/shell:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=internship_management
export DB_USERNAME=backup_user
export DB_PASSWORD='***'
export BACKUP_DIR=/secure/backups/internship-management

bash ops/backup/mysql-backup.sh
```

Output berupa `.sql.gz` dan `.sha256` dengan permission mengikuti `umask 077`.

Akun backup sebaiknya account database khusus dengan hak minimum yang cukup untuk dump.

## Restore drill MySQL / MariaDB

Provision database kosong terlebih dahulu, misalnya:

```text
internship_management_restore_drill
```

Script restore **menolak** database yang namanya tidak jelas mengandung `restore_drill` dan menolak target yang sudah memiliki tabel.

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_USERNAME=restore_drill_user
export DB_PASSWORD='***'
export RESTORE_DB=internship_management_restore_drill
export BACKUP_FILE=/secure/backups/internship-management/internship_management_YYYYMMDDTHHMMSSZ.sql.gz

bash ops/backup/mysql-restore-drill.sh
```

Sesudah restore:

```bash
# arahkan temporary .env hanya ke database drill
php artisan migrate:status
php artisan about
```

Lakukan smoke test read-only terhadap data drill. Jangan mengaktifkan mail, notification, webhook, atau scheduler terhadap hasil restore.

## SQLite backup

Untuk environment yang memang menggunakan SQLite:

```bash
export SQLITE_DATABASE=/path/database.sqlite
export BACKUP_DIR=/secure/backups/internship-management
bash ops/backup/sqlite-backup.sh
```

Script memakai command `.backup` SQLite agar copy konsisten.

## Minimum retention contoh

Retention wajib disesuaikan dengan kebijakan organisasi. Titik awal yang wajar:

- harian: 14–30 hari;
- mingguan: 8–12 minggu;
- bulanan: sesuai kebutuhan legal/operasional.

Jangan menentukan retention hanya berdasarkan kapasitas disk; pertimbangkan kebutuhan pemulihan dan klasifikasi data pribadi.
