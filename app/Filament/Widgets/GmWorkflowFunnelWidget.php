<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use App\Services\PengajuanWorkflowService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Menampilkan di tahap mana saja pengajuan yang sedang berjalan berada
 * (Verifikasi PIC -> GM -> Kabag SDM -> Staff SDM -> Kepala Bagian Tujuan
 * -> Penetapan Pembimbing -> Berjalan/Selesai). Relevan untuk keempat role
 * disposisi karena menunjukkan konteks: seberapa banyak antrean di
 * belakang & di depan tahap masing-masing.
 */
class GmWorkflowFunnelWidget extends Widget
{
    protected string $view = 'filament.widgets.gm-workflow-funnel';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        // The dashboard's base grid has one column, so no explicit base span
        // renders identically to `full` while matching Filament's contract.
        'default' => null,
        'lg' => 1,
    ];

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm', 'kepala_bagian'], true);
    }

    public function getTahapan(): array
    {
        $tahapAktif = app(PengajuanWorkflowService::class)->hitungTahapAktif();
        $roleSaya = Auth::user()?->role?->slug;

        $counts = Pengajuan::query()
            ->selectRaw("SUM(CASE WHEN status IN ('diajukan', 'verifikasi_dokumen', 'dokumen_ditolak') THEN 1 ELSE 0 END) as verifikasi_pic, SUM(CASE WHEN status = 'menunggu_penetapan_pembimbing' THEN 1 ELSE 0 END) as penetapan_pembimbing, SUM(CASE WHEN status IN ('berjalan', 'selesai', 'perlu_perpanjangan') THEN 1 ELSE 0 END) as berjalan_selesai")
            ->first();

        $verifikasiPic = (int) ($counts->verifikasi_pic ?? 0);
        $gm = $tahapAktif[1] ?? 0;
        $kabagSdm = $tahapAktif[2] ?? 0;
        $staffSdm = $tahapAktif[3] ?? 0;
        $kepalaBagian = $tahapAktif[4] ?? 0;
        $penetapanPembimbing = (int) ($counts->penetapan_pembimbing ?? 0);
        $berjalanSelesai = (int) ($counts->berjalan_selesai ?? 0);

        // Urutan sesuai alur disposisi terbaru: GM -> Kabag SDM -> Staff SDM
        // -> Kepala Bagian Tujuan (tanda tangan + catatan calon pembimbing).
        $tahapan = [
            ['label' => 'Verifikasi PIC', 'total' => $verifikasiPic, 'warna' => '#93AEE0', 'role' => null],
            ['label' => 'GM', 'total' => $gm, 'warna' => '#6E9AF0', 'role' => 'gm'],
            ['label' => 'Kabag SDM', 'total' => $kabagSdm, 'warna' => '#4C7EE0', 'role' => 'kabag_sdm'],
            ['label' => 'Staff SDM', 'total' => $staffSdm, 'warna' => '#D97706', 'role' => 'staff_sdm'],
            ['label' => 'Kepala Bagian Tujuan', 'total' => $kepalaBagian, 'warna' => '#7C3AED', 'role' => 'kepala_bagian'],
            ['label' => 'Penetapan Pembimbing', 'total' => $penetapanPembimbing, 'warna' => '#1B3B6F', 'role' => null],
            ['label' => 'Berjalan / Selesai', 'total' => $berjalanSelesai, 'warna' => '#047857', 'final' => true, 'role' => null],
        ];

        $maks = max(1, collect($tahapan)->max('total'));

        return collect($tahapan)->map(function ($t) use ($maks, $roleSaya) {
            $t['persen'] = max(6, round($t['total'] / $maks * 100));

            // Tandai tahap yang sesuai dengan role yang SEDANG LOGIN sebagai
            // "(Anda)" dan aktif — dulu ini selalu nempel ke GM, sekarang
            // ikut Kabag SDM/Staff SDM juga kalau memang mereka yang login.
            if ($t['role'] !== null && $t['role'] === $roleSaya) {
                $t['label'] .= ' (Anda)';
                $t['aktif'] = true;
            }

            return $t;
        })->all();
    }
}
