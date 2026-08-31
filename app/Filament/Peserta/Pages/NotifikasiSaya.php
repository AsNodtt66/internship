<?php

namespace App\Filament\Peserta\Pages;

use App\Models\Notifikasi;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class NotifikasiSaya extends Page
{
    protected string $view = 'filament.peserta.pages.notifikasi-saya';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Notifikasi';

    protected static ?int $navigationSort = 50;

    public function getNotifikasi()
    {
        return Notifikasi::where('user_id', Auth::id())->latest()->get();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Notifikasi::where('user_id', Auth::id())->where('is_read', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public function tandaiDibaca(int $id): void
    {
        Notifikasi::where('id', $id)->where('user_id', Auth::id())->update(['is_read' => true]);
    }

    public function tandaiSemuaDibaca(): void
    {
        Notifikasi::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);
    }
}
