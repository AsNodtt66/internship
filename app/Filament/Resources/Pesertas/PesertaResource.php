<?php

namespace App\Filament\Resources\Pesertas;

use App\Filament\Resources\Pesertas\Schemas\PesertaForm;
use App\Filament\Resources\Pesertas\Tables\PesertasTable;
use App\Models\Peserta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PesertaResource extends Resource
{
    protected static ?string $model = Peserta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static UnitEnum|string|null $navigationGroup = 'Manajemen Magang';

    protected static ?string $navigationLabel = 'Data Peserta';

    public static function form(Schema $schema): Schema
    {
        return PesertaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PesertasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->role?->slug === 'pic';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPesertas::route('/'),
            'create' => Pages\CreatePeserta::route('/create'),
            'edit' => Pages\EditPeserta::route('/{record}/edit'),
        ];
    }
}
