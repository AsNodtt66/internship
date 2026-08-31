<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Kartu ringkasan untuk dashboard PIC/Pembimbing/dst, memakai komponen
 * Stat/StatsOverviewWidget bawaan Filament (sama seperti GmStatsOverview)
 * supaya tampilannya konsisten dan tidak bergantung pada CSS custom.
 */
class PengajuanStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return ! in_array(Auth::user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm'], true);
    }

    protected function getStats(): array
    {
        $query = Pengajuan::query();
        $user = Auth::user();

        // Sesuaikan cakupan data dengan hak akses role, mengikuti scoping pada PengajuanResource.
        if ($user?->role?->slug === 'kepala_bagian') {
            $query->whereHas('bagianTujuan', fn ($q) => $q->where('kepala_bagian_id', $user->id));
        } elseif ($user?->role?->slug === 'pembimbing_lapangan') {
            $query->whereHas('penugasanPembimbing', fn ($q) => $q->where('pembimbing_id', $user->id));
        }

        $today = now()->toDateString();
        $counts = $query
            ->selectRaw("SUM(CASE WHEN status = 'berjalan' THEN 1 ELSE 0 END) as berjalan, SUM(CASE WHEN status IN ('diajukan', 'verifikasi_dokumen', 'proses_approval') THEN 1 ELSE 0 END) as menunggu, SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai, SUM(CASE WHEN status = 'berjalan' AND tanggal_selesai <= ? THEN 1 ELSE 0 END) as jatuh_tempo", [$today])
            ->first();

        $totalBerjalan = (int) ($counts->berjalan ?? 0);
        $totalMenunggu = (int) ($counts->menunggu ?? 0);
        $totalSelesai = (int) ($counts->selesai ?? 0);
        $totalJatuhTempo = (int) ($counts->jatuh_tempo ?? 0);

        $stats = [
            Stat::make('PKL/Penelitian Aktif', $totalBerjalan)
                ->description('Sedang berjalan saat ini')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning'),

            Stat::make('Menunggu Proses', $totalMenunggu)
                ->description('Perlu verifikasi atau disposisi')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('Total Selesai', $totalSelesai)
                ->description('PKL/Penelitian yang sudah tuntas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];

        if ($user?->role?->slug === 'pic') {
            $stats[] = Stat::make('Perlu Diselesaikan', $totalJatuhTempo)
                ->description('Target selesai sudah terlewat, segera input nilai/tutup')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($totalJatuhTempo > 0 ? 'danger' : 'success');
        }

        return $stats;
    }
}
