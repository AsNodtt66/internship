<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Pengajuans\PengajuanResource;
use App\Models\Pengajuan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Riwayat seluruh peserta di bagian ini yang sudah ditentukan Pembimbing
 * Lapangan-nya oleh Kepala Bagian Tujuan. Murni read-only (tidak ada aksi
 * ubah/hapus) — hanya jejak audit penempatan.
 */
class RiwayatPenempatan extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.riwayat-penempatan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Riwayat Penempatan';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Magang';

    protected static ?int $navigationSort = -1;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->role?->slug === 'kepala_bagian';
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public function getTitle(): string
    {
        return 'Riwayat Penempatan';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->columns([
                TextColumn::make('peserta.user.name')->label('Nama Peserta')->searchable(),
                TextColumn::make('peserta.universitas')->label('Universitas')->searchable(),
                TextColumn::make('penugasanPembimbing.pembimbing.name')->label('Pembimbing Lapangan'),
                TextColumn::make('penugasanPembimbing.ditetapkan_at')->label('Tanggal Penentuan')->dateTime('d M Y'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'selesai' => 'success',
                        'ditolak' => 'danger',
                        'perlu_perpanjangan' => 'warning',
                        default => 'primary',
                    }),
            ])
            ->recordActions([
                Action::make('detail')
                    ->authorize(fn (Pengajuan $record) => Auth::user()?->can('view', $record) === true)
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Pengajuan $record) => PengajuanResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum ada riwayat penempatan')
            ->emptyStateDescription('Peserta yang sudah Anda tetapkan pembimbingnya akan muncul di sini.')
            ->emptyStateIcon(Heroicon::OutlinedClock);
    }

    protected function query(): Builder
    {
        $user = Auth::user();

        return Pengajuan::query()
            ->whereHas('bagianTujuan', fn ($q) => $q->where('kepala_bagian_id', $user->id))
            ->whereHas('penugasanPembimbing')
            ->latest('updated_at');
    }
}
