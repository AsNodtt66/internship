<?php

namespace App\Filament\Peserta\Pages;

use App\Filament\Peserta\Resources\PengajuanResource;
use App\Models\Pengajuan;
use App\Services\PengajuanTimelineService;
use App\Support\Ui\PengajuanStatusPresenter;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected string $view = 'filament.peserta.pages.dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Beranda';

    protected static ?int $navigationSort = 0;

    public ?Pengajuan $pengajuan = null;

    public array $steps = [];

    public array $dokumen = [];

    public array $notifikasi = [];

    public int $totalPengajuan = 0;

    public function mount(): void
    {
        $this->pengajuan = Pengajuan::whereHas('peserta', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->first();

        $this->totalPengajuan = Pengajuan::whereHas('peserta', fn ($q) => $q->where('user_id', Auth::id()))->count();

        if ($this->pengajuan) {
            $this->steps = app(PengajuanTimelineService::class)->build($this->pengajuan);
            $this->dokumen = $this->pengajuan->dokumenPersyaratans()->get()->toArray();
        }

        $this->notifikasi = \App\Models\Notifikasi::where('user_id', Auth::id())
            ->latest()
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function labelStatus(string $status): string
    {
        return PengajuanStatusPresenter::label($status);
    }

    public function descriptionStatus(string $status): string
    {
        return PengajuanStatusPresenter::description($status);
    }

    public function getDetailUrl(): ?string
    {
        return $this->pengajuan ? PengajuanResource::getUrl('view', ['record' => $this->pengajuan]) : null;
    }

    public function getEditUrl(): ?string
    {
        return $this->pengajuan ? PengajuanResource::getUrl('edit', ['record' => $this->pengajuan]) : null;
    }

    public function canEdit(): bool
    {
        return $this->pengajuan && in_array($this->pengajuan->status, ['draft', 'dokumen_ditolak']);
    }

    public function getSuratBalasanUrl(): ?string
    {
        $surat = $this->pengajuan?->suratBalasan;

        return $surat ? route('documents.surat-balasan', $surat) : null;
    }

    public function getSuratKeteranganUrl(): ?string
    {
        $surat = $this->pengajuan?->suratKeterangan;

        return $surat ? route('documents.surat-keterangan', $surat) : null;
    }

    public function getSuratKeteranganLabel(): string
    {
        $surat = $this->pengajuan?->suratKeterangan;

        return $surat?->isSelesai() ? 'Unduh Surat Keterangan Selesai' : 'Unduh Surat Perpanjangan';
    }
}