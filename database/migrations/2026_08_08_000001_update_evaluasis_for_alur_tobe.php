<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyesuaikan tabel evaluasis dengan keputusan bisnis terbaru: karena
 * Pembimbing Lapangan tidak wajib punya akun untuk login, dia tidak lagi
 * jadi "aktor" yang login untuk menjadwalkan evaluasi atau input nilai.
 *
 * Alurnya sekarang: PIC PKL/Penelitian yang membuat & menerbitkan formulir
 * penilaian (disesuaikan format/kriteria dari kampus/institusi asal masing-
 * masing peserta -> lihat Pengajuan::nama_institusi), Pembimbing Lapangan
 * cukup MENILAI di formulir itu (fisik/luar sistem atau lewat link publik
 * tanpa login), lalu PIC yang merekap & meng-input nilai akhir ke sistem.
 *
 * pembimbing_id jadi nullable (dulu wajib), ditambah dinilai_oleh untuk
 * mencatat user PIC yang menginput nilai ke sistem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasis', function (Blueprint $table) {
            $table->dropForeign(['pembimbing_id']);
        });

        Schema::table('evaluasis', function (Blueprint $table) {
            $table->foreignId('pembimbing_id')->nullable()->change();
            $table->foreignId('dinilai_oleh')->nullable()->after('pembimbing_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('evaluasis', function (Blueprint $table) {
            $table->foreign('pembimbing_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evaluasis', function (Blueprint $table) {
            $table->dropForeign(['pembimbing_id']);
            $table->dropForeign(['dinilai_oleh']);
            $table->dropColumn('dinilai_oleh');
        });
    }
};
