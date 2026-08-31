<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Services\PengajuanWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CetakFormulirEvaluasiController extends Controller
{
    /**
     * Fallback kalau PIC tidak mengisi aspek manual sama sekali saat
     * membuat formulir (jarang terjadi karena field-nya wajib diisi
     * minimal 1, tapi tetap disiapkan sebagai jaring pengaman).
     */
    private const ASPEK_PENILAIAN_DEFAULT = [
        'Disiplin',
        'Kemampuan memiliki prioritas',
        'Kemampuan bekerja sama',
        'Kemampuan bekerja secara mandiri',
        'Kedisiplinan dalam bekerja',
        'Kecepatan kerja',
        'Kemampuan menyelesaikan hal baru',
        'Kemampuan berkomunikasi',
        'Kemampuan analisis terhadap pekerjaan',
        'Kemampuan memberikan solusi',
    ];

    public function show(Pengajuan $pengajuan): Response
    {
        Gate::authorize('viewEvaluation', $pengajuan);

        // Pembuatan record Evaluasi sekarang terjadi di modal "Buat Formulir
        // Evaluasi" (PengajuansTable::cetakFormulirEvaluasiAction), di mana
        // PIC sudah menentukan aspek penilaiannya secara manual. Rute ini
        // murni tinggal mencetak PDF dari aspek yang sudah tersimpan itu.
        // Fallback create tetap disiapkan untuk akses langsung/lama.
        if (! $pengajuan->evaluasi && Auth::user()->role?->slug === 'pic') {
            app(PengajuanWorkflowService::class)->buatFormulirEvaluasi($pengajuan, Auth::user(), self::ASPEK_PENILAIAN_DEFAULT);
            $pengajuan->refresh();
        }

        abort_if(! $pengajuan->evaluasi, 404, 'Formulir evaluasi belum bisa dibuat — pastikan Pembimbing Lapangan sudah ditetapkan.');

        $pengajuan->load(['peserta.user', 'bagianTujuan', 'penugasanPembimbing.pembimbing']);

        $pdf = Pdf::loadView('pdf.formulir-evaluasi', [
            'pengajuan' => $pengajuan,
            'aspekList' => $pengajuan->evaluasi->aspek_penilaian_default ?: self::ASPEK_PENILAIAN_DEFAULT,
        ]);

        return $pdf->stream("formulir-evaluasi-{$pengajuan->id}.pdf");
    }
}