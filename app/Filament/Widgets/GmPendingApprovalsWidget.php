<?php

namespace App\Filament\Widgets;

use App\Enums\RoleSlug;
use App\Filament\Resources\Pengajuans\PengajuanResource;
use App\Models\Pengajuan;
use App\Services\PengajuanWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Ringkasan pengajuan yang giliran/tahapnya sedang di GM, lengkap dengan
 * aksi Tandatangani langsung di baris tabel (sesuai wireframe executive
 * dashboard). Tahap GM hanya "mengetahui dan menandatangani", tidak ada
 * opsi menolak. Logikanya tetap memanggil satu-satunya sumber kebenaran,
 * PengajuanWorkflowService::tandatanganiLangkah(), yang sama juga dipakai
 * halaman "Persetujuan Pengajuan" (TugasSaya) — jadi tidak ada duplikasi
 * logika, hanya duplikasi titik akses.
 */
class GmPendingApprovalsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pengajuan Menunggu Persetujuan Anda';

    public static function canView(): bool
    {
        return Auth::user()?->role?->slug === 'gm';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Pengajuan::query()
                    ->where('status', 'proses_approval')
                    ->whereHas('approvalWorkflows', function ($q) {
                        $q->where('urutan', 1)->where('status', 'menunggu');
                    })
                    ->with(['approvalWorkflows' => fn ($q) => $q->where('urutan', 1)->where('status', 'menunggu')])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('peserta.user.name')->label('Nama Peserta')->default('-'),
                Tables\Columns\TextColumn::make('peserta.universitas')->label('Universitas')->default('-'),
                Tables\Columns\TextColumn::make('bagian.nama_bagian')->label('Bagian Tujuan')->badge()->color('info'),
                Tables\Columns\TextColumn::make('jenis_pengajuan')->label('Jenis'),
                Tables\Columns\TextColumn::make('created_at')->label('Diajukan')->date('d M Y'),
            ])
            ->recordActions([
                Action::make('detail')
                    ->authorize(fn (Pengajuan $record) => Auth::user()?->can('view', $record) === true)
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Pengajuan $record) => PengajuanResource::getUrl('view', ['record' => $record])),

                Action::make('tandatangani')
                    ->authorize(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::GM) === true
                        && $record->approvalWorkflows->isNotEmpty())
                    ->label('Tandatangani')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Tandatangani Surat Pengajuan')
                    ->modalDescription('Tahap ini hanya untuk mengetahui dan menandatangani surat pengajuan. Tidak ada opsi menolak di tahap manapun dari GM/Kabag SDM/Staff SDM.')
                    ->schema([
                        Textarea::make('catatan')->label('Catatan (opsional)'),
                    ])
                    ->action(fn (Pengajuan $record, array $data) => $this->proses($record, $data['catatan'] ?? null)),
            ])
            ->paginated([5, 10])
            ->emptyStateHeading('Tidak ada pengajuan yang menunggu')
            ->emptyStateDescription('Semua pengajuan yang jadi giliran Anda sudah diproses.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    protected function proses(Pengajuan $record, ?string $catatan): void
    {
        $step = $record->approvalWorkflows()
            ->where('urutan', 1)
            ->where('status', 'menunggu')
            ->first();

        if (! $step) {
            Notification::make()->title('Bukan giliran Anda')->danger()->send();

            return;
        }

        try {
            app(PengajuanWorkflowService::class)->tandatanganiLangkah($step, Auth::user(), $catatan);

            Notification::make()->title('Pengajuan ditandatangani')->success()->send();
        } catch (\RuntimeException $e) {
            Notification::make()->title('Gagal diproses')->body($e->getMessage())->danger()->send();
        }
    }
}
