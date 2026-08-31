<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah nilai status baru "menunggu_persetujuan_pembimbing" di antara
 * 'disetujui' (disposisi GM/Kabag SDM/Staff SDM selesai) dan 'berjalan'
 * (Surat Balasan resmi terbit). Status baru ini dipakai selama rentang
 * waktu: PIC sudah mengusulkan Pembimbing Lapangan + draft Surat Balasan,
 * tapi Kepala Bagian Tujuan belum menyetujuinya.
 *
 * SQLite tidak menegakkan ENUM secara strict jadi migration ini cukup
 * dilewati di sana (sama seperti migration fix_jenis_pengajuan_enum yang
 * sudah ada sebelumnya).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pengajuans MODIFY status ENUM(
                'draft', 'diajukan', 'verifikasi_dokumen', 'dokumen_ditolak',
                'proses_approval', 'disetujui', 'menunggu_persetujuan_pembimbing',
                'ditolak', 'berjalan', 'selesai', 'perlu_perpanjangan'
            ) NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pengajuans MODIFY status ENUM(
                'draft', 'diajukan', 'verifikasi_dokumen', 'dokumen_ditolak',
                'proses_approval', 'disetujui', 'ditolak', 'berjalan',
                'selesai', 'perlu_perpanjangan'
            ) NOT NULL DEFAULT 'draft'");
        }
    }
};
