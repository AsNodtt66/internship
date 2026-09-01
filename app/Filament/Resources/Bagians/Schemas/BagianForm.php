<?php

namespace App\Filament\Resources\Bagians\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BagianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_bagian')
                ->required()
                ->maxLength(255),

            Select::make('kepala_bagian_id')
                ->label('Kepala Bagian')
                ->relationship('kepalaBagian', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
        ]);
    }
}
