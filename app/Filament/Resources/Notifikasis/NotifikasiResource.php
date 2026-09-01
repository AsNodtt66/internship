<?php

namespace App\Filament\Resources\Notifikasis;

use App\Models\Notifikasi;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class NotifikasiResource extends Resource
{
    protected static ?string $model = Notifikasi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static UnitEnum|string|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Notifikasi';

    /**
     * Non-PIC hanya boleh lihat notifikasi milik sendiri. PIC dikecualikan
     * karena dia yang mengelola/broadcast notifikasi ke semua pihak lewat
     * resource ini (lihat form & actions di bawah).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()?->role?->slug !== 'pic') {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->label('Penerima'),

                Components\TextInput::make('judul')
                    ->label('Judul Notifikasi')
                    ->required()
                    ->maxLength(255),

                Components\Textarea::make('pesan')
                    ->label('Isi Pesan')
                    ->required()
                    ->columnSpanFull(),

                Components\Toggle::make('is_read')
                    ->label('Sudah Dibaca')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Penerima')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Dibaca')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Dikirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\Action::make('bukaSurat')
                    ->authorize(fn (Notifikasi $record) => Auth::user()?->can('view', $record) === true)
                    ->label('Buka Surat PDF')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->visible(fn (Notifikasi $record) => filled($record->approval_workflow_id))
                    ->url(fn (Notifikasi $record) => route('disposisi.cetak', $record->approval_workflow_id))
                    ->openUrlInNewTab(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifikasis::route('/'),
        ];
    }
}
