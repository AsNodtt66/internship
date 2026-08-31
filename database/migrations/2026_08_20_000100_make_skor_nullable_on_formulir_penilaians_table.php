<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field "Skor" pada Input Penilaian sekarang opsional (PIC boleh kosongkan
 * kalau belum semua aspek dinilai pembimbing), jadi kolomnya juga harus
 * boleh null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulir_penilaians', function (Blueprint $table) {
            $table->decimal('skor', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('formulir_penilaians', function (Blueprint $table) {
            $table->decimal('skor', 5, 2)->nullable(false)->change();
        });
    }
};
