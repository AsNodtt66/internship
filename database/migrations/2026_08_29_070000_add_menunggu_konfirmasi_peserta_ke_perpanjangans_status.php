<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ATURAN BISNIS (direvisi): syarat "evaluasi wajib diisi dulu" sebelum PIC
 * bisa menyetujui permohonan perpanjangan DIHAPUS -- diganti dengan syarat
 * KONFIRMASI PENERIMAAN dari peserta. Alurnya sekarang:
 *
 *   menunggu -> (PIC setuju, cek slot/kuota, TANPA syarat evaluasi lagi)
 *   -> menunggu_konfirmasi_peserta -> (peserta konfirmasi menerima keputusan
 *      lewat "form penerimaan") -> disetujui (baru di titik ini Pengajuan
 *      baru dibuat, lihat PengajuanWorkflowService::konfirmasiPenerimaanPerpanjangan())
 *
 * Status 'ditolak' tidak berubah -- tetap langsung final begitu PIC menolak.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite tidak mengenal ALTER ... MODIFY ENUM; kolom enum di
            // sini disimpan sebagai CHECK constraint lewat Blueprint enum(),
            // jadi tabel perlu dibuat ulang seperti migrasi enum lain di
            // proyek ini (lihat 2026_07_30_020000_..._approval_workflows).
            Schema::create('perpanjangans_new', function ($table) {
                $table->id();
                $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
                $table->date('tanggal_mulai_baru');
                $table->date('tanggal_selesai_baru');
                $table->text('alasan')->nullable();
                $table->string('surat_kampus_path')->nullable();
                $table->enum('status', ['menunggu', 'menunggu_konfirmasi_peserta', 'disetujui', 'ditolak'])->default('menunggu');
                $table->foreignId('pengajuan_baru_id')->nullable()->constrained('pengajuans')->nullOnDelete();
                $table->timestamps();
            });

            DB::statement('INSERT INTO perpanjangans_new (id, pengajuan_id, tanggal_mulai_baru, tanggal_selesai_baru, alasan, surat_kampus_path, status, pengajuan_baru_id, created_at, updated_at)
                SELECT id, pengajuan_id, tanggal_mulai_baru, tanggal_selesai_baru, alasan, surat_kampus_path, status, pengajuan_baru_id, created_at, updated_at
                FROM perpanjangans');

            Schema::drop('perpanjangans');
            Schema::rename('perpanjangans_new', 'perpanjangans');

            return;
        }

        DB::statement("ALTER TABLE perpanjangans MODIFY status ENUM('menunggu', 'menunggu_konfirmasi_peserta', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement("UPDATE perpanjangans SET status = 'menunggu' WHERE status = 'menunggu_konfirmasi_peserta'");

            Schema::create('perpanjangans_old', function ($table) {
                $table->id();
                $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
                $table->date('tanggal_mulai_baru');
                $table->date('tanggal_selesai_baru');
                $table->text('alasan')->nullable();
                $table->string('surat_kampus_path')->nullable();
                $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
                $table->foreignId('pengajuan_baru_id')->nullable()->constrained('pengajuans')->nullOnDelete();
                $table->timestamps();
            });

            DB::statement('INSERT INTO perpanjangans_old (id, pengajuan_id, tanggal_mulai_baru, tanggal_selesai_baru, alasan, surat_kampus_path, status, pengajuan_baru_id, created_at, updated_at)
                SELECT id, pengajuan_id, tanggal_mulai_baru, tanggal_selesai_baru, alasan, surat_kampus_path, status, pengajuan_baru_id, created_at, updated_at
                FROM perpanjangans');

            Schema::drop('perpanjangans');
            Schema::rename('perpanjangans_old', 'perpanjangans');

            return;
        }

        DB::statement("UPDATE perpanjangans SET status = 'menunggu' WHERE status = 'menunggu_konfirmasi_peserta'");
        DB::statement("ALTER TABLE perpanjangans MODIFY status ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu'");
    }
};
