<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Formulir evaluasi akhir PKL/Penelitian.
 *
 * Sesuai alur TO-BE: Pembimbing Lapangan tidak wajib login, jadi dia tidak
 * lagi jadi aktor yang menjadwalkan evaluasi/input nilai di sistem.
 * PIC PKL/Penelitian yang menerbitkan formulir penilaian (disesuaikan
 * format dari institusi/kampus asal peserta, lihat Pengajuan::nama_institusi)
 * dan yang merekap+input nilai akhir setelah Pembimbing Lapangan menilai
 * secara fisik/di luar sistem. dinilai_oleh mencatat PIC yang menginput.
 */
class Evaluasi extends Model
{
    protected $fillable = [
        'pengajuan_id', 'pembimbing_id', 'aspek_penilaian_default', 'dinilai_oleh',
        'jadwal_evaluasi', 'nilai_akhir', 'hasil', 'catatan', 'file_bukti', 'dinilai_at',
        'wajib_untuk_perpanjangan', 'tempat_pelaksanaan', 'nama_rekan_kerja',
        'nama_pendamping_sdm', 'checklist_persiapan',
    ];

    protected $casts = [
        'jadwal_evaluasi' => 'date',
        'nilai_akhir' => 'decimal:2',
        'dinilai_at' => 'datetime',
        'aspek_penilaian_default' => 'array',
        'wajib_untuk_perpanjangan' => 'boolean',
        'checklist_persiapan' => 'array',
    ];

    /** @return BelongsTo<Pengajuan, $this> */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /**
     * Hanya terisi kalau Pembimbing Lapangan itu memang punya akun User
     * (opsional). Untuk nama tampilan, lebih baik pakai
     * $evaluasi->pengajuan->penugasanPembimbing->nama_tampil.
     */
    /** @return BelongsTo<User, $this> */
    public function pembimbing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembimbing_id');
    }

    /**
     * PIC PKL/Penelitian yang menginput nilai akhir ke sistem.
     */
    /** @return BelongsTo<User, $this> */
    public function dinilaiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dinilai_oleh');
    }

    /** @return HasMany<FormulirPenilaian, $this> */
    public function formulirPenilaians(): HasMany
    {
        return $this->hasMany(FormulirPenilaian::class);
    }
}
