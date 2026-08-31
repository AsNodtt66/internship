<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class GmDepartmentUniversityWidget extends Widget
{
    protected string $view = 'filament.widgets.gm-department-university';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return in_array(Auth::user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm'], true);
    }

    public function getPerBagian(): array
    {
        $data = Pengajuan::query()
            ->leftJoin('bagians', 'bagians.id', '=', 'pengajuans.bagian_tujuan_id')
            ->selectRaw("COALESCE(bagians.nama_bagian, 'Tanpa Bagian') as nama, COUNT(*) as total")
            ->groupByRaw("COALESCE(bagians.nama_bagian, 'Tanpa Bagian')")
            ->orderByDesc('total')
            ->get();

        $maks = max(1, (int) ($data->max('total') ?? 1));

        return $data->map(fn ($row) => [
            'nama' => $row->nama,
            'total' => (int) $row->total,
            'persen' => max(6, round(((int) $row->total) / $maks * 100)),
        ])->values()->all();
    }

    public function getTopUniversitas(): array
    {
        return Pengajuan::query()
            ->leftJoin('pesertas', 'pesertas.id', '=', 'pengajuans.peserta_id')
            ->selectRaw("COALESCE(pesertas.universitas, 'Tidak diketahui') as nama, COUNT(*) as total")
            ->groupByRaw("COALESCE(pesertas.universitas, 'Tidak diketahui')")
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['nama' => $row->nama, 'total' => (int) $row->total])
            ->all();
    }
}
