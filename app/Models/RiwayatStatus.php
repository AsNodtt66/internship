<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatus extends Model
{
    protected $table = 'riwayat_status';

    protected $fillable = [
        'pengajuan_id',
        'changed_by',
        'status_sebelumnya',
        'status_baru',
        'keterangan',
    ];

    /**
     * Relasi ke data Pengajuan yang statusnya berubah.
     */
    /** @return BelongsTo<Pengajuan, $this> */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /**
     * Relasi ke User (Staff SDM/Admin) yang mengubah status.
     */
    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
