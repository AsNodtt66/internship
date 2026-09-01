<?php

namespace App\Services\Workflow;

use App\Models\Pengajuan;
use Illuminate\Support\Facades\DB;

/**
 * Sends one-time reminders when an active internship is approaching its end
 * and the participant has not made a continuation decision yet.
 */
class ExtensionReminderService
{
    public function __construct(private readonly WorkflowNotificationService $notifications) {}

    public function send(?int $daysBeforeEnd = null): int
    {
        $daysBeforeEnd ??= 14;
        $daysBeforeEnd = max(0, $daysBeforeEnd);
        $deadline = now()->addDays($daysBeforeEnd)->toDateString();
        $sent = 0;

        Pengajuan::query()
            ->where('status', 'berjalan')
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '<=', $deadline)
            ->whereNull('pengingat_perpanjangan_terkirim_at')
            ->whereDoesntHave('penilaian', fn ($query) => $query->whereNotNull('keputusan'))
            ->orderBy('id')
            ->chunkById(100, function ($pengajuans) use (&$sent): void {
                foreach ($pengajuans as $candidate) {
                    $didSend = DB::transaction(function () use ($candidate): bool {
                        $pengajuan = Pengajuan::query()
                            ->whereDoesntHave('penilaian', fn ($query) => $query->whereNotNull('keputusan'))
                            ->lockForUpdate()
                            ->find($candidate->id);

                        if (! $pengajuan
                            || $pengajuan->status !== 'berjalan'
                            || $pengajuan->pengingat_perpanjangan_terkirim_at !== null) {
                            return false;
                        }

                        $tanggalSelesai = $pengajuan->tanggal_selesai->format('d-m-Y');

                        $this->notifications->participant(
                            $pengajuan,
                            'Masa PKL/Penelitian Mendekati Selesai',
                            "Periode Anda berakhir pada {$tanggalSelesai}. Jika hasil penilaian sudah tersedia, segera tentukan keputusan perpanjangan."
                        );

                        $this->notifications->role(
                            $pengajuan,
                            'pic',
                            'Tindak Lanjut Akhir Periode',
                            "Pengajuan {$pengajuan->nomor_agenda} mendekati tanggal selesai ({$tanggalSelesai}) dan belum memiliki keputusan perpanjangan peserta."
                        );

                        $pengajuan->forceFill(['pengingat_perpanjangan_terkirim_at' => now()])->save();

                        return true;
                    }, 3);

                    if ($didSend) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
