<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data master Pembimbing Lapangan -- terpisah dari akun login (users).
 *
 * Ini yang jadi sumber dropdown saat PIC mengusulkan Pembimbing Lapangan
 * (lihat PengajuanWorkflowService::usulkanPembimbing()). Sekali seorang
 * pembimbing didaftarkan di sini, namanya otomatis muncul lagi di dropdown
 * untuk pengajuan-pengajuan berikutnya -- PIC tidak perlu ketik ulang nama
 * yang sama setiap kali.
 *
 * user_id nullable & opsional: kalau suatu saat pembimbing ini memang mau
 * dikasih akun untuk login ke sistem, tinggal dihubungkan lewat kolom ini.
 * Tanpa akun sama sekali juga tetap bisa dipakai normal di seluruh alur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembimbing_lapangans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->string('no_hp')->nullable();
            $table->foreignId('bagian_id')->nullable()->constrained('bagians')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembimbing_lapangans');
    }
};
