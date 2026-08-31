<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            // Step 2 - Data Pribadi
            $table->string('nama_lengkap')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('no_hp')->nullable();
            $table->string('email_aktif')->nullable();

            // Step 3 - Informasi Akademik
            $table->string('nama_institusi')->nullable();
            $table->string('fakultas')->nullable();
            $table->string('program_studi')->nullable();
            $table->enum('jenjang_pendidikan', ['SMK', 'D3', 'D4', 'S1', 'S2', 'S3'])->nullable();
            $table->unsignedTinyInteger('semester')->nullable();
            $table->string('nim_nisn')->nullable();
            $table->decimal('ipk_nilai', 3, 2)->nullable();

            // Step 4 - tambahan (periode pakai tanggal_mulai/tanggal_selesai yang sudah ada)
            $table->text('tujuan')->nullable();

            // Step 5 - Dosen/Guru Pembimbing
            $table->string('nama_pembimbing_akademik')->nullable();
            $table->string('no_hp_pembimbing_akademik')->nullable();
            $table->string('email_pembimbing_akademik')->nullable();

            // Step 6 - Dokumen tambahan (surat_pengantar & proposal sudah ada)
            $table->string('file_cv')->nullable();
            $table->string('file_ktp_ktm')->nullable();
            $table->string('file_transkrip')->nullable();
            $table->string('file_pas_foto')->nullable();

            // Step 7 - tambahan (keahlian_skill, sumber_informasi, motivasi sudah ada)
            $table->string('rekomendasi_dari')->nullable();

            // Step 8 - Pernyataan
            $table->boolean('setuju_data_benar')->default(false);
            $table->boolean('setuju_patuh_aturan')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn([
                'nama_lengkap', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'nik', 'no_hp', 'email_aktif',
                'nama_institusi', 'fakultas', 'program_studi', 'jenjang_pendidikan', 'semester', 'nim_nisn', 'ipk_nilai',
                'tujuan',
                'nama_pembimbing_akademik', 'no_hp_pembimbing_akademik', 'email_pembimbing_akademik',
                'file_cv', 'file_ktp_ktm', 'file_transkrip', 'file_pas_foto',
                'rekomendasi_dari',
                'setuju_data_benar', 'setuju_patuh_aturan',
            ]);
        });
    }
};