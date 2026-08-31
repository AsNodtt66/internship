<?php

namespace App\Http\Controllers;

use App\Models\ApprovalWorkflow;
use App\Models\DokumenPersyaratan;
use App\Models\Evaluasi;
use App\Models\Pengajuan;
use App\Models\Penilaian;
use App\Models\Perpanjangan;
use App\Models\SuratBalasan;
use App\Models\SuratKeterangan;
use App\Support\Documents\PrivateDocumentRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateDocumentController extends Controller
{
    public function pengajuan(Pengajuan $pengajuan, string $field): StreamedResponse
    {
        Gate::authorize('view', $pengajuan);
        abort_unless(in_array($field, PrivateDocumentRegistry::pengajuanFields(), true), 404);

        return $this->stream($pengajuan->getAttribute($field));
    }

    public function persyaratan(DokumenPersyaratan $dokumen): StreamedResponse
    {
        Gate::authorize('view', $dokumen->pengajuan);

        return $this->stream($dokumen->file_path);
    }

    public function disposisi(ApprovalWorkflow $disposisi): StreamedResponse
    {
        Gate::authorize('view', $disposisi);

        return $this->stream($disposisi->file_path);
    }

    public function suratBalasan(SuratBalasan $surat): StreamedResponse
    {
        Gate::authorize('view', $surat->pengajuan);

        return $this->stream($surat->file_path);
    }

    public function suratKeterangan(SuratKeterangan $surat): StreamedResponse
    {
        Gate::authorize('view', $surat->pengajuan);

        return $this->stream($surat->file_path);
    }

    public function penilaian(Penilaian $penilaian): StreamedResponse
    {
        Gate::authorize('view', $penilaian->pengajuan);

        return $this->stream($penilaian->file_pdf);
    }

    public function evaluasi(Evaluasi $evaluasi): StreamedResponse
    {
        Gate::authorize('viewEvaluation', $evaluasi->pengajuan);

        return $this->stream($evaluasi->file_bukti);
    }

    public function perpanjangan(Perpanjangan $perpanjangan): StreamedResponse
    {
        Gate::authorize('view', $perpanjangan->pengajuan);

        return $this->stream($perpanjangan->surat_kampus_path);
    }

    private function stream(?string $path): StreamedResponse
    {
        abort_unless(PrivateDocumentRegistry::isSafePath($path), 404);

        $disk = Storage::disk(config('filesystems.private_documents_disk', 'documents'));
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, basename($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
