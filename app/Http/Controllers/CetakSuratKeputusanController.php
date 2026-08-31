<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\Perpanjangan;
use App\Models\SuratKeterangan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CetakSuratKeputusanController extends Controller
{
    /**
     * Surat Keterangan Selesai PKL — flowchart langkah 17-18: "Lulus ->
     * PIC menerbitkan Surat Keterangan Selesai PKL".
     *
     * PENTING: sebelumnya method ini hanya men-stream PDF ke browser PIC
     * tanpa pernah menyimpan file-nya maupun membuat record
     * `SuratKeterangan`. Akibatnya peserta tidak pernah bisa menerima
     * suratnya, karena tombol download di sisi peserta (lihat
     * ViewPengajuan::getHeaderActions()) bergantung pada
     * `$pengajuan->suratKeterangan !== null`. Sekarang file disimpan ke
     * private document storage dan record dibuat begitu surat pertama kali dicetak,
     * supaya nomor surat konsisten dan peserta bisa mengunduhnya.
     */
    public function suratKeteranganSelesai(Pengajuan $pengajuan): Response
    {
        Gate::authorize('issueCompletionDocument', $pengajuan);
        abort_unless($pengajuan->status === 'selesai' && $pengajuan->evaluasi?->hasil === 'selesai', 404,
            'Surat ini hanya bisa diterbitkan untuk pengajuan yang sudah selesai dengan hasil Selesai.');

        $pengajuan->load(['peserta.user', 'bagianTujuan', 'evaluasi', 'penugasanPembimbing.pembimbing', 'suratKeterangan']);

        $suratKeterangan = $pengajuan->suratKeterangan;

        // Kalau sudah pernah diterbitkan, jangan generate ulang (nomor surat
        // harus tetap sama) -- cukup sajikan file yang sudah tersimpan.
        if (! $suratKeterangan) {
            $nomorSurat = 'SKL/'.$pengajuan->id.'/'.now()->format('m/Y');

            $pdf = Pdf::loadView('pdf.surat-keterangan-selesai', [
                'pengajuan' => $pengajuan,
                'nomorSurat' => $nomorSurat,
            ]);

            $filePath = "surat-keterangan/selesai-{$pengajuan->id}-".now()->timestamp.'.pdf';
            Storage::disk(config('filesystems.private_documents_disk', 'documents'))->put($filePath, $pdf->output());

            $suratKeterangan = SuratKeterangan::create([
                'pengajuan_id' => $pengajuan->id,
                'jenis' => 'selesai',
                'nomor_surat' => $nomorSurat,
                'file_path' => $filePath,
                'generated_by' => Auth::id(),
                'generated_at' => now(),
            ]);
        }

        return Storage::disk(config('filesystems.private_documents_disk', 'documents'))->response($suratKeterangan->file_path, basename($suratKeterangan->file_path), ['Cache-Control' => 'private, no-store']);
    }

    /**
     * Surat Perpanjangan PKL — flowchart langkah 17-18: "Belum memenuhi
     * standar -> PIC menerbitkan Surat Perpanjangan PKL". Hanya bisa
     * dicetak setelah Kepala Bagian Tujuan menyetujui perpanjangannya.
     *
     * Sama seperti suratKeteranganSelesai() di atas -- sebelumnya cuma
     * stream tanpa menyimpan record, sehingga peserta tidak pernah
     * menerima surat perpanjangannya. Sekarang disimpan dengan jenis
     * `perpanjangan` pada tabel `surat_keterangans` yang sama.
     */
    public function suratPerpanjangan(Perpanjangan $perpanjangan): Response
    {
        Gate::authorize('issueCompletionDocument', $perpanjangan->pengajuan);
        abort_unless($perpanjangan->status === 'disetujui', 404,
            'Surat ini hanya bisa diterbitkan setelah perpanjangan disetujui Kepala Bagian Tujuan.');

        $perpanjangan->load(['pengajuan.peserta.user', 'pengajuan.bagianTujuan', 'pengajuan.suratKeterangan']);

        $pengajuan = $perpanjangan->pengajuan;
        $suratKeterangan = $pengajuan->suratKeterangan;

        if (! $suratKeterangan) {
            $nomorSurat = 'SPJ/'.$perpanjangan->id.'/'.now()->format('m/Y');

            $pdf = Pdf::loadView('pdf.surat-perpanjangan', [
                'perpanjangan' => $perpanjangan,
                'pengajuan' => $pengajuan,
                'nomorSurat' => $nomorSurat,
            ]);

            $filePath = "surat-keterangan/perpanjangan-{$perpanjangan->id}-".now()->timestamp.'.pdf';
            Storage::disk(config('filesystems.private_documents_disk', 'documents'))->put($filePath, $pdf->output());

            $suratKeterangan = SuratKeterangan::create([
                'pengajuan_id' => $pengajuan->id,
                'jenis' => 'perpanjangan',
                'nomor_surat' => $nomorSurat,
                'file_path' => $filePath,
                'generated_by' => Auth::id(),
                'generated_at' => now(),
            ]);
        }

        return Storage::disk(config('filesystems.private_documents_disk', 'documents'))->response($suratKeterangan->file_path, basename($suratKeterangan->file_path), ['Cache-Control' => 'private, no-store']);
    }
}