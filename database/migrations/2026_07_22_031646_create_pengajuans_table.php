<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->foreignId('bagian_tujuan_id')->constrained('bagians');
            $table->string('nomor_agenda')->nullable();
            $table->string('nomor_surat_balasan')->nullable();
            $table->enum('jenis_pengajuan', ['PKL', 'Penelitian']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', [
                'draft', 'diajukan', 'verifikasi_dokumen', 'dokumen_ditolak',
                'proses_approval', 'disetujui', 'menunggu_persetujuan_pembimbing',
                'menunggu_catatan_pembimbing', 'menunggu_penetapan_pembimbing',
                'ditolak', 'berjalan',
                'selesai', 'perlu_perpanjangan',
            ])->default('draft');
            $table->timestamp('diajukan_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuans');
    }
};
