<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration 2026_07_24_090000 hanya memperbaiki enum "status" untuk MySQL
     * dan melewati SQLite dengan asumsi yang salah (SQLite tidak menegakkan
     * ENUM). Padahal Laravel membuat CHECK constraint untuk enum di SQLite,
     * sehingga constraint lama (hanya 'menunggu','ditandatangani') masih
     * aktif dan menolak nilai 'disetujui' / 'ditolak' yang dipakai oleh
     * PengajuanWorkflowService::prosesApproval().
     *
     * SQLite tidak mendukung ALTER COLUMN untuk enum/CHECK, jadi tabel
     * dibangun ulang: buat tabel baru dengan constraint yang benar, salin
     * data, hapus tabel lama, lalu ganti nama tabel baru.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::create('approval_workflows_new', function ($table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan');
            $table->foreignId('penandatangan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('catatan')->nullable();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamps();
        });

        DB::statement('INSERT INTO approval_workflows_new (id, pengajuan_id, urutan, penandatangan_id, status, catatan, diproses_at, created_at, updated_at)
            SELECT id, pengajuan_id, urutan, penandatangan_id,
                   CASE WHEN status = "ditandatangani" THEN "disetujui" ELSE status END,
                   catatan, diproses_at, created_at, updated_at
            FROM approval_workflows');

        Schema::drop('approval_workflows');
        Schema::rename('approval_workflows_new', 'approval_workflows');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

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

        DB::statement('INSERT INTO approval_workflows_old (id, pengajuan_id, urutan, penandatangan_id, status, catatan, diproses_at, created_at, updated_at)
            SELECT id, pengajuan_id, urutan, penandatangan_id,
                   CASE WHEN status IN ("disetujui", "ditolak") THEN "ditandatangani" ELSE status END,
                   catatan, diproses_at, created_at, updated_at
            FROM approval_workflows');

        Schema::drop('approval_workflows');
        Schema::rename('approval_workflows_old', 'approval_workflows');
    }
};