<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use App\Models\RiwayatStatus;
use App\Support\Authorization\PengajuanAccess;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * "Widget Aktivitas Terbaru" di sisi dashboard (PIC, Pembimbing, dst).
 * GM sudah punya versi full-width sendiri (GmRecentActivityWidget) yang
 * lebih detail, jadi widget ini sengaja disembunyikan untuk role GM.
 */
class RecentActivityWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-activity';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ! in_array(Auth::user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm'], true);
    }

    public function getAktivitas(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        return RiwayatStatus::with(['pengajuan.peserta.user', 'changedBy'])
            ->whereIn('pengajuan_id', PengajuanAccess::scope(Pengajuan::query(), $user)->select('id'))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (RiwayatStatus $riwayat) => [
                'waktu' => $riwayat->created_at,
                'peserta' => $riwayat->pengajuan?->peserta?->user->name ?? 'Peserta',
                'keterangan' => $riwayat->keterangan
                    ?? "Status berubah menjadi {$riwayat->status_baru}",
            ])
            ->values()
            ->all();
    }
}
