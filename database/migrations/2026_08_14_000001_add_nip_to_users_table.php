<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom NIP khusus untuk login Pembimbing Lapangan (bukan email, sesuai
 * info dari pegawai di lokasi PKL). Role lain (PIC, Kepala Bagian, GM,
 * Kabag SDM, Staff SDM, Peserta) TETAP login pakai email seperti biasa --
 * kolom ini nullable karena cuma dipakai user dengan role
 * 'pembimbing_lapangan'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip')->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nip');
        });
    }
};
