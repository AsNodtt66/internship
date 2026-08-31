<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bagian extends Model
{
    protected $fillable = ['nama_bagian', 'kepala_bagian_id'];

    public function kepalaBagian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepala_bagian_id');
    }

    public function pengajuans(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'bagian_tujuan_id');
    }

    public function pembimbingLapangans(): HasMany
    {
        return $this->hasMany(PembimbingLapangan::class);
    }
}