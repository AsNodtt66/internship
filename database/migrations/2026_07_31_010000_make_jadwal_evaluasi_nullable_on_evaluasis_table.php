<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sebelumnya jadwal_evaluasi wajib diisi saat Evaluasi dibuat (satu
     * langkah, langsung oleh Pembimbing). Sesuai flowchart AS-IS ini
     * sebenarnya 2 langkah oleh 2 aktor berbeda:
     *   1. PIC membuat formulir evaluasi (Evaluasi dibuat, jadwal belum ada)
     *   2. Pembimbing Lapangan menerima formulir & menentukan jadwal evaluasi
     * Maka jadwal_evaluasi perlu nullable supaya langkah 1 bisa membuat
     * record tanpa harus tahu jadwalnya dulu.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::create('evaluasis_new', function ($table) {
                $table->id();
                $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
                $table->foreignId('pembimbing_id')->constrained('users');
                $table->date('jadwal_evaluasi')->nullable();
                $table->decimal('nilai_akhir', 5, 2)->nullable();
                $table->enum('hasil', ['lulus', 'perlu_perpanjangan'])->nullable();
                $table->text('catatan')->nullable();
                $table->timestamp('dinilai_at')->nullable();
                $table->timestamps();
            });

            DB::statement('INSERT INTO evaluasis_new SELECT * FROM evaluasis');
            Schema::drop('evaluasis');
            Schema::rename('evaluasis_new', 'evaluasis');

            return;
        }

        DB::statement('ALTER TABLE evaluasis MODIFY jadwal_evaluasi DATE NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement("UPDATE evaluasis SET jadwal_evaluasi = CURRENT_DATE WHERE jadwal_evaluasi IS NULL");

            Schema::create('evaluasis_old', function ($table) {
                $table->id();
                $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
                $table->foreignId('pembimbing_id')->constrained('users');
                $table->date('jadwal_evaluasi');
                $table->decimal('nilai_akhir', 5, 2)->nullable();
                $table->enum('hasil', ['lulus', 'perlu_perpanjangan'])->nullable();
                $table->text('catatan')->nullable();
                $table->timestamp('dinilai_at')->nullable();
                $table->timestamps();
            });

            DB::statement('INSERT INTO evaluasis_old SELECT * FROM evaluasis');
            Schema::drop('evaluasis');
            Schema::rename('evaluasis_old', 'evaluasis');

            return;
        }

        DB::statement("UPDATE evaluasis SET jadwal_evaluasi = CURRENT_DATE WHERE jadwal_evaluasi IS NULL");
        DB::statement('ALTER TABLE evaluasis MODIFY jadwal_evaluasi DATE NOT NULL');
    }
};
