<?php

namespace App\Filament\Resources\FormFieldDefinitions\Pages;

use App\Filament\Resources\FormFieldDefinitions\FormFieldDefinitionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFormFieldDefinition extends EditRecord
{
    protected static string $resource = FormFieldDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
