<?php

namespace App\Support\Documents;

use App\Models\ApprovalWorkflow;
use App\Models\DokumenPersyaratan;
use App\Models\Evaluasi;
use App\Models\Pengajuan;
use App\Models\Penilaian;
use App\Models\Perpanjangan;
use App\Models\SuratBalasan;
use App\Models\SuratKeterangan;

/**
 * Central registry of database columns that contain sensitive document paths.
 *
 * Add new private document fields here so authorization endpoints, migration
 * tooling, and future maintenance jobs do not drift apart.
 */
final class PrivateDocumentRegistry
{
    /** @return array<int, string> */
    public static function pengajuanFields(): array
    {
        return [
            'file_surat_pengantar',
            'file_cv',
            'file_proposal',
            'file_ktp_ktm',
            'file_transkrip',
            'file_pas_foto',
            'file_data_penelitian',
            'file_bpjs_ketenagakerjaan',
            'file_surat_kampus_perpanjangan',
        ];
    }

    public static function isSafePath(?string $path): bool
    {
        if (! is_string($path) || trim($path) === '' || str_contains($path, "\0")) {
            return false;
        }

        $normalized = str_replace('\\', '/', trim($path));

        if (str_starts_with($normalized, '/')
            || preg_match('/^[A-Za-z]:\//', $normalized) === 1
            || str_contains($normalized, '://')
            || preg_match('/[\x00-\x1F\x7F]/', $normalized) === 1) {
            return false;
        }

        $segments = explode('/', $normalized);

        return ! in_array('..', $segments, true) && ! in_array('.', $segments, true);
    }

    /** @return array<class-string, array<int, string>> */
    public static function modelFields(): array
    {
        return [
            Pengajuan::class => self::pengajuanFields(),
            DokumenPersyaratan::class => ['file_path'],
            ApprovalWorkflow::class => ['file_path'],
            SuratBalasan::class => ['file_path'],
            SuratKeterangan::class => ['file_path'],
            Penilaian::class => ['file_pdf'],
            Evaluasi::class => ['file_bukti'],
            Perpanjangan::class => ['surat_kampus_path'],
        ];
    }
}
