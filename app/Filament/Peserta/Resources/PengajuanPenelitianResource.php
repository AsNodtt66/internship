<?php

namespace App\Filament\Peserta\Resources;

use App\Filament\Peserta\Resources\PengajuanPenelitianResource\Pages;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menu "Pengajuan Penelitian" (peserta). Lihat komentar di
 * PengajuanPklResource -- pola yang sama persis, tidak ada logic baru,
 * murni reuse lewat extends supaya tidak ada backend/resource duplikat.
 */
class PengajuanPenelitianResource extends PengajuanResource
{
    protected static ?string $navigationLabel = 'Pengajuan Penelitian';

    protected static ?string $modelLabel = 'Pengajuan Penelitian';

    protected static ?string $pluralModelLabel = 'Pengajuan Penelitian Saya';

    protected static ?string $slug = 'pengajuan-penelitian';

    protected static ?int $navigationSort = 20;

    /**
     * Menu sidebar terpisah "Pengajuan Penelitian" TETAP ADA (dikembalikan
     * setelah sempat disembunyikan di patch 14 -- user minta menu lama
     * jangan dihapus, cuma halaman Lihat Detail yang dirapikan).
     */
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('jenis_pengajuan', 'Penelitian');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuanPenelitian::route('/'),
            'create' => Pages\CreatePengajuanPenelitian::route('/create'),
            'view' => Pages\ViewPengajuanPenelitian::route('/{record}'),
            'edit' => Pages\EditPengajuanPenelitian::route('/{record}/edit'),
        ];
    }
}
