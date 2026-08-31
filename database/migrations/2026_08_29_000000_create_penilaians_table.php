<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formulir hasil penilaian (PDF) yang diupload PIC setelah PKL/Penelitian
 * berjalan, lalu peserta sendiri yang memilih keputusan perpanjang atau
 * tidak berdasarkan hasil tersebut (lihat
 * PengajuanWorkflowService::uploadPenilaian() &
 * PengajuanWorkflowService::pilihKeputusanPerpanjangan()).
 *
 * Satu Pengajuan hanya punya satu Penilaian (relasi hasOne) -- upload
 * ulang akan MENIMPA file_pdf & mereset keputusan yang sudah lama,
 * bukan membuat baris baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penilaians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->unique()->constrained('pengajuans')->cascadeOnDelete();
            $table->string('file_pdf');
            $table->foreignId('diupload_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diupload_at')->useCurrent();
            $table->string('keputusan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penilaians');
    }
};
