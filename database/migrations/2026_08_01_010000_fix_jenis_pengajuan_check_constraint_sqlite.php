<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 2026_07_24_012352_fix_jenis_pengajuan_enum_on_pengajuans_table
 * memperbaiki enum "jenis_pengajuan" dengan `ALTER TABLE ... MODIFY`, yang
 * HANYA valid di MySQL. Di SQLite, statement itu gagal secara diam-diam
 * (SQLite tidak mengenal MODIFY), sehingga CHECK constraint asli dari
 * create_pengajuans_table (hanya mengizinkan 'PKL', 'Penelitian') tetap
 * aktif. Sementara itu form Peserta (PengajuanResource) mengirim nilai
 * 'PKL/Magang', bukan 'PKL' -> QueryException "CHECK constraint failed:
 * jenis_pengajuan" saat submit.
 *
 * SQLite tidak mendukung ALTER COLUMN untuk CHECK constraint. Karena tabel
 * "pengajuans" punya sangat banyak kolom (hasil beberapa migration
 * tambahan), merekonstruksi ulang seluruh tabel via Schema::create berisiko
 * salah/tertinggal kolom. Sebagai gantinya, migration ini menulis ulang
 * definisi tabel langsung di sqlite_master (teknik resmi yang didukung
 * SQLite melalui PRAGMA writable_schema) hanya untuk mengganti daftar nilai
 * valid pada CHECK constraint kolom jenis_pengajuan, tanpa menyentuh kolom
 * lain sama sekali.
 */
return new class extends Migration
{
    private const OLD_CHECK = '"jenis_pengajuan" varchar check ("jenis_pengajuan" in (\'PKL\', \'Penelitian\')) not null';

    private const NEW_CHECK = '"jenis_pengajuan" varchar check ("jenis_pengajuan" in (\'PKL\', \'PKL/Magang\', \'Penelitian\')) not null';

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            // Di MySQL, migration 2026_07_24_012352 sudah menangani ini.
            return;
        }

        $sql = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'pengajuans'")->sql;

        if (! str_contains($sql, self::OLD_CHECK)) {
            // Constraint sudah benar / berbeda dari yang diperkirakan -> jangan sentuh apa pun.
            return;
        }

        $sqlBaru = str_replace(self::OLD_CHECK, self::NEW_CHECK, $sql);

        DB::statement('PRAGMA writable_schema = 1');
        DB::statement('UPDATE sqlite_master SET sql = ? WHERE type = "table" AND name = "pengajuans"', [$sqlBaru]);
        DB::statement('PRAGMA writable_schema = 0');

        // Reconnect is required for file-backed SQLite, but it destroys an
        // in-memory test database and its migration ledger.
        if (DB::connection()->getDatabaseName() !== ':memory:') {
            DB::reconnect();
        }
        DB::statement('PRAGMA integrity_check');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        $sql = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'pengajuans'")->sql;

        if (! str_contains($sql, self::NEW_CHECK)) {
            return;
        }

        $sqlLama = str_replace(self::NEW_CHECK, self::OLD_CHECK, $sql);

        DB::statement('PRAGMA writable_schema = 1');
        DB::statement('UPDATE sqlite_master SET sql = ? WHERE type = "table" AND name = "pengajuans"', [$sqlLama]);
        DB::statement('PRAGMA writable_schema = 0');

        if (DB::connection()->getDatabaseName() !== ':memory:') {
            DB::reconnect();
        }
        DB::statement('PRAGMA integrity_check');
    }
};
