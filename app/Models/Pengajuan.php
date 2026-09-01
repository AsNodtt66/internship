<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengajuan extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**
     * Casting tipe data otomatis
     */
    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'diteruskan_ke_kabag_at' => 'datetime',
        'data_tambahan' => 'array',
        'punya_bpjs_ketenagakerjaan' => 'boolean',
        'pengingat_perpanjangan_terkirim_at' => 'datetime',
    ];

    /** @return BelongsTo<Peserta, $this> */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'peserta_id');
    }

    /** @return BelongsTo<Bagian, $this> */
    public function bagian(): BelongsTo
    {
        return $this->belongsTo(Bagian::class, 'bagian_tujuan_id');
    }

    /** @return BelongsTo<Bagian, $this> */
    public function bagianTujuan(): BelongsTo
    {
        return $this->bagian();
    }

    /** @return HasMany<DokumenPersyaratan, $this> */
    public function dokumenPersyaratans(): HasMany
    {
        return $this->hasMany(DokumenPersyaratan::class);
    }

    /** @return HasMany<ApprovalWorkflow, $this> */
    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class);
    }

    /** @return HasOne<PenugasanPembimbing, $this> */
    public function penugasanPembimbing(): HasOne
    {
        return $this->hasOne(PenugasanPembimbing::class);
    }

    /** @return HasOne<SuratBalasan, $this> */
    public function suratBalasan(): HasOne
    {
        return $this->hasOne(SuratBalasan::class);
    }

    /** @return HasOne<Evaluasi, $this> */
    public function evaluasi(): HasOne
    {
        return $this->hasOne(Evaluasi::class);
    }

    /** @return HasMany<Perpanjangan, $this> */
    public function perpanjangans(): HasMany
    {
        return $this->hasMany(Perpanjangan::class);
    }

    /** @return HasOne<Penilaian, $this> */
    public function penilaian(): HasOne
    {
        return $this->hasOne(Penilaian::class);
    }

    /** @return HasOne<SuratKeterangan, $this> */
    public function suratKeterangan(): HasOne
    {
        return $this->hasOne(SuratKeterangan::class);
    }

    /** @return HasMany<RiwayatStatus, $this> */
    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatus::class)->orderByDesc('created_at');
    }

    /** @return BelongsTo<Pengajuan, $this> */
    public function pengajuanAsal(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_asal_id');
    }

    /** @return HasOne<Pengajuan, $this> */
    public function pengajuanPerpanjangan(): HasOne
    {
        return $this->hasOne(Pengajuan::class, 'pengajuan_asal_id');
    }
}
