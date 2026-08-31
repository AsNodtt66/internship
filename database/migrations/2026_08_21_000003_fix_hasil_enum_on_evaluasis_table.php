<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BUG DITEMUKAN SAAT AUDIT (revisi checklist evaluasi/perpanjangan):
 * kolom evaluasis.hasil masih pakai enum lama ['lulus', 'perlu_perpanjangan']
 * dari migrasi awal, padahal PengajuanWorkflowService::inputPenilaian() dan
 * form Filament ("Input Penilaian") sudah lama memakai nilai 'selesai' untuk
 * hasil akhir yang tuntas (bukan 'lulus'). Di MySQL ini bikin INSERT/UPDATE
 * dengan hasil='selesai' DITOLAK database (nilai di luar enum) -- di SQLite
 * kebetulan tidak diblokir tapi jadi salah tampil (cetakSuratKeteranganSelesai
 * & jadwal-pkl.blade.php mengecek hasil === 'lulus', yang tidak akan pernah
 * cocok). Migrasi ini menyamakan enum DB dengan nilai yang benar-benar
 * dipakai kode, sekaligus mengonversi baris lama yang kadung tersimpan
 * 'lulus' menjadi 'selesai' supaya data lama tidak "hilang" secara logis.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('evaluasis')->where('hasil', 'lulus')->update(['hasil' => 'selesai']);

        if (DB::getDriverName() === 'sqlite') {
            // SQLite: enum Laravel diimplementasikan sebagai CHECK constraint
            // yang tidak bisa diubah langsung -- rebuild kolom lewat
            // string sementara supaya konsisten dengan MySQL/Postgres.
            Schema::table('evaluasis', function (Blueprint $table) {
                $table->string('hasil_tmp')->nullable()->after('hasil');
            });
            DB::table('evaluasis')->update(['hasil_tmp' => DB::raw('hasil')]);
            Schema::table('evaluasis', function (Blueprint $table) {
                $table->dropColumn('hasil');
            });
            Schema::table('evaluasis', function (Blueprint $table) {
                $table->enum('hasil', ['selesai', 'perlu_perpanjangan'])->nullable()->after('nilai_akhir');
            });
            DB::table('evaluasis')->update(['hasil' => DB::raw('hasil_tmp')]);
            Schema::table('evaluasis', function (Blueprint $table) {
                $table->dropColumn('hasil_tmp');
            });

            return;
        }

        DB::statement("ALTER TABLE evaluasis MODIFY hasil ENUM('selesai', 'perlu_perpanjangan') NULL");
    }

    public function down(): void
    {
        DB::table('evaluasis')->where('hasil', 'selesai')->update(['hasil' => 'lulus']);

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE evaluasis MODIFY hasil ENUM('lulus', 'perlu_perpanjangan') NULL");
    }
};
