<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\TugasSaya;
use App\Models\Pengajuan;
use App\Services\PengajuanWorkflowService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Kartu ringkasan dashboard untuk Kepala Bagian Tujuan: Total Pengajuan,
 * Menunggu Tanda Tangan Anda (tahap disposisi terakhir, sekaligus tempat
 * menulis catatan calon Pembimbing), Sudah Dikirim ke PIC, dan
 * PKL/Penelitian Aktif — seluruhnya ter-scope hanya pada bagian yang
 * dia pimpin.
 */
class KepalaBagianStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return Auth::user()?->role?->slug === 'kepala_bagian';
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $urutanSaya = array_search('kepala_bagian', PengajuanWorkflowService::URUTAN_APPROVAL, true);

        $query = Pengajuan::query()
            ->whereHas('bagianTujuan', fn ($q) => $q->where('kepala_bagian_id', $user->id));

        $counts = (clone $query)
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN catatan_pembimbing IS NOT NULL THEN 1 ELSE 0 END) as sudah_dicatat, SUM(CASE WHEN status = 'berjalan' THEN 1 ELSE 0 END) as aktif")
            ->first();

        $total = (int) ($counts->total ?? 0);

        $menungguTandaTangan = (clone $query)
            ->where('status', 'proses_approval')
            ->whereHas('approvalWorkflows', fn ($q) => $q->where('urutan', $urutanSaya)->where('status', 'menunggu'))
            ->count();

        $sudahDicatat = (int) ($counts->sudah_dicatat ?? 0);
        $aktif = (int) ($counts->aktif ?? 0);

        return [
            Stat::make('Total Pengajuan', $total)
                ->description('Ditujukan ke bagian Anda')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Menunggu Tanda Tangan Anda', $menungguTandaTangan)
                ->description($menungguTandaTangan > 0 ? 'Perlu tindakan Anda' : 'Semua sudah diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color($menungguTandaTangan > 0 ? 'warning' : 'success')
                ->url(fn () => TugasSaya::getUrl()),

            Stat::make('Sudah Dikirim ke PIC', $sudahDicatat)
                ->description('Catatan calon Pembimbing sudah dikirim')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('PKL/Penelitian Aktif', $aktif)
                ->description('Sedang berjalan di bagian Anda')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('primary'),
        ];
    }
}
