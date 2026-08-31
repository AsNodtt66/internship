<?php

namespace App\Filament\Resources\FormFieldDefinitions;

use App\Filament\Resources\FormFieldDefinitions\Pages;
use App\Models\FormFieldDefinition;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * PIC bisa nambah/hapus/edit sendiri pertanyaan tambahan yang muncul di
 * form registrasi peserta dan/atau form pengajuan PKL, tanpa perlu minta
 * ubah kode. Field inti (nama, NIM, tujuan, tanggal, dst.) TIDAK lewat
 * sini -- itu tetap tertanam di kode seperti biasa. Lihat
 * App\Services\DynamicFormFieldBuilder untuk bagaimana ini dirender jadi
 * form sungguhan, dan kolom `data_tambahan` di tabel pesertas/pengajuans
 * untuk bagaimana jawabannya disimpan.
 */
class FormFieldDefinitionResource extends Resource
{
    protected static ?string $model = FormFieldDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Field Tambahan';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('target')
                ->label('Muncul di Form')
                ->options([
                    'registrasi_peserta' => 'Registrasi Akun Peserta',
                    'pengajuan' => 'Pengajuan PKL / Magang / Penelitian',
                ])
                ->required(),

            TextInput::make('label')
                ->label('Pertanyaan / Label Field')
                ->placeholder('mis. Golongan Darah, Ukuran Baju, Nomor BPJS')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true),

            TextInput::make('key')
                ->label('Kode Field (otomatis dari label, boleh diedit)')
                ->helperText('Huruf kecil & underscore saja, tidak boleh sama dengan field lain pada form yang sama.')
                ->maxLength(100)
                ->rule('regex:/^[a-z0-9_]+$/'),

            Select::make('tipe')
                ->label('Jenis Isian')
                ->options([
                    'text' => 'Teks Singkat',
                    'textarea' => 'Teks Panjang',
                    'number' => 'Angka',
                    'date' => 'Tanggal',
                    'select' => 'Pilihan Dropdown',
                    'checkbox' => 'Centang (Ya/Tidak)',
                    'file' => 'Unggah Berkas',
                ])
                ->required()
                ->live(),

            Repeater::make('opsi')
                ->label('Daftar Pilihan')
                ->simple(TextInput::make('nilai')->required())
                ->visible(fn (Get $get) => $get('tipe') === 'select')
                ->addActionLabel('Tambah Pilihan')
                ->minItems(1),

            Toggle::make('wajib_diisi')
                ->label('Wajib Diisi')
                ->default(false),

            TextInput::make('urutan')
                ->label('Urutan Tampil')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('target')
                    ->label('Muncul di')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'registrasi_peserta' => 'Registrasi Peserta',
                        'pengajuan' => 'Pengajuan PKL',
                        default => $state,
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('label')
                    ->label('Pertanyaan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('Kode')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tipe')
                    ->label('Jenis'),

                Tables\Columns\IconColumn::make('wajib_diisi')
                    ->label('Wajib')
                    ->boolean(),

                Tables\Columns\TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->defaultSort('urutan')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->role?->slug === 'pic';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormFieldDefinitions::route('/'),
            'create' => Pages\CreateFormFieldDefinition::route('/create'),
            'edit' => Pages\EditFormFieldDefinition::route('/{record}/edit'),
        ];
    }
}
