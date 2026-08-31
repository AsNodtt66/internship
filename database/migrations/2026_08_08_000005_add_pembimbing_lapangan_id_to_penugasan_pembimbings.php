<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghubungkan penugasan_pembimbings ke data master pembimbing_lapangans
 * (dropdown), menggantikan cara input manual nama/jabatan/no_hp tiap kali
 * PIC mengusulkan pembimbing. Kolom nama_pembimbing/jabatan_pembimbing/
 * no_hp_pembimbing dari migration sebelumnya TETAP DIPERTAHANKAN sebagai
 * "snapshot" nilainya pada saat diusulkan (supaya riwayat pengajuan lama
 * tidak berubah walau data master di pembimbing_lapangans diedit belakangan),
 * tapi sekarang otomatis diisi dari record pembimbing_lapangans yang dipilih
 * PIC di dropdown -- bukan diketik manual lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penugasan_pembimbings', function (Blueprint $table) {
            $table->foreignId('pembimbing_lapangan_id')->nullable()->after('pembimbing_id')->constrained('pembimbing_lapangans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('penugasan_pembimbings', function (Blueprint $table) {
            $table->dropForeign(['pembimbing_lapangan_id']);
            $table->dropColumn('pembimbing_lapangan_id');
        });
    }
};
