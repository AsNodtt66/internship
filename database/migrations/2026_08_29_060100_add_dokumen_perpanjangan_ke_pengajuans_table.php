<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Dokumen pelengkap" perpanjangan (alasan + surat pengantar dari kampus)
 * dipindah ke sini -- diisi peserta saat melengkapi Pengajuan periode baru
 * (status 'draft', hasil PengajuanWorkflowService::buatPengajuanPerpanjanganBaru())
 * SETELAH PIC menyetujui permohonan perpanjangan, BUKAN lagi saat pertama
 * kali mengajukan permohonan (lihat migrasi
 * 2026_08_29_060000_make_alasan_nullable_on_perpanjangans_table.php).
 *
 * file_surat_kampus_perpanjangan sengaja diberi prefix "file_" mengikuti
 * konvensi kolom dokumen lain di tabel ini (file_surat_pengantar, file_cv,
 * dst.) supaya otomatis ikut tercatat sebagai DokumenPersyaratan lewat
 * CreatePengajuan::FIELD_DOKUMEN (dipakai bersama oleh alur Create & Edit),
 * jadi bisa diverifikasi PIC seperti dokumen persyaratan lainnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->text('alasan_perpanjangan')->nullable()->after('data_tambahan');
            $table->string('file_surat_kampus_perpanjangan')->nullable()->after('alasan_perpanjangan');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['alasan_perpanjangan', 'file_surat_kampus_perpanjangan']);
        });
    }
};
