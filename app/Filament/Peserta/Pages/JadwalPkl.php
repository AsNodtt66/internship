<?php

namespace App\Filament\Peserta\Pages;

use App\Models\Pengajuan;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class JadwalPkl extends Page
{
    protected string $view = 'filament.peserta.pages.jadwal-pkl';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Jadwal Kegiatan';

    protected static ?int $navigationSort = 40;

    public ?Pengajuan $pengajuan = null;

    public function mount(): void
    {
        $this->pengajuan = Pengajuan::whereHas('peserta', fn ($q) => $q->where('user_id', Auth::id()))
            ->with(['penugasanPembimbing.pembimbingLapangan', 'evaluasi', 'penilaian'])
            ->latest()
            ->first();
    }
}
