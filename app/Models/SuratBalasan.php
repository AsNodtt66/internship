<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Surat Balasan PKL/Penelitian.
 *
 * Sesuai alur TO-BE: PIC membuat DRAFT surat ini bersamaan dengan
 * mengusulkan Pembimbing Lapangan (generated_by/generated_at = PIC).
 * Surat baru resmi ('terbit') setelah Kepala Bagian Tujuan menyetujui
 * usulan pembimbing (diterbitkan_oleh/diterbitkan_at = Kepala Bagian).
 */
class SuratBalasan extends Model
{
    protected $fillable = [
        'pengajuan_id', 'nomor_surat', 'file_path', 'status',
        'generated_by', 'generated_at',
        'diterbitkan_oleh', 'diterbitkan_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'diterbitkan_at' => 'datetime',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /** PIC yang membuat draft */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** Kepala Bagian yang menyetujui & menerbitkan resmi */
    public function diterbitkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterbitkan_oleh');
    }
}