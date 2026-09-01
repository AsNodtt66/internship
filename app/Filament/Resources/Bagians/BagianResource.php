<?php

namespace App\Filament\Resources\Bagians;

use App\Models\Bagian;
use Filament\Actions;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class BagianResource extends Resource
{
    protected static ?string $model = Bagian::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Data Bagian / Unit Kerja';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('nama_bagian')
                    ->label('Nama Bagian')
                    ->required()
                    ->maxLength(255),
                Components\Textarea::make('deskripsi')
                    ->label('Deskripsi Bagian')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_bagian')
                    ->label('Nama Bagian')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(50),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->action(function (Bagian $record, Actions\DeleteAction $action) {
                        // Bagian yang masih dipakai pengajuan (sebagai bagian tujuan)
                        // tidak boleh dihapus -- tampilkan pesan yang jelas ke user,
                        // bukan biarkan error SQL mentah muncul di layar.
                        if ($record->pengajuans()->exists()) {
                            Notification::make()
                                ->title('Bagian tidak bisa dihapus')
                                ->body('Bagian ini masih dipakai sebagai bagian tujuan di satu atau lebih data pengajuan. Pindahkan/ubah dulu data pengajuan yang terkait sebelum menghapus bagian ini.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->delete();
                    }),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->fetchSelectedRecords()
                        ->action(function (Collection $records): void {
                            // Reload the selected identifiers as Bagian models with one
                            // aggregate count query; Filament may otherwise inject a base collection.
                            $bagians = Bagian::query()
                                ->whereKey($records->modelKeys())
                                ->withCount('pengajuans')
                                ->get();
                            $terpakai = $bagians->filter(fn (Bagian $bagian): bool => $bagian->pengajuans_count > 0);

                            if ($terpakai->isNotEmpty()) {
                                Notification::make()
                                    ->title('Sebagian bagian tidak bisa dihapus')
                                    ->body('Bagian berikut masih dipakai di data pengajuan, jadi dilewati: '.$terpakai->pluck('nama_bagian')->join(', '))
                                    ->warning()
                                    ->send();
                            }

                            $bagians->reject(fn (Bagian $bagian): bool => $bagian->pengajuans_count > 0)
                                ->each->delete();
                        }),
                ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->role?->slug === 'pic';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBagians::route('/'),
            'create' => Pages\CreateBagian::route('/create'),
            'edit' => Pages\EditBagian::route('/{record}/edit'),
        ];
    }
}
