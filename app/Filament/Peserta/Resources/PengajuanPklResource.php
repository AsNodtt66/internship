<?php

namespace App\Filament\Peserta\Resources;

use App\Filament\Peserta\Resources\PengajuanPklResource\Pages;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menu "Pengajuan PKL/Magang" (peserta).
 *
 * SENGAJA tidak menduplikasi form()/table()/policy dari PengajuanResource --
 * semuanya diwarisi apa adanya lewat extends. Yang dioverride hanya:
 * - label & slug navigasi, supaya jadi menu sidebar sendiri
 * - getEloquentQuery(), menambah filter jenis_pengajuan di atas filter
 *   "milik peserta login" yang sudah ada di PengajuanResource
 * - getPages(), menunjuk ke Pages\* miliknya sendiri (masing-masing juga
 *   cuma extends Pages\* milik PengajuanResource, override $resource saja)
 */
class PengajuanPklResource extends PengajuanResource
{
    protected static ?string $navigationLabel = 'Pengajuan PKL/Magang';

    protected static ?string $modelLabel = 'Pengajuan PKL/Magang';

    protected static ?string $pluralModelLabel = 'Pengajuan PKL/Magang Saya';

    protected static ?string $slug = 'pengajuan-pkl-magang';

    protected static ?int $navigationSort = 10;

    /**
     * Menu sidebar terpisah "Pengajuan PKL/Magang" TETAP ADA (dikembalikan
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
            ->where('jenis_pengajuan', 'PKL/Magang');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuanPkl::route('/'),
            'create' => Pages\CreatePengajuanPkl::route('/create'),
            'view' => Pages\ViewPengajuanPkl::route('/{record}'),
            'edit' => Pages\EditPengajuanPkl::route('/{record}/edit'),
        ];
    }
}
