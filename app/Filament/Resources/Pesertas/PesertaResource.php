<?php

namespace App\Filament\Resources\Pesertas;

use App\Models\Peserta;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Resources\Pesertas\Pages;
use App\Filament\Resources\Pesertas\Schemas\PesertaForm;
use App\Filament\Resources\Pesertas\Tables\PesertasTable;

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
        return \Illuminate\Support\Facades\Auth::user()?->role?->slug === 'pic';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPesertas::route('/'),
            'create' => Pages\CreatePeserta::route('/create'),
            'edit'   => Pages\EditPeserta::route('/{record}/edit'),
        ];
    }
}