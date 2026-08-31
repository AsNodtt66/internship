<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PIC bisa menambah/menghapus "Field Tambahan" sendiri lewat menu Master
 * Data, tanpa perlu ubah kode. Field inti yang sudah ada (nama, NIM,
 * universitas, jurusan, tujuan, tanggal, dst.) TETAP seperti sekarang --
 * ini cuma menampung pertanyaan tambahan di luar field inti tersebut.
 *
 * target menentukan field ini muncul di form yang mana:
 * - 'registrasi_peserta' -> form daftar akun peserta
 * - 'pengajuan'           -> form pengajuan PKL/Magang/Penelitian
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->enum('target', ['registrasi_peserta', 'pengajuan']);
            $table->string('key'); // dipakai sebagai nama kolom di JSON data_tambahan
            $table->string('label');
            $table->enum('tipe', ['text', 'textarea', 'number', 'date', 'select', 'checkbox', 'file']);
            $table->json('opsi')->nullable(); // daftar pilihan, khusus tipe 'select'
            $table->boolean('wajib_diisi')->default(false);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['target', 'key']);
        });

        Schema::table('pesertas', function (Blueprint $table) {
            $table->json('data_tambahan')->nullable()->after('jurusan');
        });

        Schema::table('pengajuans', function (Blueprint $table) {
            $table->json('data_tambahan')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_field_definitions');

        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropColumn('data_tambahan');
        });

        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn('data_tambahan');
        });
    }
};
