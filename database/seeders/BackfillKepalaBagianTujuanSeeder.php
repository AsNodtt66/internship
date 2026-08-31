<?php

namespace Database\Seeders;

use App\Models\ApprovalWorkflow;
use App\Models\Pengajuan;
use Illuminate\Database\Seeder;

/**
 * Backfill SEKALI JALAN untuk pengajuan yang datanya sudah dibuat sebelum
 * URUTAN_APPROVAL diperluas jadi 4 tahap (GM -> Kabag SDM -> Staff SDM ->
 * Kepala Bagian Tujuan). Pengajuan lama itu cuma punya 3 baris di
 * approval_workflows, jadi perlu disesuaikan supaya konsisten dengan alur
 * baru. Aman dijalankan berkali-kali (idempotent) — pengajuan yang sudah
 * punya baris urutan=4 dilewati.
 *
 * Cara pakai:
 *   1. Copy ke: database/seeders/BackfillKepalaBagianTujuanSeeder.php
 *   2. Jalankan: php artisan db:seed --class=Database\\Seeders\\BackfillKepalaBagianTujuanSeeder
 */
class BackfillKepalaBagianTujuanSeeder extends Seeder
{
    public function run(): void
    {
        // Kasus 1: pengajuan masih 'proses_approval' (urutan 1-3 sudah/sedang
        // jalan) tapi belum punya baris urutan=4 -> tambahkan saja, status
        // pengajuan TIDAK berubah, nanti otomatis kena giliran setelah
        // urutan 1-3 selesai.
        $prosesApproval = Pengajuan::where('status', 'proses_approval')
            ->whereDoesntHave('approvalWorkflows', fn ($q) => $q->where('urutan', 4))
            ->get();

        foreach ($prosesApproval as $pengajuan) {
            ApprovalWorkflow::create([
                'pengajuan_id' => $pengajuan->id,
                'urutan' => 4,
                'status' => 'menunggu',
            ]);

            $this->command->info("[proses_approval] #{$pengajuan->id} ({$pengajuan->nomor_agenda}) -> ditambahkan tahap Kepala Bagian Tujuan (menunggu).");
        }

        // Kasus 2: pengajuan SUDAH 'disetujui' di bawah alur lama (3 tahap)
        // dan belum punya baris urutan=4.
        $disetujui = Pengajuan::where('status', 'disetujui')
            ->whereDoesntHave('approvalWorkflows', fn ($q) => $q->where('urutan', 4))
            ->get();

        foreach ($disetujui as $pengajuan) {
            $sudahAdaPembimbing = $pengajuan->penugasanPembimbing()->exists();

            if ($sudahAdaPembimbing) {
                // Kepala Bagian sudah sempat menetapkan pembimbing lewat UI
                // lama -> anggap tahap ke-4 ini SUDAH selesai, cukup catat
                // retroaktif supaya riwayat konsisten. Status pengajuan tidak
                // diubah (tetap 'disetujui').
                ApprovalWorkflow::create([
                    'pengajuan_id' => $pengajuan->id,
                    'urutan' => 4,
                    'status' => 'ditandatangani',
                    'catatan' => '(Backfill) Pembimbing sudah ditetapkan sebelumnya lewat alur lama.',
                    'diproses_at' => now(),
                ]);

                $this->command->info("[disetujui, sudah ada pembimbing] #{$pengajuan->id} ({$pengajuan->nomor_agenda}) -> tahap Kepala Bagian Tujuan dicatat retroaktif sebagai selesai.");

                continue;
            }

            // Belum ada pembimbing -> KEMBALIKAN ke 'proses_approval' supaya
            // benar-benar melewati tahap Kepala Bagian Tujuan yang baru
            // (approve + tetapkan pembimbing jadi satu aksi), baru statusnya
            // balik jadi 'disetujui' lagi setelah tahap ini benar-benar diproses.
            ApprovalWorkflow::create([
                'pengajuan_id' => $pengajuan->id,
                'urutan' => 4,
                'status' => 'menunggu',
            ]);

            $pengajuan->update(['status' => 'proses_approval']);

            $this->command->info("[disetujui, BELUM ada pembimbing] #{$pengajuan->id} ({$pengajuan->nomor_agenda}) -> dikembalikan ke 'proses_approval', menunggu Kepala Bagian Tujuan.");
        }

        $this->command->info('Backfill selesai.');
    }
}