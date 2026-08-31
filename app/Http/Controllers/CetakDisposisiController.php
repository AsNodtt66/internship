<?php

namespace App\Http\Controllers;

use App\Models\ApprovalWorkflow;
use App\Services\PengajuanWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CetakDisposisiController extends Controller
{
    public function show(ApprovalWorkflow $disposisi): Response
    {
        abort_unless($disposisi->status === 'ditandatangani', 404);

        Gate::authorize('view', $disposisi);

        $disposisi->load(['pengajuan.peserta.user', 'pengajuan.bagianTujuan', 'penandatangan']);

        $namaTahap = PengajuanWorkflowService::URUTAN_APPROVAL[$disposisi->urutan] ?? null;
        $labelTahap = match ($namaTahap) {
            'gm' => 'General Manager (GM)',
            'kabag_sdm' => 'Kepala Bagian SDM',
            'staff_sdm' => 'Staff SDM',
            'kepala_bagian' => 'Kepala Bagian Tujuan',
            default => 'Disposisi',
        };

        $pdf = Pdf::loadView('pdf.lembar-disposisi', [
            'disposisi' => $disposisi,
            'labelTahap' => $labelTahap,
        ]);

        return $pdf->stream("lembar-disposisi-{$labelTahap}-{$disposisi->pengajuan_id}.pdf");
    }
}
