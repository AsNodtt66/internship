<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perubahan aturan bisnis: saat mengajukan PERMOHONAN perpanjangan, peserta
 * sekarang HANYA mengisi tanggal mulai & tanggal selesai periode baru
 * (maksimal 3 bulan, lihat PengajuanWorkflowService::validasiDurasiMaksimal()).
 * Alasan perpanjangan & surat pengantar kampus TIDAK lagi disyaratkan di
 * tahap ini -- keduanya baru diminta belakangan, sebagai "dokumen pelengkap"
 * saat peserta melengkapi Pengajuan periode baru SETELAH PIC menyetujui
 * permohonan (lihat kolom alasan_perpanjangan & file_surat_kampus_perpanjangan
 * pada tabel pengajuans, migrasi terpisah).
 *
 * Kolom alasan & surat_kampus_path di tabel ini dibiarkan (nullable) sebagai
 * riwayat/kompatibilitas data lama -- tidak dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perpanjangans', function (Blueprint $table) {
            $table->text('alasan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('perpanjangans', function (Blueprint $table) {
            $table->text('alasan')->nullable(false)->change();
        });
    }
};
