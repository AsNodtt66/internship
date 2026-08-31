<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menandai kapan pengingat "belum pilih keputusan perpanjangan"
     * terakhir dikirim ke peserta & PIC, supaya command
     * IngatkanKeputusanPerpanjangan tidak mengirim notifikasi berulang
     * untuk pengajuan yang sama (dikirim SEKALI saja, bukan blokir apa
     * pun -- peserta tetap bebas memilih kapan saja).
     */
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->timestamp('pengingat_perpanjangan_terkirim_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn('pengingat_perpanjangan_terkirim_at');
        });
    }
};
