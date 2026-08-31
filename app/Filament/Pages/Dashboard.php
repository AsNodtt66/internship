<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        return match (Auth::user()?->role?->slug) {
            'gm' => 'Ringkasan Persetujuan',
            'kabag_sdm', 'staff_sdm' => 'Ringkasan Tugas',
            'kepala_bagian' => 'Ringkasan Bagian',
            'pembimbing_lapangan' => 'Ringkasan Bimbingan',
            'pic' => 'Ringkasan Operasional',
            default => 'Dashboard',
        };
    }

    public function getSubheading(): ?string
    {
        return match (Auth::user()?->role?->slug) {
            'gm' => 'Pantau pengajuan yang menunggu persetujuan dan perkembangan program secara ringkas.',
            'kabag_sdm', 'staff_sdm' => 'Prioritaskan pengajuan yang sedang menunggu tindakan Anda.',
            'kepala_bagian' => 'Pantau pengajuan untuk bagian Anda, penempatan, dan tindak lanjut yang diperlukan.',
            'pembimbing_lapangan' => 'Lihat peserta yang Anda bimbing dan kegiatan yang membutuhkan tindak lanjut.',
            'pic' => 'Pantau verifikasi, penempatan, dokumen, dan pekerjaan operasional yang perlu diproses.',
            default => null,
        };
    }

    /**
     * Grid 3 kolom supaya "Perlu Tindakan Anda" (span 2) dan "Aktivitas
     * Terbaru" (span 1) bisa berdampingan di baris yang sama, sesuai desain.
     */
    public function getColumns(): int|array
    {
        return [
            'md' => 3,
            'default' => 1,
        ];
    }
}
