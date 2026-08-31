<?php

namespace App\Filament\Peserta\Widgets;

use App\Models\DokumenPersyaratan;
use App\Models\Pengajuan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PesertaDokumenStatusWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Status Dokumen')
            ->query(
                DokumenPersyaratan::query()
                    ->whereHas('pengajuan.peserta', fn (Builder $q) => $q->where('user_id', Auth::id()))
                    ->whereIn('pengajuan_id', function ($query) {
                        // Batasi ke pengajuan TERBARU milik peserta saja
                        $query->select('id')
                            ->from('pengajuans')
                            ->whereIn('peserta_id', function ($sub) {
                                $sub->select('id')->from('pesertas')->where('user_id', Auth::id());
                            })
                            ->latest()
                            ->limit(1);
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('jenis_dokumen')
                    ->label('Nama Dokumen'),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lengkap' => 'Lengkap',
                        'tidak_lengkap' => 'Perlu Revisi',
                        default => 'Menunggu Verifikasi',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'lengkap' => 'success',
                        'tidak_lengkap' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label('Diunggah')
                    ->dateTime('d M Y H:i'),

                Tables\Columns\TextColumn::make('catatan_verifikasi')
                    ->label('Catatan Revisi')
                    ->placeholder('—')
                    ->visible(fn ($record) => $record?->status_verifikasi === 'tidak_lengkap')
                    ->wrap(),
            ])
            ->paginated(false);
    }
}
