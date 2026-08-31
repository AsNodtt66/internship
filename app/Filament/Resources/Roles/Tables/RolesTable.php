<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_role')->searchable()->sortable(),
                TextColumn::make('slug')->badge()->searchable(),
                TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}