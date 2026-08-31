<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saat Kepala Bagian menyetujui PERMOHONAN perpanjangan, sistem TIDAK
 * mengubah tanggal_selesai pada pengajuan lama -- sistem membuat
 * pengajuan_baru (lihat pengajuan_asal_id di tabel pengajuans) yang wajib
 * melalui approval dari awal. pengajuan_baru_id menyimpan hasilnya.
 *
 * surat_kampus_path: bukti surat pengantar dari kampus untuk perpanjangan
 * (wajib dilampirkan peserta sebelum permohonan bisa diajukan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perpanjangans', function (Blueprint $table) {
            $table->foreignId('pengajuan_baru_id')
                ->nullable()
                ->after('pengajuan_id')
                ->constrained('pengajuans')
                ->nullOnDelete();
            $table->string('surat_kampus_path')->nullable()->after('alasan');
        });
    }

    public function down(): void
    {
        Schema::table('perpanjangans', function (Blueprint $table) {
            $table->dropForeign(['pengajuan_baru_id']);
            $table->dropColumn(['pengajuan_baru_id', 'surat_kampus_path']);
        });
    }
};
