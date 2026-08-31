<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Pengajuans\PengajuanResource;
use App\Models\Pengajuan;
use App\Support\Authorization\PengajuanAccess;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Daftar peserta yang statusnya sudah "selesai" — dipisah dari
 * PesertaAktifTableWidget (yang isinya peserta "berjalan") supaya atasan
 * bisa lihat riwayat kelulusan tanpa scroll campur dengan yang masih aktif.
 */
class PesertaSelesaiTableWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Peserta yang Telah Selesai PKL / Magang';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ($user = Auth::user())
                    ? PengajuanAccess::scope(Pengajuan::query(), $user)->where('status', 'selesai')->latest('tanggal_selesai')
                    : Pengajuan::query()->whereRaw('1 = 0')
            )
            ->columns([
                Tables\Columns\TextColumn::make('peserta.user.name')
                    ->label('Nama Peserta')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('peserta.universitas')
                    ->label('Sekolah / Universitas')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('bagian.nama_bagian')
                    ->label('Bagian Unit Kerja')
                    ->badge()
                    ->color('info')
                    ->default('-'),

                Tables\Columns\TextColumn::make('jenis_pengajuan')
                    ->label('Jenis'),

                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('suratKeterangan.id')
                    ->label('Surat Keterangan')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document')
                    ->getStateUsing(fn (Pengajuan $record) => filled($record->suratKeterangan)),
            ])
            ->recordActions([
                Action::make('detail')
                    ->authorize(fn (Pengajuan $record) => Auth::user()?->can('view', $record) === true)
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Pengajuan $record) => PengajuanResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->defaultSort('tanggal_selesai', 'desc')
            ->emptyStateHeading('Belum ada peserta yang selesai')
            ->emptyStateDescription('Peserta yang statusnya sudah "selesai" akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }
}
