<?php

namespace App\Filament\Resources\PembimbingLapangans;

use App\Enums\RoleSlug;
use App\Models\PembimbingLapangan;
use App\Services\PengajuanWorkflowService;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * Data master Pembimbing Lapangan (nama, jabatan, no HP, bagian). Sesuai
 * aturan bisnis: akun login PEMBIMBING bersifat OPSIONAL -- yang wajib
 * cuma data pembimbingnya tersimpan & terhubung ke peserta. Kalau
 * pembimbing baru minta akses belakangan (di tengah PKL berjalan), pakai
 * tombol "Buatkan Akun Login" di sini -- lihat
 * PengajuanWorkflowService::buatkanAkunPembimbing().
 */
class PembimbingLapanganResource extends Resource
{
    protected static ?string $model = PembimbingLapangan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan Akses';

    protected static ?string $navigationLabel = 'Pembimbing Lapangan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Components\TextInput::make('nama')->label('Nama')->required()->maxLength(255),
            Components\TextInput::make('jabatan')->label('Jabatan')->maxLength(255),
            Components\TextInput::make('no_hp')->label('No. HP')->tel()->maxLength(20),
            Components\Select::make('bagian_id')
                ->label('Bagian')
                ->relationship('bagian', 'nama_bagian')
                ->searchable()
                ->preload(),
            Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('jabatan')->label('Jabatan')->placeholder('-'),
                Tables\Columns\TextColumn::make('bagian.nama_bagian')->label('Bagian')->placeholder('-'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Aktif'),
                Tables\Columns\IconColumn::make('user_id')
                    ->label('Punya Akun')
                    ->boolean()
                    ->getStateUsing(fn (PembimbingLapangan $record) => (bool) $record->user_id),
                Tables\Columns\TextColumn::make('penugasanPembimbings_count')
                    ->label('Peserta Dibimbing')
                    ->counts('penugasanPembimbings')
                    ->badge(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                self::buatkanAkunAction(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Kondisi C pada dokumen aturan bisnis: pembimbing minta akses SETELAH
     * PKL berjalan. Akun baru otomatis tersambung ke semua peserta yang
     * sudah dibimbing sebelumnya, tanpa mengulang pengajuan/approval.
     */
    protected static function buatkanAkunAction(): Actions\Action
    {
        return Actions\Action::make('buatkanAkun')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Buatkan Akun Login')
            ->icon('heroicon-o-key')
            ->color('primary')
            ->visible(fn (PembimbingLapangan $record) => ! $record->user_id)
            ->schema([
                Components\TextInput::make('nip')
                    ->label('NIP')
                    ->required()
                    ->rule('digits:6')
                    ->unique(table: 'users', column: 'nip', ignoreRecord: false)
                    ->helperText('6 digit angka. Pembimbing Lapangan login pakai NIP sebagai username maupun password awal.'),
            ])
            ->action(function (PembimbingLapangan $record, array $data) {
                try {
                    app(PengajuanWorkflowService::class)->buatkanAkunPembimbing(
                        $record,
                        $data['nip'],
                        $data['nip']
                    );

                    Notification::make()
                        ->title('Akun login dibuat')
                        ->body('Pembimbing langsung bisa melihat peserta & aktivitas yang sudah dibimbing sebelumnya.')
                        ->success()
                        ->send();
                } catch (RuntimeException $e) {
                    Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                }
            });
    }

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(Auth::user()?->role?->slug, ['pic', 'kepala_bagian'], true);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPembimbingLapangans::route('/'),
            'create' => Pages\CreatePembimbingLapangan::route('/create'),
            'edit' => Pages\EditPembimbingLapangan::route('/{record}/edit'),
        ];
    }
}
