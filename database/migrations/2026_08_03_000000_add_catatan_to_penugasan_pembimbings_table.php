<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom catatan penempatan yang diisi Kepala Bagian Tujuan
     * saat menetapkan Pembimbing Lapangan (lihat halaman "Penentuan
     * Pembimbing"). Kolom ini opsional dan murni informasional.
     */
    public function up(): void
    {
        Schema::table('penugasan_pembimbings', function (Blueprint $table) {
            if (! Schema::hasColumn('penugasan_pembimbings', 'catatan')) {
                $table->text('catatan')->nullable()->after('pembimbing_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penugasan_pembimbings', function (Blueprint $table) {
            if (Schema::hasColumn('penugasan_pembimbings', 'catatan')) {
                $table->dropColumn('catatan');
            }
        });
    }
};
