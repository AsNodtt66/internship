<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan bisnis revisi: perpanjangan masa PKL/Penelitian BUKAN menambah
 * tanggal_selesai pada pengajuan yang sama, melainkan diproses sebagai
 * PENGAJUAN BARU yang melalui approval dari awal (PIC -> GM -> Kabag SDM
 * -> Staff SDM -> Kepala Bagian). pengajuan_asal_id menautkan pengajuan
 * baru itu ke pengajuan sebelumnya supaya riwayat tiap periode tetap utuh
 * dan tidak pernah dihapus/ditimpa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->foreignId('pengajuan_asal_id')
                ->nullable()
                ->after('peserta_id')
                ->constrained('pengajuans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropForeign(['pengajuan_asal_id']);
            $table->dropColumn('pengajuan_asal_id');
        });
    }
};
