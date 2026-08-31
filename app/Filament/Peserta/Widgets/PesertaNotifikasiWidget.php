<?php

namespace App\Filament\Peserta\Widgets;

use App\Models\Notifikasi;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PesertaNotifikasiWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pemberitahuan Terbaru')
            ->query(
                Notifikasi::query()
                    ->where('user_id', Auth::id())
                    ->latest()
            )
            ->columns([
                Tables\Columns\IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('gray')
                    ->falseColor('primary'),

                Tables\Columns\TextColumn::make('judul')
                    ->label('Aktivitas')
                    ->weight(fn ($record) => $record->is_read ? 'normal' : 'bold'),

                Tables\Columns\TextColumn::make('pesan')
                    ->label('Detail')
                    ->wrap()
                    ->limit(120),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y'),
            ])
            ->recordAction(null)
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
