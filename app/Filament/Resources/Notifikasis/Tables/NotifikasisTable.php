<?php

namespace App\Filament\Resources\Notifikasis\Tables;

use App\Models\Notifikasi;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class NotifikasisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-s-envelope')
                    ->trueColor('gray')
                    ->falseColor('warning'),
                TextColumn::make('judul')->label('Judul')->weight(fn ($record) => $record->is_read ? 'normal' : 'bold'),
                TextColumn::make('pesan')->label('Pesan')->wrap()->limit(80),
                TextColumn::make('pengajuan.nomor_agenda')->label('No. Agenda')->placeholder('—'),
                TextColumn::make('created_at')->label('Waktu')->since()->sortable(),
            ])
            ->recordActions([
                Action::make('tandaiSudahDibaca')
                    ->authorize(fn (Notifikasi $record) => Auth::user()?->can('view', $record) === true)
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-o-check')
                    ->visible(fn ($record) => ! $record->is_read)
                    ->action(fn ($record) => $record->update(['is_read' => true])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
