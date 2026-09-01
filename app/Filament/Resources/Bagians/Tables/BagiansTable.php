<?php

namespace App\Filament\Resources\Bagians\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BagiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_bagian')->searchable()->sortable(),
                TextColumn::make('kepalaBagian.name')->label('Kepala Bagian')->placeholder('— belum ditentukan —'),
                TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
