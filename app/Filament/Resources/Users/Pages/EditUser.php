<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

/** @property User $record */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $current = Auth::user();

        if ($current !== null && $current->is($this->record)) {
            // Server-side guard: a crafted Livewire request must not be able
            // to demote or deactivate the PIC account currently performing
            // the edit.
            $data['role_id'] = $this->record->role_id;
            $data['is_active'] = true;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
