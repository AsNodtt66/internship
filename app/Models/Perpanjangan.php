<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perpanjangan extends Model
{
    protected $fillable = [
        'pengajuan_id', 'pengajuan_baru_id', 'tanggal_mulai_baru', 'tanggal_selesai_baru',
        'alasan', 'surat_kampus_path', 'status',
    ];

    protected $casts = [
        'tanggal_mulai_baru' => 'date',
        'tanggal_selesai_baru' => 'date',
    ];

    /** @return BelongsTo<Pengajuan, $this> */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /**
     * Pengajuan BARU yang dibuat sistem begitu permohonan ini disetujui
     * Kepala Bagian (bukan pengajuan lama yang tanggalnya diubah).
     */
    /** @return BelongsTo<Pengajuan, $this> */
    public function pengajuanBaru(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_baru_id');
    }
}
