<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            if (! Schema::hasColumn('pengajuans', 'jenis_pengajuan')) {
                $table->string('jenis_pengajuan')->default('pkl')->after('bagian_id');
            }
            if (! Schema::hasColumn('pengajuans', 'judul_penelitian')) {
                $table->text('judul_penelitian')->nullable()->after('jenis_pengajuan');
            }
            if (! Schema::hasColumn('pengajuans', 'motivasi')) {
                $table->text('motivasi')->nullable();
            }
            if (! Schema::hasColumn('pengajuans', 'keahlian_skill')) {
                $table->text('keahlian_skill')->nullable();
            }
            if (! Schema::hasColumn('pengajuans', 'sumber_informasi')) {
                $table->string('sumber_informasi')->nullable();
            }
            if (! Schema::hasColumn('pengajuans', 'file_surat_pengantar')) {
                $table->string('file_surat_pengantar')->nullable();
            }
            if (! Schema::hasColumn('pengajuans', 'file_proposal')) {
                $table->string('file_proposal')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_pengajuan',
                'judul_penelitian',
                'motivasi',
                'keahlian_skill',
                'sumber_informasi',
                'file_surat_pengantar',
                'file_proposal',
            ]);
        });
    }
};
