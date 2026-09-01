<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Hitung data real dari database
        $sedangBerjalan = Pengajuan::where('status', 'approved')->count();
        $menungguProses = Pengajuan::where('status', 'pending')->count();
        $selesai = Pengajuan::where('status', 'rejected')->count(); // atau kondisi selesai Anda

        return [
            Stat::make('Sedang Berjalan', $sedangBerjalan)
                ->description('PKL/Penelitian aktif')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                // Menjadikan kartu bisa diklik mengarah ke tabel pengajuan yang disetujui
                ->url(route('filament.admin.resources.pengajuans.index', [
                    'tableFilters[status][value]' => 'approved',
                ])),

            Stat::make('Menunggu Proses', $menungguProses)
                ->description('Verifikasi & disposisi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.pengajuans.index', [
                    'tableFilters[status][value]' => 'pending',
                ])),

            Stat::make('Selesai', $selesai)
                ->description('Total PKL/Penelitian selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('gray')
                ->url(route('filament.admin.resources.pengajuans.index')),
        ];
    }
}
