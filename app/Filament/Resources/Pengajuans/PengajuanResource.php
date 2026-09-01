<?php

namespace App\Filament\Resources\Pengajuans;

use App\Filament\Resources\Pengajuans\Schemas\PengajuanInfolist;
use App\Filament\Resources\Pengajuans\Tables\PengajuansTable;
use App\Models\Pengajuan;
use App\Support\Authorization\PengajuanAccess;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/** @extends resource<Pengajuan> */
class PengajuanResource extends Resource
{
    protected static ?string $model = Pengajuan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Pengajuan PKL/Magang';

    protected static ?string $modelLabel = 'Pengajuan';

    protected static ?string $pluralModelLabel = 'Pengajuan';

    /**
     * Batasi data yang muncul sesuai peran (selaras dengan PengajuanPolicy &
     * PengajuanStatsWidget):
     * - pic/staff_sdm/kabag_sdm/gm: lihat semua pengajuan.
     * - kepala_bagian: hanya pengajuan yang bagian tujuannya dia pimpin.
     * - pembimbing_lapangan: hanya pengajuan yang dia bimbing.
     *
     * @return Builder<Pengajuan>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        return $user ? PengajuanAccess::scope($query, $user) : $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        // Pengajuan hanya dibuat peserta lewat panel Peserta.
        // Staf internal di Admin panel hanya memproses, tidak membuat baru.
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PengajuanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PengajuansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuans::route('/'),
            'view' => Pages\ViewPengajuan::route('/{record}'),
        ];
    }

    /**
     * Role yang cuma perlu approve/tetapkan-pembimbing sudah dilayani oleh
     * halaman "Tugas Saya" yang lebih sederhana. Resource lengkap ini tetap
     * ada (untuk PIC & Pembimbing Lapangan), tapi disembunyikan dari sidebar
     * 4 role approval supaya tidak ada 2 tempat yang membingungkan.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return ! in_array(Auth::user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm', 'kepala_bagian']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
