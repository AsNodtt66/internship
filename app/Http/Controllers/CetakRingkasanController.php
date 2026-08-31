<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CetakRingkasanController extends Controller
{
    public function show(Pengajuan $pengajuan): Response
    {
        Gate::authorize('view', $pengajuan);

        $pengajuan->load(['peserta.user', 'bagianTujuan', 'penugasanPembimbing.pembimbing']);

        $pdf = Pdf::loadView('pdf.ringkasan-pengajuan', ['pengajuan' => $pengajuan]);

        return $pdf->stream("ringkasan-pengajuan-{$pengajuan->id}.pdf");
    }
}
