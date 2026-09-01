<?php

namespace App\Filament\Resources\ApprovalWorkflows\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApprovalWorkflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Disposisi')
                ->columns(2)
                ->components([
                    Select::make('pengajuan_id')
                        ->label('Pengajuan')
                        ->relationship('pengajuan', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->nomor_agenda ?? ('Pengajuan #'.$record->id))
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('urutan')
                        ->label('Urutan')
                        ->options([
                            1 => '1 - GM',
                            2 => '2 - Kepala Bagian SDM',
                            3 => '3 - Staff SDM',
                            4 => '4 - Kepala Bagian Tujuan',
                        ])
                        ->required(),

                    Select::make('penandatangan_id')
                        ->label('Penandatangan')
                        ->relationship('penandatangan', 'name')
                        ->searchable()
                        ->preload(),

                    Select::make('status')
                        ->options([
                            'menunggu' => 'Menunggu',
                            'ditandatangani' => 'Ditandatangani',
                        ])
                        ->default('menunggu')
                        ->required(),

                    DateTimePicker::make('diproses_at'),

                    Textarea::make('catatan')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
