<?php

namespace App\Filament\Resources\PembimbingLapangans\Pages;

use App\Filament\Resources\PembimbingLapangans\PembimbingLapanganResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPembimbingLapangan extends EditRecord
{
    protected static string $resource = PembimbingLapanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
