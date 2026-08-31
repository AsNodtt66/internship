<?php

namespace App\Filament\Resources\FormFieldDefinitions\Pages;

use App\Filament\Resources\FormFieldDefinitions\FormFieldDefinitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFormFieldDefinitions extends ListRecords
{
    protected static string $resource = FormFieldDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
