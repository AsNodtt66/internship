<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sebelum ini, begitu disposisi (GM -> Kabag SDM -> Staff SDM) selesai
 * ditandatangani, sistem LANGSUNG otomatis notifikasi Kepala Bagian
 * Tujuan tanpa keterlibatan PIC sama sekali (lihat
 * PengajuanWorkflowService::tandatanganiLangkah()). Ini bikin PIC terasa
 * "nganggur" di tahap ini -- padahal PIC seharusnya jadi checkpoint yang
 * secara sadar meneruskan pengajuan ke Kepala Bagian, sama seperti PIC
 * juga yang secara manual menerbitkan Surat Balasan & Surat Keterangan
 * di tahap-tahap lain.
 *
 * Kolom ini menandai kapan PIC menekan tombol "Teruskan ke Kepala
 * Bagian". Selama masih NULL, Kepala Bagian belum menerima notifikasi
 * apa pun dan pengajuan belum muncul di halaman Penentuan Pembimbing
 * miliknya -- walau status pengajuan sudah 'disetujui'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->timestamp('diteruskan_ke_kabag_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn('diteruskan_ke_kabag_at');
        });
    }
};
