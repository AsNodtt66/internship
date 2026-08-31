<?php

namespace App\Filament\Peserta\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Ringkasan jumlah SELURUH pengajuan milik peserta yang login (PKL/Magang
 * maupun Penelitian digabung -- ditampilkan sebagai header widget di kedua
 * menu "Pengajuan Saya", lihat ListPengajuans::getHeaderWidgets()) supaya
 * peserta bisa memantau progres tanpa harus membuka satu per satu baris di
 * tabel. Beda dari PesertaStatsOverview (widget lama, dipakai admin di
 * halaman detail satu peserta) yang cuma menampilkan pengajuan TERBARU --
 * widget ini menghitung SEMUA baris milik peserta, dikelompokkan per status.
 */
class PesertaPengajuanStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    /**
     * Status yang sedang berada di tahap verifikasi/disposisi (belum
     * berjalan, belum final) -- dianggap "perlu perhatian" karena peserta
     * biasanya menunggu tindakan pihak lain (PIC/GM/Kabag/Staff SDM).
     *
     * @var array<int, string>
     */
    private const STATUS_DIPROSES = [
        'diajukan', 'verifikasi_dokumen', 'dokumen_ditolak', 'proses_approval',
        'menunggu_catatan_pembimbing', 'menunggu_penetapan_pembimbing',
    ];

    protected function getStats(): array
    {
        $query = Pengajuan::whereHas('peserta', fn ($q) => $q->where('user_id', Auth::id()));

        $jumlahPerStatus = (clone $query)->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $total = $jumlahPerStatus->sum();
        $diproses = collect(self::STATUS_DIPROSES)->sum(fn ($status) => $jumlahPerStatus[$status] ?? 0);
        $berjalan = $jumlahPerStatus['berjalan'] ?? 0;
        $perluPerpanjangan = $jumlahPerStatus['perlu_perpanjangan'] ?? 0;
        $selesai = $jumlahPerStatus['selesai'] ?? 0;

        return [
            Stat::make('Total Pengajuan', $total)
                ->description('PKL/Magang & Penelitian')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Dalam Proses', $diproses)
                ->description('Menunggu verifikasi/disposisi')
                ->descriptionIcon('heroicon-m-clock')
                ->color($diproses > 0 ? 'warning' : 'gray'),

            Stat::make('Sedang Berjalan', $berjalan)
                ->description($perluPerpanjangan > 0 ? "{$perluPerpanjangan} perlu keputusan perpanjangan" : 'PKL/Penelitian aktif')
                ->descriptionIcon($perluPerpanjangan > 0 ? 'heroicon-m-arrow-path' : 'heroicon-m-play-circle')
                ->color($perluPerpanjangan > 0 ? 'danger' : 'success'),

            Stat::make('Selesai', $selesai)
                ->description('PKL/Penelitian tuntas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
