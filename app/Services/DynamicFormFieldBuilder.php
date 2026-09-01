<?php

namespace App\Services;

use App\Models\FormFieldDefinition;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

/**
 * Mengubah baris FormFieldDefinition (dibuat PIC lewat Master Data >
 * Field Tambahan) jadi komponen form Filament sungguhan -- dipakai baik
 * di form registrasi peserta maupun form pengajuan PKL/Magang/Penelitian.
 * Nilai yang diisi peserta disimpan ke kolom JSON `data_tambahan`
 * (lihat FormFieldValueMapper untuk extract/simpan-nya).
 */
class DynamicFormFieldBuilder
{
    /**
     * @return array<int, Component>
     */
    public function buildFor(string $target): array
    {
        return FormFieldDefinition::untuk($target)->get()
            ->map(fn (FormFieldDefinition $field) => $this->buildComponent($field))
            ->all();
    }

    protected function buildComponent(FormFieldDefinition $field): Component
    {
        $name = "data_tambahan.{$field->key}";

        $component = match ($field->tipe) {
            'textarea' => Textarea::make($name)->rows(3),
            'number' => TextInput::make($name)->numeric(),
            'date' => DatePicker::make($name),
            'select' => Select::make($name)->options(
                collect($field->opsi ?? [])->mapWithKeys(fn ($opsi) => [$opsi => $opsi])
            ),
            'checkbox' => Checkbox::make($name),
            'file' => FileUpload::make($name)
                ->directory('data-tambahan')
                ->visibility('private')
                ->acceptedFileTypes(config('uploads.dynamic.accepted_mime_types'))
                ->maxSize(config('uploads.dynamic.max_kb')),
            default => TextInput::make($name),
        };

        return $component
            ->label($field->label)
            ->required($field->wajib_diisi);
    }
}
