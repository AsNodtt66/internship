<?php

namespace App\Filament\Peserta\Widgets;

use App\Models\Pengajuan;
use App\Services\PengajuanTimelineService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PesertaProgressTimeline extends Widget
{
    protected string $view = 'filament.peserta.widgets.progress-timeline';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getPengajuan(): ?Pengajuan
    {
        return Pengajuan::whereHas('peserta', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->first();
    }

    public function getSteps(): array
    {
        $pengajuan = $this->getPengajuan();

        if (! $pengajuan) {
            return [];
        }

        return app(PengajuanTimelineService::class)->build($pengajuan);
    }
}
