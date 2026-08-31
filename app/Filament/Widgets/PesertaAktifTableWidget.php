<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use App\Support\Authorization\PengajuanAccess;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PesertaAktifTableWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Daftar Peserta PKL / Penelitian Sedang Berjalan';

    /**
     * GM hanya berperan di tahap persetujuan, bukan pengelolaan data peserta
     * yang sedang berjalan — jadi tabel ini disembunyikan dari dashboardnya.
     */
    public static function canView(): bool
    {
        return ! in_array(auth()->user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm'], true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ($user = Auth::user())
                    ? PengajuanAccess::scope(Pengajuan::query(), $user)->where('status', 'berjalan')->latest()
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

                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn () => 'Sedang Berjalan'),
            ]);
    }
}