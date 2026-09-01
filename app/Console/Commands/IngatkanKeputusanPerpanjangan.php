<?php

namespace App\Console\Commands;

use App\Services\PengajuanWorkflowService;
use Illuminate\Console\Command;

/**
 * Pengingat harian (BUKAN blokir) untuk peserta yang periode
 * PKL/Penelitian-nya sudah dekat tapi belum memilih keputusan
 * perpanjangan sama sekali. Lihat
 * PengajuanWorkflowService::kirimPengingatKeputusanPerpanjangan().
 */
class IngatkanKeputusanPerpanjangan extends Command
{
    protected $signature = 'pkl:ingatkan-keputusan-perpanjangan {--h= : Override jumlah hari H- sebelum tanggal_selesai}';

    protected $description = 'Kirim pengingat ke peserta & PIC untuk pengajuan yang mendekati tanggal_selesai tapi belum ada keputusan perpanjang/tidak.';

    public function handle(PengajuanWorkflowService $service): int
    {
        $optionHari = $this->option('h');
        $hHari = $optionHari ? (int) $optionHari : null;

        $jumlah = $service->kirimPengingatKeputusanPerpanjangan($hHari);

        $this->info("Pengingat keputusan perpanjangan terkirim untuk {$jumlah} pengajuan.");

        return self::SUCCESS;
    }
}
