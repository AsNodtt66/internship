<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesuai flowchart AS-IS: setelah GM/Kabag SDM/Staff SDM menandatangani,
 * surat KEMBALI ke PIC dulu (PIC "menerima surat yang telah disetujui",
 * "memperbarui status pengajuan", baru "meneruskan surat" ke tahap
 * berikutnya). Sebelumnya sistem langsung lompat ke tahap berikutnya
 * begitu approver menandatangani, tanpa titik "kembali ke PIC" ini.
 *
 * Kolom baru:
 * - diteruskan_oleh_id: PIC yang menerima & meneruskan tahap ini.
 * - diteruskan_at: kapan PIC meneruskannya ke tahap berikutnya.
 *
 * Sebuah tahap disposisi baru "aktif" (bisa ditandatangani approver-nya)
 * kalau tahap sebelumnya sudah 'ditandatangani' DAN sudah 'diteruskan_at'
 * oleh PIC. Tahap urutan pertama (GM) tetap aktif sejak awal karena PIC
 * sudah meneruskannya langsung saat rekap nomor agenda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            $table->foreignId('diteruskan_oleh_id')->nullable()->after('penandatangan_id')->constrained('users')->nullOnDelete();
            $table->timestamp('diteruskan_at')->nullable()->after('diproses_at');
        });
    }

    public function down(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diteruskan_oleh_id');
            $table->dropColumn('diteruskan_at');
        });
    }
};
