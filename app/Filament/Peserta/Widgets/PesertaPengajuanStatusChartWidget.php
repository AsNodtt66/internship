<?php

namespace App\Filament\Peserta\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Grafik donat proporsi status dari SELURUH pengajuan milik peserta yang
 * login -- pelengkap visual untuk PesertaPengajuanStatsWidget (angka) supaya
 * peserta bisa langsung melihat sebaran status tanpa buka tabel.
 */
class PesertaPengajuanStatusChartWidget extends ChartWidget
{
    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Sebaran Status Pengajuan Saya';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /**
     * Label & warna per status -- urutan array ini yang menentukan urutan
     * tampil di grafik. Warna disamakan dengan badge status di tabel/detail
     * (lihat PengajuanResource::table() & ViewPengajuan) supaya konsisten.
     *
     * @var array<string, array{label: string, color: string}>
     */
    private const LABEL_WARNA = [
        'draft' => ['label' => 'Draft', 'color' => '#9CA3AF'],
        'diajukan' => ['label' => 'Diajukan', 'color' => '#F59E0B'],
        'verifikasi_dokumen' => ['label' => 'Verifikasi Dokumen', 'color' => '#F59E0B'],
        'dokumen_ditolak' => ['label' => 'Dokumen Perlu Revisi', 'color' => '#EF4444'],
        'proses_approval' => ['label' => 'Proses Persetujuan', 'color' => '#F59E0B'],
        'menunggu_catatan_pembimbing' => ['label' => 'Menunggu Catatan Pembimbing', 'color' => '#F59E0B'],
        'menunggu_penetapan_pembimbing' => ['label' => 'Menunggu Penetapan Pembimbing', 'color' => '#F59E0B'],
        'berjalan' => ['label' => 'Berjalan', 'color' => '#22C55E'],
        'perlu_perpanjangan' => ['label' => 'Perlu Tindak Lanjut Perpanjangan', 'color' => '#F97316'],
        'selesai' => ['label' => 'Selesai', 'color' => '#15803D'],
        'ditolak' => ['label' => 'Ditolak', 'color' => '#EF4444'],
    ];

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $jumlahPerStatus = Pengajuan::whereHas('peserta', fn ($q) => $q->where('user_id', Auth::id()))
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        // Cuma tampilkan status yang benar-benar punya data, urutannya
        // ikut urutan LABEL_WARNA supaya konsisten tiap kali dibuka.
        $status = collect(self::LABEL_WARNA)->filter(fn ($_, $key) => ($jumlahPerStatus[$key] ?? 0) > 0);

        return [
            'datasets' => [
                [
                    'data' => $status->map(fn ($_, $key) => $jumlahPerStatus[$key])->values()->all(),
                    'backgroundColor' => $status->pluck('color')->values()->all(),
                ],
            ],
            'labels' => $status->pluck('label')->values()->all(),
        ];
    }
}
