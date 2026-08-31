<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Pengajuans\PengajuanResource;
use App\Filament\Resources\Pengajuans\Tables\PengajuansTable;
use App\Models\Pengajuan;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PerluTindakanWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    // Sesuai redesign dashboard: tabel "Perlu Tindakan Anda" full width,
    // "Aktivitas Terbaru" dipindah ke bawahnya (lihat RecentActivityWidget),
    // bukan lagi berdampingan di grid 3 kolom.
    protected int|string|array $columnSpan = 'full';

    /**
     * GM/Kabag SDM/Staff SDM/Kepala Bagian sekarang punya halaman khusus
     * "Tugas Saya" dengan aksi langsung di tabel — widget ini cukup untuk
     * PIC & Pembimbing Lapangan yang masih pakai resource Pengajuan lengkap.
     */
    public static function canView(): bool
    {
        return ! in_array(Auth::user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm', 'kepala_bagian']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Perlu Tindakan Anda')
            ->query($this->queryPerRole())
            ->columns([
                Tables\Columns\TextColumn::make('peserta.user.name')
                    ->label('Peserta')
                    ->searchable()
                    ->icon('heroicon-o-user-circle')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('jenis_pengajuan')->label('Jenis'),
                Tables\Columns\TextColumn::make('bagian.nama_bagian')->label('Bagian')->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => PengajuansTable::labelStatus($state))
                    ->color(fn (string $state) => PengajuansTable::warnaStatus($state)),
            ])
            ->recordActions([
                Action::make('proses')
                    ->authorize(fn (Pengajuan $record) => Auth::user()?->can('view', $record) === true)
                    ->label('Proses')
                    ->url(fn (Pengajuan $record) => PengajuanResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([5, 10])
            ->emptyStateHeading('Tidak ada pengajuan yang perlu ditindak')
            ->emptyStateDescription('Semua pengajuan yang jadi tanggung jawab Anda sudah diproses.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    protected function queryPerRole(): Builder
    {
        $user = Auth::user();
        $roleSlug = $user?->role?->slug;

        $query = Pengajuan::query();

        return match ($roleSlug) {
            'pic' => $query->whereIn('status', ['diajukan', 'dokumen_ditolak', 'verifikasi_dokumen', 'disetujui']),

            'pembimbing_lapangan' => $query->where('status', 'berjalan')
                ->whereHas('penugasanPembimbing', fn ($q) => $q->where('pembimbing_id', $user->id)),

            default => $query->whereRaw('1 = 0'),
        };
    }
}
