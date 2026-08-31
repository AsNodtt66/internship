<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penugasan Pembimbing Lapangan.
 *
 * Sesuai alur TO-BE: Pembimbing Lapangan TIDAK WAJIB punya akun User untuk
 * login ke sistem (pembimbing_id nullable). PIC cukup mencatat namanya
 * (nama_pembimbing, jabatan_pembimbing, no_hp_pembimbing) sebagai usulan.
 * Kalau di kemudian hari pembimbing itu memang punya akun, pembimbing_id
 * bisa diisi dan itu jadi sumber nama yang diprioritaskan (lihat namaTampil()).
 *
 * Alur status:
 *  - 'diusulkan' : baru diajukan PIC (bersamaan dengan draft Surat Balasan),
 *                  menunggu persetujuan Kepala Bagian Tujuan.
 *  - 'disetujui' : sudah disahkan Kepala Bagian (ditetapkan_oleh/at terisi).
 */
class PenugasanPembimbing extends Model
{
    protected $fillable = [
        'pengajuan_id', 'pembimbing_id', 'pembimbing_lapangan_id',
        'nama_pembimbing', 'jabatan_pembimbing', 'no_hp_pembimbing',
        'catatan', 'status',
        'diusulkan_oleh', 'diusulkan_at',
        'ditetapkan_oleh', 'ditetapkan_at',
    ];

    protected $casts = [
        'diusulkan_at' => 'datetime',
        'ditetapkan_at' => 'datetime',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    /**
     * Dipilih dari dropdown data master Pembimbing Lapangan. Ini yang
     * jadi sumber utama sekarang (lihat PengajuanWorkflowService::usulkanPembimbing()).
     */
    public function pembimbingLapangan(): BelongsTo
    {
        return $this->belongsTo(PembimbingLapangan::class);
    }

    /**
     * Hanya terisi kalau pembimbing memang punya akun User (opsional).
     * Untuk kasus normal (tanpa akun), pakai namaTampil() di bawah.
     */
    public function pembimbing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembimbing_id');
    }

    public function diusulkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diusulkan_oleh');
    }

    public function ditetapkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditetapkan_oleh');
    }

    /**
     * Nama Pembimbing Lapangan untuk ditampilkan di UI/surat/notifikasi.
     * Prioritas: data master (pembimbing_lapangan) -> snapshot nama saat
     * diusulkan (nama_pembimbing, untuk data lama sebelum ada master) ->
     * nama akun User kalau memang terhubung.
     */
    public function getNamaTampilAttribute(): ?string
    {
        return $this->pembimbingLapangan?->nama ?? $this->nama_pembimbing ?? $this->pembimbing?->name;
    }
}