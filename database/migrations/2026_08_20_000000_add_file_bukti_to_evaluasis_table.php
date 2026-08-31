<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti fisik formulir evaluasi yang sudah diisi (hasil scan/foto),
 * diupload berbarengan dengan input skor manual di aksi "Input Penilaian".
 * Sifatnya pelengkap/dokumentasi -- skor tetap diisi manual seperti biasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasis', function (Blueprint $table) {
            $table->string('file_bukti')->nullable()->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('evaluasis', function (Blueprint $table) {
            $table->dropColumn('file_bukti');
        });
    }
};
