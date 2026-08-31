<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            // Khusus menu "Pengajuan Penelitian" -- dokumen data yang
            // dibutuhkan untuk diteliti, supaya PIC bisa cek kesiapan data.
            $table->string('file_data_penelitian')->nullable();

            // Khusus menu "Pengajuan PKL/Magang" -- kepemilikan BPJS
            // Ketenagakerjaan. Boleh dilewati (skip) kalau peserta belum
            // punya; kalau punya, nomor & foto kartu wajib diisi supaya
            // PIC bisa memverifikasi kebenarannya.
            $table->boolean('punya_bpjs_ketenagakerjaan')->nullable();
            $table->string('no_bpjs_ketenagakerjaan')->nullable();
            $table->string('file_bpjs_ketenagakerjaan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn([
                'file_data_penelitian',
                'punya_bpjs_ketenagakerjaan',
                'no_bpjs_ketenagakerjaan',
                'file_bpjs_ketenagakerjaan',
            ]);
        });
    }
};
