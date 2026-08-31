<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengembalikan status approval_workflows ke ('menunggu', 'ditandatangani'),
     * TANPA opsi 'ditolak'.
     *
     * Latar belakang: migration 2026_07_24_090000 mengubah enum ini menjadi
     * ('menunggu', 'disetujui', 'ditolak') supaya cocok dengan
     * PengajuanWorkflowService::prosesApproval() versi lama, yang mengizinkan
     * penolakan di tahap GM / Kabag SDM / Staff SDM.
     *
     * Ini BERTENTANGAN dengan keputusan bisnis yang sudah disepakati sebelumnya:
     * ketiga tahap disposisi tersebut hanya "mengetahui dan menandatangani"
     * surat pengajuan (bukan titik approve/reject), sesuai dokumen Business
     * Process AS-IS resmi. Hanya PIC (verifikasi dokumen) yang punya wewenang
     * menolak/meminta revisi, lewat status 'dokumen_ditolak' pada tabel
     * pengajuans — bukan lewat tabel ini.
     *
     * Baris dengan status 'disetujui' dikonversi jadi 'ditandatangani'.
     * Baris dengan status 'ditolak' (jika ada, dari periode migration lama)
     * dikembalikan ke 'menunggu' agar bisa ditandatangani ulang, karena
     * penolakan pada tahap ini seharusnya tidak pernah terjadi.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::create('approval_workflows_new', function ($table) {
                $table->id();
                $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
                $table->unsignedTinyInteger('urutan');
                $table->foreignId('penandatangan_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['menunggu', 'ditandatangani'])->default('menunggu');
                $table->text('catatan')->nullable();
                $table->timestamp('diproses_at')->nullable();
                $table->timestamps();
            });

            DB::statement("INSERT INTO approval_workflows_new (id, pengajuan_id, urutan, penandatangan_id, status, catatan, diproses_at, created_at, updated_at)
                SELECT id, pengajuan_id, urutan, penandatangan_id,
                       CASE
                           WHEN status = 'disetujui' THEN 'ditandatangani'
                           WHEN status = 'ditolak' THEN 'menunggu'
                           ELSE status
                       END,
                       catatan, diproses_at, created_at, updated_at
                FROM approval_workflows");

            Schema::drop('approval_workflows');
            Schema::rename('approval_workflows_new', 'approval_workflows');

            return;
        }

        DB::statement("UPDATE approval_workflows SET status = 'ditandatangani' WHERE status = 'disetujui'");
        DB::statement("UPDATE approval_workflows SET status = 'menunggu' WHERE status = 'ditolak'");
        DB::statement("ALTER TABLE approval_workflows MODIFY status ENUM('menunggu', 'ditandatangani') NOT NULL DEFAULT 'menunggu'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::create('approval_workflows_old', function ($table) {
                $table->id();
                $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
                $table->unsignedTinyInteger('urutan');
                $table->foreignId('penandatangan_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
                $table->text('catatan')->nullable();
                $table->timestamp('diproses_at')->nullable();
                $table->timestamps();
            });

            DB::statement("INSERT INTO approval_workflows_old (id, pengajuan_id, urutan, penandatangan_id, status, catatan, diproses_at, created_at, updated_at)
                SELECT id, pengajuan_id, urutan, penandatangan_id,
                       CASE WHEN status = 'ditandatangani' THEN 'disetujui' ELSE status END,
                       catatan, diproses_at, created_at, updated_at
                FROM approval_workflows");

            Schema::drop('approval_workflows');
            Schema::rename('approval_workflows_old', 'approval_workflows');

            return;
        }

        DB::statement("ALTER TABLE approval_workflows MODIFY status ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu'");
    }
};
