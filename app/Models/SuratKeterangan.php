<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Surat Keterangan Selesai PKL/Penelitian, atau Surat Perpanjangan PKL/
 * Penelitian (langkah 17-18 flowchart AS-IS). Lihat migration
 * `create_surat_keterangans_table` untuk konteks bisnisnya.
 */
class SuratKeterangan extends Model
{
    protected $fillable = ['pengajuan_id', 'jenis', 'nomor_surat', 'file_path', 'generated_by', 'generated_at'];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    /** @return BelongsTo<Pengajuan, $this> */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isSelesai(): bool
    {
        return $this->jenis === 'selesai';
    }
}
