<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * Relasi ke model Peserta
     */
    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'peserta_id');
    }

    /**
     * Relasi ke model Bagian / Unit Kerja
     */
    public function bagian(): BelongsTo
    {
        return $this->belongsTo(Bagian::class, 'bagian_tujuan_id');
    }

    /**
     * Alias dari bagian(). Beberapa service/policy (PengajuanWorkflowService,
     * PengajuanPolicy, PengajuanStatsWidget) memanggil nama relasi ini.
     */
    public function bagianTujuan(): BelongsTo
    {
        return $this->bagian();
    }

    /**
     * Dokumen persyaratan yang diupload untuk pengajuan ini
     */
    public function dokumenPersyaratans(): HasMany
    {
        return $this->hasMany(DokumenPersyaratan::class);
    }

    /**
     * Tahapan approval internal (Staff SDM, Kabag SDM, GM)
     * Hanya urutan & status yang boleh dibaca dashboard peserta.
     */
    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class);
    }

    /**
     * Penugasan pembimbing lapangan (hanya terisi setelah ditetapkan)
     */
    public function penugasanPembimbing(): HasOne
    {
        return $this->hasOne(PenugasanPembimbing::class);
    }

    /**
     * Surat balasan resmi (jika sudah diterbitkan)
     */
    public function suratBalasan(): HasOne
    {
        return $this->hasOne(SuratBalasan::class);
    }

    /**
     * Formulir evaluasi PKL/Penelitian (dibuat PIC, dijadwalkan & dinilai
     * Pembimbing Lapangan). Satu pengajuan hanya punya satu evaluasi.
     */
    public function evaluasi(): HasOne
    {
        return $this->hasOne(Evaluasi::class);
    }

    /**
     * Riwayat pengajuan perpanjangan PKL/Penelitian (saat nilai evaluasi
     * di bawah KKM).
     */
    public function perpanjangans(): HasMany
    {
        return $this->hasMany(Perpanjangan::class);
    }

    /**
     * Hasil penilaian PDF (upload PIC) + keputusan perpanjangan peserta.
     * Beda dari evaluasi() (formulir multi-aspek internal, hasil
     * ditentukan PIC) -- ini alur di mana PESERTA sendiri yang memilih
     * perpanjang/tidak setelah melihat file PDF hasil penilaiannya.
     */
    public function penilaian(): HasOne
    {
        return $this->hasOne(Penilaian::class);
    }

    /**
     * Surat Keterangan Selesai PKL/Penelitian atau Surat Perpanjangan PKL/
     * Penelitian (langkah 17-18 flowchart AS-IS, diterbitkan PIC setelah
     * nilai evaluasi/keputusan perpanjangan final).
     */
    public function suratKeterangan(): HasOne
    {
        return $this->hasOne(SuratKeterangan::class);
    }

    /**
     * Riwayat perubahan status (sumber notifikasi dashboard)
     */
    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatus::class)->orderByDesc('created_at');
    }

    /**
     * Pengajuan sebelumnya, kalau baris ini adalah hasil PERPANJANGAN
     * (perpanjangan selalu berupa pengajuan baru, bukan mengubah
     * tanggal_selesai pada pengajuan lama -- lihat pengajuan_asal_id).
     */
    public function pengajuanAsal(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_asal_id');
    }

    /**
     * Pengajuan perpanjangan (periode berikutnya) yang lahir dari baris
     * ini, kalau ada. Riwayat tiap periode PKL/Penelitian tetap terpisah
     * dan tidak pernah digabung/ditimpa.
     */
    public function pengajuanPerpanjangan(): HasOne
    {
        return $this->hasOne(Pengajuan::class, 'pengajuan_asal_id');
    }
}