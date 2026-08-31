<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Langkah 17-18 flowchart AS-IS: setelah PIC menginput nilai & menentukan
 * hasil akhir, PIC menerbitkan salah satu dari dua jenis surat kepada
 * peserta:
 *  - "selesai"     -> Surat Keterangan Selesai PKL/Penelitian (nilai >= KKM,
 *                     atau perpanjangan tidak disetujui sehingga PKL ditutup).
 *  - "perpanjangan"-> Surat Perpanjangan PKL/Penelitian (nilai < KKM dan
 *                     Kepala Bagian Tujuan menyetujui permohonan perpanjangan).
 *
 * Sebelum ini, sistem hanya mengubah kolom `status` pengajuan tanpa
 * menerbitkan dokumen resmi apa pun ke peserta, padahal flowchart AS-IS
 * (langkah 17-18) secara eksplisit mensyaratkan PIC menerbitkan salah satu
 * surat di atas dan peserta menerimanya -- sama seperti Surat Balasan di
 * awal alur (surat_balasans).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_keterangans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->enum('jenis', ['selesai', 'perpanjangan']);
            $table->string('nomor_surat');
            $table->string('file_path');
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_keterangans');
    }
};
