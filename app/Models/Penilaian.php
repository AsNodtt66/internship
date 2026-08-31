<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasil penilaian PDF yang diupload PIC (formulir fisik yang sudah diisi
 * & ditandatangani Pembimbing Lapangan), lalu peserta sendiri yang
 * memilih keputusan perpanjang/tidak berdasarkan hasil ini.
 */
class Penilaian extends Model
{
    protected $fillable = [
        'pengajuan_id', 'file_pdf', 'diupload_oleh', 'diupload_at', 'keputusan',
    ];

    protected $casts = [
        'diupload_at' => 'datetime',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /** PIC yang mengupload file PDF ini */
    public function diuploadOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diupload_oleh');
    }
}
