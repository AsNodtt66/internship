<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesuai flowchart TO-BE: PIC PKL/Penelitian membuat draft Surat Balasan
 * BERSAMAAN dengan mengusulkan Pembimbing Lapangan (bukan setelah pembimbing
 * ditetapkan seperti alur lama). Draft ini baru resmi/terbit setelah Kepala
 * Bagian Tujuan menyetujui usulan pembimbing pada tahap "Persetujuan &
 * Penentuan Pembimbing Lapangan".
 *
 * nomor_surat jadi nullable (draft belum tentu sudah punya nomor surat resmi),
 * ditambah status (draft/terbit) dan kolom pencatatan siapa & kapan surat itu
 * akhirnya diterbitkan resmi oleh Kepala Bagian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_balasans', function (Blueprint $table) {
            $table->string('nomor_surat')->nullable()->change();
            $table->string('status')->default('draft')->after('file_path'); // draft|terbit
            $table->foreignId('diterbitkan_oleh')->nullable()->after('generated_at')->constrained('users')->nullOnDelete();
            $table->timestamp('diterbitkan_at')->nullable()->after('diterbitkan_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('surat_balasans', function (Blueprint $table) {
            $table->dropForeign(['diterbitkan_oleh']);
            $table->dropColumn(['status', 'diterbitkan_oleh', 'diterbitkan_at']);
        });
    }
};
