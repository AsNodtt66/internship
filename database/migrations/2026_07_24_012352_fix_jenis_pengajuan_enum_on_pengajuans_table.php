<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite tidak punya sintaks "ALTER TABLE ... MODIFY" (itu sintaks MySQL).
        // SQLite juga tidak menegakkan tipe ENUM/VARCHAR secara strict, jadi
        // untuk SQLite migration ini cukup dilewati (kolom sudah fleksibel by default).
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pengajuans MODIFY jenis_pengajuan VARCHAR(50) NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pengajuans MODIFY jenis_pengajuan ENUM('PKL', 'Penelitian') NOT NULL");
        }
    }
};
