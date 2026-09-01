<?php

namespace App\Filament\Resources\ApprovalWorkflows\Tables;

use App\Enums\RoleSlug;
use App\Models\ApprovalWorkflow;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ApprovalWorkflowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Surat yang baru ditandatangani ditaruh paling atas, biar
            // dengan ratusan baris pun yang perlu diproses tetap kelihatan
            // duluan tanpa harus scroll/cari manual.
            ->defaultSort('diproses_at', 'desc')
            ->columns([
                TextColumn::make('pengajuan.peserta.user.name')
                    ->label('Nama Peserta')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('pengajuan.nomor_agenda')
                    ->label('No. Agenda')
                    ->placeholder('Belum ada')
                    ->searchable(),

                TextColumn::make('urutan')
                    ->label('Tahap')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => 'GM',
                        2 => 'Kepala Bagian SDM',
                        3 => 'Staff SDM',
                        4 => 'Kepala Bagian Tujuan',
                        default => (string) $state,
                    })
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'info',
                        2 => 'warning',
                        3 => 'success',
                        4 => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu' => 'warning',
                        'ditandatangani' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('penandatangan.name')
                    ->label('Ditandatangani Oleh')
                    ->placeholder('—'),

                TextColumn::make('diproses_at')
                    ->label('Waktu Tanda Tangan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('urutan')
                    ->label('Tahap')
                    ->options([
                        1 => 'GM',
                        2 => 'Kepala Bagian SDM',
                        3 => 'Staff SDM',
                        4 => 'Kepala Bagian Tujuan',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'ditandatangani' => 'Ditandatangani',
                        'ditolak' => 'Ditolak',
                    ]),

                // Filter andalan untuk volume besar: langsung nyaring surat
                // yang sudah ditandatangani tapi belum "ditindaklanjuti/
                // diteruskan" oleh PIC -> ini backlog yang harus dikejar.
                TernaryFilter::make('belum_diteruskan')
                    ->label('Belum Diteruskan')
                    ->queries(
                        true: fn ($query) => $query->where('status', 'ditandatangani')->whereNull('diteruskan_at'),
                        false: fn ($query) => $query->whereNotNull('diteruskan_at'),
                        blank: fn ($query) => $query,
                    ),

                Filter::make('peserta')
                    ->schema([
                        TextInput::make('nama')->label('Cari Nama Peserta'),
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        $data['nama'] ?? null,
                        fn ($q, $nama) => $q->whereHas(
                            'pengajuan.peserta.user',
                            fn ($uq) => $uq->where('name', 'like', "%{$nama}%")
                        )
                    )),
            ])
            ->recordActions([
                Action::make('cetakPdf')
                    ->authorize(fn (ApprovalWorkflow $record) => Auth::user()?->can('view', $record) === true)
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->visible(fn (ApprovalWorkflow $record) => $record->status === 'ditandatangani')
                    ->url(fn (ApprovalWorkflow $record) => route('disposisi.cetak', $record))
                    ->openUrlInNewTab(),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('tandaiDiteruskanMassal')
                        ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
                        ->label('Tandai Diteruskan (Massal)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function (ApprovalWorkflow $record) {
                                if ($record->status === 'ditandatangani' && blank($record->diteruskan_at)) {
                                    $record->update([
                                        'diteruskan_at' => now(),
                                        'diteruskan_oleh_id' => Auth::id(),
                                    ]);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
