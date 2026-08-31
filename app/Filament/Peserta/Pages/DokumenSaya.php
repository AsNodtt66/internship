<?php

namespace App\Filament\Peserta\Pages;

use App\Enums\RoleSlug;
use App\Models\DokumenPersyaratan;
use App\Models\Pengajuan;
use App\Services\PengajuanWorkflowService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Facades\Auth;

class DokumenSaya extends Page implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.peserta.pages.dokumen-saya';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Dokumen Saya';

    protected static ?int $navigationSort = 30;

    public ?Pengajuan $pengajuan = null;

    public array $dokumen = [];

    public function mount(): void
    {
        $this->pengajuan = Pengajuan::whereHas('peserta', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->first();

        if ($this->pengajuan) {
            $this->dokumen = $this->pengajuan->dokumenPersyaratans()->get()->toArray();
        }
    }

    public function labelStatusDokumen(string $status): string
    {
        return match ($status) {
            'lengkap' => 'Lengkap',
            'tidak_lengkap' => 'Perlu Revisi',
            default => 'Menunggu Verifikasi',
        };
    }

    public function warnaStatusDokumen(string $status): string
    {
        return match ($status) {
            'lengkap' => 'sipkl-badge-success',
            'tidak_lengkap' => 'sipkl-badge-danger',
            default => 'sipkl-badge-warning',
        };
    }

    public function perbaikiDokumenAction(): Action
    {
        return Action::make('perbaikiDokumen')
            ->authorize(fn (array $arguments) => Auth::user()?->hasRole(RoleSlug::PESERTA) === true
                && DokumenPersyaratan::query()->whereKey($arguments['dokumenId'] ?? 0)
                    ->whereHas('pengajuan.peserta', fn ($query) => $query->where('user_id', Auth::id()))
                    ->exists())
            ->label('Unggah Perbaikan')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('danger')
            ->size('sm')
            ->modalHeading('Unggah Perbaikan Dokumen')
            ->schema([
                FileUpload::make('file_path')
                    ->label('Berkas Pengganti')
                    ->helperText('Gunakan PDF, JPG, atau PNG yang jelas dan sesuai catatan PIC.')
                    ->disk(config('filesystems.private_documents_disk', 'documents'))
                    ->visibility('private')
                    ->directory('dokumen-persyaratan')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240)
                    ->required(),
            ])
            ->action(function (array $arguments, array $data) {
                $dokumen = DokumenPersyaratan::findOrFail($arguments['dokumenId']);

                app(PengajuanWorkflowService::class)->perbaikiDokumen($dokumen, $data['file_path'], Auth::user());

                Notification::make()->title('Perbaikan dokumen tersimpan')->body('PIC akan memeriksa kembali berkas yang Anda unggah.')->success()->send();

                $this->mount();
            });
    }
}
