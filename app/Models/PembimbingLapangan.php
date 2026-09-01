<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Data master Pembimbing Lapangan -- sumber dropdown saat PIC mengusulkan
 * pembimbing (lihat PengajuanWorkflowService::usulkanPembimbing()).
 * TIDAK WAJIB punya akun login (user_id nullable & opsional).
 */
class PembimbingLapangan extends Model
{
    protected $fillable = ['nama', 'jabatan', 'no_hp', 'bagian_id', 'user_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Bagian, $this> */
    public function bagian(): BelongsTo
    {
        return $this->belongsTo(Bagian::class);
    }

    /** Opsional -- hanya terisi kalau pembimbing ini memang punya akun login. */
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PenugasanPembimbing, $this> */
    public function penugasanPembimbings(): HasMany
    {
        return $this->hasMany(PenugasanPembimbing::class);
    }
}
