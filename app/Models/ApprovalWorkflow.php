<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalWorkflow extends Model
{
    protected $fillable = ['pengajuan_id', 'urutan', 'penandatangan_id', 'status', 'catatan', 'file_path', 'diproses_at', 'diteruskan_oleh_id', 'diteruskan_at'];

    protected $casts = [
        'diproses_at' => 'datetime',
        'diteruskan_at' => 'datetime',
    ];

    /** @return BelongsTo<Pengajuan, $this> */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function penandatangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penandatangan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function diteruskanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diteruskan_oleh_id');
    }
}
