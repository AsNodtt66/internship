<?php

namespace App\Filament\Peserta\Resources\PengajuanResource\Pages;

use App\Filament\Peserta\Resources\PengajuanResource;
use App\Filament\Peserta\Widgets\PesertaPengajuanStatsWidget;
use App\Filament\Peserta\Widgets\PesertaPengajuanStatusChartWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPengajuans extends ListRecords
{
    protected static string $resource = PengajuanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Ringkasan (angka + grafik) di atas tabel supaya peserta bisa memantau
     * progres SELURUH pengajuannya sekilas, tanpa buka satu-satu baris.
     * Diwarisi otomatis oleh menu "Pengajuan PKL/Magang" & "Pengajuan
     * Penelitian" (lihat ListPengajuanPkl & ListPengajuanPenelitian, cuma
     * extends class ini) -- sengaja TIDAK dipisah per jenis pengajuan,
     * supaya peserta yang punya riwayat PKL sekaligus Penelitian tetap
     * lihat gambaran lengkap di kedua menu.
     */
    protected function getHeaderWidgets(): array
    {
        return [
            PesertaPengajuanStatsWidget::class,
            PesertaPengajuanStatusChartWidget::class,
        ];
    }
}
