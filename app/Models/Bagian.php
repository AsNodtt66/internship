<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bagian extends Model
{
    protected $fillable = ['nama_bagian', 'kepala_bagian_id'];

    /** @return BelongsTo<User, $this> */
    public function kepalaBagian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepala_bagian_id');
    }

    /** @return HasMany<Pengajuan, $this> */
    public function pengajuans(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'bagian_tujuan_id');
    }

    /** @return HasMany<PembimbingLapangan, $this> */
    public function pembimbingLapangans(): HasMany
    {
        return $this->hasMany(PembimbingLapangan::class);
    }
}
