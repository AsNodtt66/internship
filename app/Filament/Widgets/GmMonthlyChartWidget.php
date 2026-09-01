<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class GmMonthlyChartWidget extends ChartWidget
{
    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Pengajuan PKL/Penelitian per Bulan';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        // The dashboard's base grid has one column, so no explicit base span
        // renders identically to `full` while matching Filament's contract.
        'default' => null,
        'lg' => 1,
    ];

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm'], true);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $tahun = now()->year;

        $startOfYear = now()->startOfYear();
        $startOfNextYear = $startOfYear->copy()->addYear();

        // One portable SQL aggregate instead of loading every row for the year
        // into PHP. CASE + date ranges work on both SQLite (local/tests) and
        // MySQL (production), and the outer range can use the created_at index.
        $cases = [];
        $bindings = [];
        foreach (range(1, 12) as $bulan) {
            $start = $startOfYear->copy()->month($bulan)->startOfMonth();
            $end = $start->copy()->addMonth();
            $cases[] = "SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) AS bulan_{$bulan}";
            $bindings[] = $start;
            $bindings[] = $end;
        }

        $row = Pengajuan::query()
            ->where('created_at', '>=', $startOfYear)
            ->where('created_at', '<', $startOfNextYear)
            ->selectRaw(implode(', ', $cases), $bindings)
            ->first();

        $namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $data = collect(range(1, 12))->map(fn ($bulan) => (int) ($row->{"bulan_{$bulan}"} ?? 0));

        return [
            'datasets' => [
                [
                    'label' => "Pengajuan $tahun",
                    'data' => $data->values()->all(),
                    'backgroundColor' => '#F59E0B',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $namaBulan,
        ];
    }
}
