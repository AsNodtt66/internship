<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite indexes reflect the application's hot WHERE / ORDER BY paths.
     * Keep the set intentionally small: extra indexes speed reads but add
     * storage and write cost.
     */
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'pengajuan_status_created_idx');
            $table->index(['status', 'tanggal_selesai'], 'pengajuan_status_selesai_idx');
            $table->index(['bagian_tujuan_id', 'status'], 'pengajuan_bagian_status_idx');
            $table->index(['peserta_id', 'created_at'], 'pengajuan_peserta_created_idx');
        });

        Schema::table('approval_workflows', function (Blueprint $table): void {
            $table->index(['status', 'urutan', 'pengajuan_id'], 'approval_status_urutan_pengajuan_idx');
        });

        Schema::table('riwayat_status', function (Blueprint $table): void {
            $table->index('created_at', 'riwayat_created_idx');
            $table->index(['pengajuan_id', 'created_at'], 'riwayat_pengajuan_created_idx');
        });

        Schema::table('notifikasis', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at'], 'notifikasi_user_created_idx');
        });

        Schema::table('perpanjangans', function (Blueprint $table): void {
            $table->index(['pengajuan_id', 'status', 'created_at'], 'perpanjangan_pengajuan_status_idx');
        });

        Schema::table('dokumen_persyaratans', function (Blueprint $table): void {
            $table->index(['pengajuan_id', 'jenis_dokumen'], 'dokumen_pengajuan_jenis_idx');
            $table->index(['pengajuan_id', 'status_verifikasi'], 'dokumen_pengajuan_verifikasi_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_persyaratans', function (Blueprint $table): void {
            $table->dropIndex('dokumen_pengajuan_verifikasi_idx');
            $table->dropIndex('dokumen_pengajuan_jenis_idx');
        });

        Schema::table('perpanjangans', fn (Blueprint $table) => $table->dropIndex('perpanjangan_pengajuan_status_idx'));
        Schema::table('notifikasis', fn (Blueprint $table) => $table->dropIndex('notifikasi_user_created_idx'));

        Schema::table('riwayat_status', function (Blueprint $table): void {
            $table->dropIndex('riwayat_pengajuan_created_idx');
            $table->dropIndex('riwayat_created_idx');
        });

        Schema::table('approval_workflows', fn (Blueprint $table) => $table->dropIndex('approval_status_urutan_pengajuan_idx'));

        Schema::table('pengajuans', function (Blueprint $table): void {
            $table->dropIndex('pengajuan_peserta_created_idx');
            $table->dropIndex('pengajuan_bagian_status_idx');
            $table->dropIndex('pengajuan_status_selesai_idx');
            $table->dropIndex('pengajuan_status_created_idx');
        });
    }
};
