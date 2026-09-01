<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kembali status 'ditolak' pada approval_workflows.
     *
     * LATAR BELAKANG: migration 2026_07_30_000000 sempat menghapus opsi
     * 'ditolak' di tahap disposisi (GM/Kabag SDM/Staff SDM) karena saat itu
     * disepakati tahap ini hanya "mengetahui dan menandatangani" (sesuai
     * dokumen AS-IS resmi). Keputusan bisnis terbaru membalik ini kembali:
     * demi kualitas kontrol internal perusahaan, tiap tahap disposisi BOLEH
     * menolak pengajuan, dengan syarat WAJIB menyertakan alasan/catatan
     * (lihat PengajuanWorkflowService::tolakLangkah()).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::create('approval_workflows_new', function ($table) {
                $table->id();
                $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
                $table->unsignedTinyInteger('urutan');
                $table->foreignId('penandatangan_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['menunggu', 'ditandatangani', 'ditolak'])->default('menunggu');
                $table->text('catatan')->nullable();
                $table->timestamp('diproses_at')->nullable();
                $table->timestamps();
            });

            DB::statement('INSERT INTO approval_workflows_new (id, pengajuan_id, urutan, penandatangan_id, status, catatan, diproses_at, created_at, updated_at)
                SELECT id, pengajuan_id, urutan, penandatangan_id, status, catatan, diproses_at, created_at, updated_at
                FROM approval_workflows');

            Schema::drop('approval_workflows');
            Schema::rename('approval_workflows_new', 'approval_workflows');

            return;
        }

        DB::statement("ALTER TABLE approval_workflows MODIFY status ENUM('menunggu', 'ditandatangani', 'ditolak') NOT NULL DEFAULT 'menunggu'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::create('approval_workflows_old', function ($table) {
                $table->id();
                $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
                $table->unsignedTinyInteger('urutan');
                $table->foreignId('penandatangan_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['menunggu', 'ditandatangani'])->default('menunggu');
                $table->text('catatan')->nullable();
                $table->timestamp('diproses_at')->nullable();
                $table->timestamps();
            });

            DB::statement("INSERT INTO approval_workflows_old (id, pengajuan_id, urutan, penandatangan_id, status, catatan, diproses_at, created_at, updated_at)
                SELECT id, pengajuan_id, urutan, penandatangan_id,
                       CASE WHEN status = 'ditolak' THEN 'menunggu' ELSE status END,
                       catatan, diproses_at, created_at, updated_at
                FROM approval_workflows");

            Schema::drop('approval_workflows');
            Schema::rename('approval_workflows_old', 'approval_workflows');

            return;
        }

        DB::statement("UPDATE approval_workflows SET status = 'menunggu' WHERE status = 'ditolak'");
        DB::statement("ALTER TABLE approval_workflows MODIFY status ENUM('menunggu', 'ditandatangani') NOT NULL DEFAULT 'menunggu'");
    }
};
