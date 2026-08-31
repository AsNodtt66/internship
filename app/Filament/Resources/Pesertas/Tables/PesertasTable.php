<?php

namespace App\Filament\Resources\Pesertas\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PesertasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Belum ada peserta')
            ->emptyStateDescription('Data peserta otomatis terbentuk begitu pengajuan PKL/Magang mereka mulai diproses.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->columns([
                TextColumn::make('user.name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('nim')->label('NIM')->searchable(),
                TextColumn::make('universitas')->searchable(),
                TextColumn::make('jurusan')->searchable(),
                TextColumn::make('user.email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
;
    }
}