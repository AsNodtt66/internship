<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use App\Services\PengajuanWorkflowService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Kartu statistik utama untuk executive dashboard GM.
 * Hanya tampil untuk role 'gm' — role lain tetap pakai widget dashboard
 * standar (PengajuanStatsWidget dkk).
 */
class GmStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm'], true);
    }

    protected function getStats(): array
    {
        $counts = Pengajuan::query()
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status IN ('disetujui', 'berjalan', 'selesai', 'perlu_perpanjangan') THEN 1 ELSE 0 END) as disetujui, SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak")
            ->first();

        $total = (int) ($counts->total ?? 0);

        $roleSlug = Auth::user()?->role?->slug;
        $labelRole = match ($roleSlug) {
            'gm' => 'GM',
            'kabag_sdm' => 'Kepala Bagian SDM',
            'staff_sdm' => 'Staff SDM',
            default => 'Anda',
        };
        $urutanSaya = array_search($roleSlug, PengajuanWorkflowService::URUTAN_APPROVAL, true) ?: null;

        // "Menunggu Persetujuan [role login]" = pengajuan yang tahap
        // aktifnya benar-benar sedang di urutan role yang SEDANG LOGIN,
        // bukan selalu urutan GM — biar Kabag SDM/Staff SDM juga lihat
        // angka yang relevan buat mereka, bukan punya GM.
        $tahapAktif = app(PengajuanWorkflowService::class)->hitungTahapAktif();
        $menunggu = $urutanSaya ? ($tahapAktif[$urutanSaya] ?? 0) : 0;

        $disetujui = (int) ($counts->disetujui ?? 0);
        $ditolak = (int) ($counts->ditolak ?? 0);

        return [
            Stat::make('Total Pengajuan', $total)
                ->description('Sepanjang tahun berjalan')
                ->color('info'),

            Stat::make("Menunggu Persetujuan {$labelRole}", $menunggu)
                ->description('Perlu tindakan Anda')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(fn () => \App\Filament\Pages\TugasSaya::getUrl()),

            Stat::make('Disetujui', $disetujui)
                ->description($total > 0 ? round($disetujui / $total * 100).'% dari total' : '-')
                ->color('success'),

            Stat::make('Ditolak', $ditolak)
                ->description($total > 0 ? round($ditolak / $total * 100).'% dari total' : '-')
                ->color('danger'),
        ];
    }
}
