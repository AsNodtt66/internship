<?php

namespace App\Filament\Resources\Pesertas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PesertaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Akun User (Peserta)')
                ->relationship(
                    name: 'user',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn ($query) => $query->whereHas(
                        'role',
                        fn ($q) => $q->where('slug', 'peserta')
                    ),
                )
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Hanya menampilkan user dengan role Peserta'),

            TextInput::make('nim')
                ->label('NIM')
                ->maxLength(255),

            TextInput::make('universitas')
                ->required()
                ->maxLength(255),

            TextInput::make('jurusan')
                ->required()
                ->maxLength(255),

            TextInput::make('no_hp')
                ->tel()
                ->maxLength(20),

            Textarea::make('alamat')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}
