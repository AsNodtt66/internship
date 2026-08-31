<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyesuaikan tabel penugasan_pembimbings dengan flowchart TO-BE terbaru:
 *
 *  1. Pembimbing Lapangan TIDAK WAJIB punya akun User untuk login ke sistem
 *     (dia cukup dicatat by name/no HP oleh PIC). Kolom pembimbing_id jadi
 *     nullable, dan ditambah kolom teks nama_pembimbing/jabatan_pembimbing/
 *     no_hp_pembimbing sebagai fallback kalau tidak ada akun User yang dipilih.
 *     Kalau suatu saat pembimbing MEMANG punya akun (opsional), pembimbing_id
 *     tetap bisa diisi dan itu yang jadi sumber data utama.
 *
 *  2. Urutan proses dibalik sesuai gambar TO-BE: PIC PKL/Penelitian dulu yang
 *     MENGUSULKAN nama Pembimbing Lapangan (bersamaan dengan membuat draft
 *     Surat Balasan), baru kemudian Kepala Bagian Tujuan yang MENYETUJUI &
 *     menetapkannya secara resmi. Ini beda dari alur lama di mana Kepala
 *     Bagian langsung menetapkan pembimbing tanpa usulan PIC lebih dulu.
 *     Kolom status/diusulkan_* mencatat tahap usulan PIC, sementara
 *     ditetapkan_oleh/ditetapkan_at (sudah ada) sekarang mencatat tahap
 *     persetujuan Kepala Bagian sehingga jadi nullable dulu sampai disetujui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penugasan_pembimbings', function (Blueprint $table) {
            $table->dropForeign(['pembimbing_id']);
            $table->dropForeign(['ditetapkan_oleh']);
        });

        Schema::table('penugasan_pembimbings', function (Blueprint $table) {
            $table->foreignId('pembimbing_id')->nullable()->change();
            $table->foreignId('ditetapkan_oleh')->nullable()->change();
            $table->timestamp('ditetapkan_at')->nullable()->change();

            $table->string('nama_pembimbing')->nullable()->after('pembimbing_id');
            $table->string('jabatan_pembimbing')->nullable()->after('nama_pembimbing');
            $table->string('no_hp_pembimbing')->nullable()->after('jabatan_pembimbing');

            // diusulkan = baru diajukan PIC, menunggu persetujuan Kepala Bagian
            // disetujui = sudah disahkan Kepala Bagian (mengisi ditetapkan_oleh/at)
            $table->string('status')->default('diusulkan')->after('catatan');

            $table->foreignId('diusulkan_oleh')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('diusulkan_at')->nullable()->after('diusulkan_oleh');
        });

        Schema::table('penugasan_pembimbings', function (Blueprint $table) {
            $table->foreign('pembimbing_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('ditetapkan_oleh')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('penugasan_pembimbings', function (Blueprint $table) {
            $table->dropForeign(['pembimbing_id']);
            $table->dropForeign(['ditetapkan_oleh']);
            $table->dropForeign(['diusulkan_oleh']);
            $table->dropColumn([
                'nama_pembimbing', 'jabatan_pembimbing', 'no_hp_pembimbing',
                'status', 'diusulkan_oleh', 'diusulkan_at',
            ]);
        });
    }
};
