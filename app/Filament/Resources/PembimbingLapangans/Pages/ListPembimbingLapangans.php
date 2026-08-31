<?php

namespace App\Filament\Resources\PembimbingLapangans\Pages;

use App\Filament\Resources\PembimbingLapangans\PembimbingLapanganResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPembimbingLapangans extends ListRecords
{
    protected static string $resource = PembimbingLapanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
