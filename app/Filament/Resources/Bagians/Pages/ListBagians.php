<?php

namespace App\Filament\Resources\Bagians\Pages;

use App\Filament\Resources\Bagians\BagianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBagians extends ListRecords
{
    protected static string $resource = BagianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
