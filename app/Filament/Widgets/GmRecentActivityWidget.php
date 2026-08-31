<?php

namespace App\Filament\Widgets;

use App\Models\RiwayatStatus;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * "Aktivitas Terbaru" pada executive dashboard GM — daftar perubahan status
 * pengajuan paling baru (diambil dari riwayat_status) supaya GM punya
 * gambaran cepat apa yang baru terjadi tanpa perlu buka tiap pengajuan.
 */
class GmRecentActivityWidget extends Widget
{
    protected string $view = 'filament.widgets.gm-recent-activity';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm'], true);
    }

    public function getAktivitas(): array
    {
        return RiwayatStatus::with(['pengajuan.peserta.user', 'changedBy'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (RiwayatStatus $riwayat) => [
                'waktu' => $riwayat->created_at,
                'peserta' => $riwayat->pengajuan?->peserta?->user?->name ?? 'Peserta',
                'pelaku' => $riwayat->changedBy?->name,
                'keterangan' => $riwayat->keterangan
                    ?? "Status berubah dari {$riwayat->status_sebelumnya} menjadi {$riwayat->status_baru}",
                'status_baru' => $riwayat->status_baru,
                'warna' => $this->warnaStatus($riwayat->status_baru),
            ])
            ->values()
            ->all();
    }

    protected function warnaStatus(?string $status): string
    {
        return match ($status) {
            'ditolak' => 'danger',
            'disetujui', 'selesai', 'berjalan' => 'success',
            'proses_approval', 'verifikasi_dokumen' => 'warning',
            default => 'gray',
        };
    }
}
