<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PIC sekarang bisa mengedit daftar aspek penilaian secara manual saat
 * membuat formulir evaluasi (disesuaikan format kampus/institusi asal
 * peserta), bukan cuma pakai daftar aspek yang di-hardcode di kode.
 * Daftar yang dipilih PIC disimpan di sini supaya form cetak (PDF) dan
 * form input nilai digital nanti tetap konsisten pakai aspek yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasis', function (Blueprint $table) {
            $table->json('aspek_penilaian_default')->nullable()->after('pembimbing_id');
        });
    }

    public function down(): void
    {
        Schema::table('evaluasis', function (Blueprint $table) {
            $table->dropColumn('aspek_penilaian_default');
        });
    }
};
