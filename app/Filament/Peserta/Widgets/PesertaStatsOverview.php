<?php

namespace App\Filament\Peserta\Widgets;

use App\Models\Pengajuan;
use App\Support\Ui\PengajuanStatusPresenter;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PesertaStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pengajuan = $this->pengajuanTerbaru();

        if (! $pengajuan) {
            return [
                Stat::make('Total Pengajuan', 0)->color('gray'),
                Stat::make('Status', 'Belum Ada Pengajuan')->color('gray'),
                Stat::make('Jenis', '-')->color('gray'),
                Stat::make('Periode', '-')->color('gray'),
            ];
        }

        // Format tanggal secara aman menggunakan Carbon::parse()
        $tglMulai = Carbon::parse($pengajuan->tanggal_mulai)->format('d M Y');

        $tglSelesai = Carbon::parse($pengajuan->tanggal_selesai)->format('d M Y');

        return [
            Stat::make('Total Pengajuan', $this->totalPengajuan())
                ->color('primary'),

            Stat::make('Status', $this->labelStatus($pengajuan->status ?? ''))
                ->color($this->warnaStatus($pengajuan->status ?? '')),

            Stat::make('Jenis', $pengajuan->jenis_pengajuan ?? '-')
                ->color('gray'),

            Stat::make('Periode PKL', "{$tglMulai} - {$tglSelesai}")
                ->color('gray'),
        ];
    }

    protected function pengajuanTerbaru(): ?Pengajuan
    {
        $user = Auth::user();

        return Pengajuan::where(function ($query) use ($user) {
            $query->whereHas('peserta', fn ($q) => $q->where('user_id', $user->id))
                ->orWhere('email_aktif', $user->email);
        })
            ->latest()
            ->first();
    }

    protected function totalPengajuan(): int
    {
        $user = Auth::user();

        return Pengajuan::where(function ($query) use ($user) {
            $query->whereHas('peserta', fn ($q) => $q->where('user_id', $user->id))
                ->orWhere('email_aktif', $user->email);
        })->count();
    }

    protected function labelStatus(string $status): string
    {
        return PengajuanStatusPresenter::label($status);
    }

    protected function warnaStatus(string $status): string
    {
        return PengajuanStatusPresenter::color($status);
    }
}
