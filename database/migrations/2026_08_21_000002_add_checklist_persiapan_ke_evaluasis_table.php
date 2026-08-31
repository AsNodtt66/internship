<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan bisnis revisi:
 * - Evaluasi/penilaian OPSIONAL pada kondisi normal (peserta selesai tanpa
 *   perpanjangan), tapi WAJIB dilakukan dulu sebelum pengajuan perpanjangan
 *   bisa diproses (lihat wajib_untuk_perpanjangan & PengajuanWorkflowService).
 * - Menambahkan form "CHECKLIST PERSIAPAN PELAKSANAAN EVALUASI MAGANG/PKL"
 *   sebagai bagian dari proses evaluasi (identitas, jadwal, tempat,
 *   peralatan/dokumen -- checklist_persiapan disimpan sebagai JSON list
 *   item {label, checked}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasis', function (Blueprint $table) {
            $table->boolean('wajib_untuk_perpanjangan')->default(false)->after('pengajuan_id');
            $table->string('tempat_pelaksanaan')->nullable()->after('jadwal_evaluasi');
            $table->string('nama_rekan_kerja')->nullable()->after('tempat_pelaksanaan');
            $table->string('nama_pendamping_sdm')->nullable()->after('nama_rekan_kerja');
            $table->json('checklist_persiapan')->nullable()->after('nama_pendamping_sdm');
        });
    }

    public function down(): void
    {
        Schema::table('evaluasis', function (Blueprint $table) {
            $table->dropColumn([
                'wajib_untuk_perpanjangan',
                'tempat_pelaksanaan',
                'nama_rekan_kerja',
                'nama_pendamping_sdm',
                'checklist_persiapan',
            ]);
        });
    }
};
