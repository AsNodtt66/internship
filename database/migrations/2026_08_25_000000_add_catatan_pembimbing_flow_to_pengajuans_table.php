<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * REVISI ALUR penentuan Pembimbing Lapangan (menggantikan alur di migration
 * 2026_08_08_000003_add_menunggu_persetujuan_pembimbing_status.php):
 *
 * SEBELUMNYA: Kabag SDM pilih pembimbing dari dropdown saat tanda tangan ->
 * Kepala Bagian menyetujui usulan tsb -> berjalan.
 *
 * SEKARANG: GM -> Kabag SDM -> Staff SDM (tanda tangan biasa, TANPA pilih
 * pembimbing) -> Kepala Bagian Tujuan menulis CATATAN BEBAS calon pembimbing
 * (status 'menunggu_catatan_pembimbing') -> PIC menetapkan resmi dari data
 * master berdasarkan catatan itu (status 'menunggu_penetapan_pembimbing')
 * -> begitu ditetapkan, LANGSUNG 'berjalan' (tidak ada persetujuan lagi
 * dari Kepala Bagian sesudahnya).
 *
 * Status lama 'menunggu_persetujuan_pembimbing' SENGAJA tetap dipertahankan
 * di enum (bukan dihapus) supaya data lama yang mungkin masih berstatus itu
 * tidak error -- tapi sudah tidak dipakai lagi oleh alur baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pengajuans MODIFY status ENUM(
                'draft', 'diajukan', 'verifikasi_dokumen', 'dokumen_ditolak',
                'proses_approval', 'disetujui', 'menunggu_persetujuan_pembimbing',
                'menunggu_catatan_pembimbing', 'menunggu_penetapan_pembimbing',
                'ditolak', 'berjalan', 'selesai', 'perlu_perpanjangan'
            ) NOT NULL DEFAULT 'draft'");
        }

        Schema::table('pengajuans', function (Blueprint $table) {
            $table->text('catatan_pembimbing')->nullable();
            $table->foreignId('catatan_pembimbing_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('catatan_pembimbing_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('catatan_pembimbing_oleh');
            $table->dropColumn(['catatan_pembimbing', 'catatan_pembimbing_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pengajuans MODIFY status ENUM(
                'draft', 'diajukan', 'verifikasi_dokumen', 'dokumen_ditolak',
                'proses_approval', 'disetujui', 'menunggu_persetujuan_pembimbing',
                'ditolak', 'berjalan', 'selesai', 'perlu_perpanjangan'
            ) NOT NULL DEFAULT 'draft'");
        }
    }
};
