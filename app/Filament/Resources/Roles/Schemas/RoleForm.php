<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_role')
                ->required()
                ->maxLength(255),

            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->disabled()
                ->dehydrated(false)
                ->helperText('Slug adalah identifier sistem dan tidak dapat diubah. Perubahan slug dapat memutus authorization dan workflow.'),
        ]);
    }
}