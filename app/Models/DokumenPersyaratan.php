<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenPersyaratan extends Model
{
    protected $fillable = [
        'pengajuan_id', 'jenis_dokumen', 'file_path', 'status_verifikasi',
        'catatan_verifikasi', 'verified_by', 'verified_at', 'uploaded_at',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}