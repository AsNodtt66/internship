<?php

namespace App\Filament\Peserta\Widgets;

use App\Filament\Peserta\Resources\PengajuanResource;
use App\Models\Pengajuan;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PesertaQuickActions extends Widget
{
    protected string $view = 'filament.peserta.widgets.quick-actions';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getPengajuan(): ?Pengajuan
    {
        return Pengajuan::whereHas('peserta', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->first();
    }

    public function getDetailUrl(): ?string
    {
        $pengajuan = $this->getPengajuan();

        return $pengajuan ? PengajuanResource::getUrl('view', ['record' => $pengajuan]) : null;
    }

    public function getSuratBalasanUrl(): ?string
    {
        $pengajuan = $this->getPengajuan();
        $surat = $pengajuan?->suratBalasan;

        return $surat ? route('documents.surat-balasan', $surat) : null;
    }

    public function getSuratKeteranganUrl(): ?string
    {
        $surat = $this->getPengajuan()?->suratKeterangan;

        return $surat ? route('documents.surat-keterangan', $surat) : null;
    }

    public function getSuratKeteranganLabel(): string
    {
        $surat = $this->getPengajuan()?->suratKeterangan;

        return $surat?->isSelesai()
            ? 'Unduh Surat Keterangan Selesai'
            : 'Unduh Surat Perpanjangan';
    }

    public function canEdit(): bool
    {
        $pengajuan = $this->getPengajuan();

        return $pengajuan && in_array($pengajuan->status, ['draft', 'dokumen_ditolak']);
    }

    public function getEditUrl(): ?string
    {
        $pengajuan = $this->getPengajuan();

        return $pengajuan ? PengajuanResource::getUrl('edit', ['record' => $pengajuan]) : null;
    }
}
