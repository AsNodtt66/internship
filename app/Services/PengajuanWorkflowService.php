<?php

namespace App\Services;

use App\Enums\RoleSlug;
use App\Models\ApprovalWorkflow;
use App\Models\DokumenPersyaratan;
use App\Models\Evaluasi;
use App\Models\FormulirPenilaian;
use App\Models\Pengajuan;
use App\Models\PembimbingLapangan;
use App\Models\Penilaian;
use App\Models\Perpanjangan;
use App\Models\PenugasanPembimbing;
use App\Models\RiwayatStatus;
use App\Models\Role;
use App\Models\SuratBalasan;
use App\Models\SuratKeterangan;
use App\Models\User;
use App\Services\Workflow\ExtensionReminderService;
use App\Services\Workflow\WorkflowNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Mesin alur kerja (workflow engine) Pengajuan PKL/Penelitian.
 *
 * Mengikuti flowchart TO-BE "Alur Pengajuan PKL/Penelitian" (sistem online):
 *
 *  1. Peserta mengajukan melalui aplikasi (dicatat oleh PIC).
 *  2. PIC PKL/Penelitian memverifikasi kelengkapan peserta/dokumen.
 *  3. PIC melakukan rekap nomor agenda -> Pengajuan diteruskan untuk disposisi
 *     berjenjang 4 tahap: GM -> Kepala Bagian SDM -> Staff SDM -> Kepala
 *     Bagian Tujuan (urutan 1-4 pada approval_workflows). Keempat tahap ini
 *     pakai mekanisme SAMA PERSIS (tandatanganiLangkah()) -- HANYA
 *     "mengetahui dan menandatangani" surat pengajuan, BUKAN titik
 *     approve/reject — tidak ada opsi menolak di tahap manapun (keputusan
 *     bisnis final). Satu pengecualian: tahap TERAKHIR (Kepala Bagian
 *     Tujuan) WAJIB sekalian mengisi catatan bebas calon Pembimbing
 *     Lapangan untuk PIC -- digabung ke aksi tanda tangan yang sama,
 *     BUKAN halaman/langkah terpisah.
 *  4. PIC PKL/Penelitian MENETAPKAN Pembimbing Lapangan dari dropdown data
 *     master berdasarkan catatan itu, sekaligus menerbitkan Surat Balasan
 *     RESMI langsung (usulkanPembimbing()) -> status Pengajuan langsung
 *     menjadi "berjalan" -> peserta dinotifikasi daftar & jadwal
 *     PKL/Penelitiannya. TIDAK ADA lagi persetujuan terpisah dari Kepala
 *     Bagian sesudah ini. Pembimbing Lapangan TIDAK WAJIB punya akun User
 *     untuk login.
 *  5. PIC membuat & menerbitkan Formulir Evaluasi (disesuaikan dari format
 *     institusi/kampus asal peserta), jadwal masih kosong.
 *  6. PIC menentukan jadwal evaluasi/presentasi (menggantikan peran
 *     Pembimbing Lapangan yang tidak login ke sistem), lalu setelah
 *     presentasi PIC merekap & meng-input nilai yang sudah diisi Pembimbing
 *     Lapangan secara fisik/di luar sistem ke formulir penilaian multi-aspek
 *     (tiap aspek dicatat di FormulirPenilaian).
 *  7. Nilai akhir numerik dihitung dan disimpan sebagai informasi. Keputusan
 *     akhir `selesai` / `perlu_perpanjangan` dipilih MANUAL oleh PIC sesuai
 *     hasil evaluasi yang sah. KKM = 70 adalah referensi operasional, bukan
 *     pemicu otomatis perubahan status. Aturan ini dibekukan sebagai
 *     baseline P8 agar source, dokumentasi, dan automated test konsisten.
 *     Jika perusahaan mengubah kebijakan, perubahan wajib dilakukan melalui
 *     business-rule change + regression test, bukan hanya mengubah angka KKM.
 */
class PengajuanWorkflowService
{
    public function __construct(
        private readonly WorkflowNotificationService $notifications,
        private readonly ExtensionReminderService $extensionReminders,
    ) {}

    /**
     * Kriteria Ketuntasan Minimal (KKM) nilai evaluasi akhir.
     */
    public const KKM = 70;

    /**
     * Masa PKL/Penelitian maksimal (bulan). Aturan bisnis: perpanjangan
     * TIDAK BOLEH dilakukan dengan menambah tanggal_selesai pada pengajuan
     * yang sama -- kalau peserta butuh waktu lebih lama, itu wajib diproses
     * sebagai pengajuan baru (lihat ajukanPermohonanPerpanjangan() &
     * buatPengajuanPerpanjanganBaru()).
     */
    public const MASA_PKL_MAKSIMAL_BULAN = 3;

    /**
     * Jumlah hari sebelum tanggal_selesai untuk mulai menampilkan
     * peringatan "masa PKL akan segera berakhir" ke peserta/PIC.
     */
    public const AMBANG_PERINGATAN_HARI = 14;

    /**
     * Daftar item default untuk form "CHECK LIST PERSIAPAN PELAKSANAAN
     * EVALUASI MAGANG/PKL" (lihat simpanChecklistPersiapanEvaluasi()).
     *
     * @var array<int, string>
     */
    public const ITEM_CHECKLIST_PERSIAPAN_EVALUASI = [
        'Formulir evaluasi kegiatan magang',
        'Persiapan dokumen/form evaluasi',
        'Kebutuhan pelaksanaan evaluasi',
    ];

    /**
     * Urutan disposisi/approval berjenjang beserta role penanggung jawab.
     * REVISI: Kepala Bagian Tujuan sekarang jadi tahap ke-4, MEKANISMENYA
     * SAMA PERSIS dengan GM/Kabag SDM/Staff SDM (tanda tangan lewat
     * tandatanganiLangkah()) -- bukan halaman terpisah lagi. Bedanya cuma
     * satu: saat menandatangani tahap TERAKHIR ini, Kepala Bagian WAJIB
     * sekalian mengisi catatan calon Pembimbing Lapangan (lihat parameter
     * $catatanCalonPembimbing di tandatanganiLangkah()) yang nanti dibaca
     * PIC untuk menetapkan resmi (lihat usulkanPembimbing()).
     *
     * @var array<int, string>
     */
    public const URUTAN_APPROVAL = [
        1 => 'gm',
        2 => 'kabag_sdm',
        3 => 'staff_sdm',
        4 => 'kepala_bagian',
    ];

    /**
     * Nama tampil per role disposisi, dipakai untuk pesan notifikasi ke PIC
     * ("Pengajuan telah disetujui [nama role]...").
     *
     * @var array<string, string>
     */
    private const LABEL_ROLE_APPROVAL = [
        'gm' => 'GM',
        'kabag_sdm' => 'Kepala Bagian SDM',
        'staff_sdm' => 'Staff SDM',
        'kepala_bagian' => 'Kepala Bagian Tujuan',
    ];

    /**
     * Langkah 1: Peserta mengajukan Surat Pengajuan PKL/Penelitian.
     */
    public function ajukan(Pengajuan $pengajuan): Pengajuan
    {
        $this->pastikanStatus($pengajuan, ['draft']);
        $this->validasiDurasiMaksimal($pengajuan->tanggal_mulai, $pengajuan->tanggal_selesai);

        $pengajuan->update([
            'status' => 'diajukan',
            'diajukan_at' => now(),
        ]);

        $this->catatRiwayat($pengajuan, 'draft', 'diajukan', 'Surat pengajuan diterima oleh PIC PKL/Penelitian.');
        $this->notifikasiRole($pengajuan, 'pic', 'Pengajuan Baru', "Pengajuan {$pengajuan->jenis_pengajuan} dari {$pengajuan->peserta->user->name} menunggu verifikasi dokumen.");

        return $pengajuan;
    }

    /**
     * Langkah 2: PIC memverifikasi satu dokumen persyaratan.
     */
    /**
     * Peserta mengunggah ulang 1 dokumen yang sebelumnya ditolak/perlu
     * revisi. Status dokumen itu direset ke "menunggu" (siap diverifikasi
     * ulang PIC). Kalau ini dokumen tertolak TERAKHIR pada pengajuan itu,
     * status pengajuan otomatis balik ke "diajukan" supaya muncul lagi di
     * antrean verifikasi PIC.
     */
    public function perbaikiDokumen(DokumenPersyaratan $dokumen, string $filePathBaru, User $peserta): DokumenPersyaratan
    {
        $this->pastikanRole($peserta, RoleSlug::PESERTA);

        if ($dokumen->pengajuan?->peserta?->user_id !== $peserta->id) {
            throw new RuntimeException('Dokumen ini bukan milik peserta yang sedang login.');
        }

        if ($dokumen->status_verifikasi !== 'tidak_lengkap') {
            throw new RuntimeException('Hanya dokumen yang perlu revisi yang dapat diunggah ulang.');
        }

        $dokumen->update([
            'file_path' => $filePathBaru,
            'status_verifikasi' => 'menunggu',
            'catatan_verifikasi' => null,
            'verified_by' => null,
            'verified_at' => null,
            'uploaded_at' => now(),
        ]);

        $pengajuan = $dokumen->pengajuan;

        $masihAdaYangTertolak = $pengajuan->dokumenPersyaratans()->where('status_verifikasi', 'tidak_lengkap')->exists();

        if (! $masihAdaYangTertolak && $pengajuan->status === 'dokumen_ditolak') {
            $statusLama = $pengajuan->status;
            $pengajuan->update(['status' => 'diajukan']);
            $this->catatRiwayat($pengajuan, $statusLama, 'diajukan', "Dokumen '{$dokumen->jenis_dokumen}' telah diperbaiki peserta.");
            $this->notifikasiRole($pengajuan, 'pic', 'Dokumen Direvisi', "Peserta {$peserta->name} telah memperbaiki dokumen yang ditolak, silakan verifikasi ulang.");
        }

        return $dokumen;
    }

    public function verifikasiDokumen(DokumenPersyaratan $dokumen, string $statusVerifikasi, User $verifier, ?string $catatan = null): DokumenPersyaratan
    {
        $this->pastikanRole($verifier, RoleSlug::PIC);

        if (! in_array($statusVerifikasi, ['lengkap', 'tidak_lengkap'], true)) {
            throw new RuntimeException('Status verifikasi dokumen tidak valid.');
        }

        if ($statusVerifikasi === 'tidak_lengkap' && blank($catatan)) {
            throw new RuntimeException('Catatan wajib diisi saat dokumen dinyatakan tidak lengkap.');
        }

        $dokumen->update([
            'status_verifikasi' => $statusVerifikasi,
            'catatan_verifikasi' => $catatan,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);

        $pengajuan = $dokumen->pengajuan;

        if ($statusVerifikasi === 'tidak_lengkap') {
            $pengajuan->update(['status' => 'dokumen_ditolak']);
            $this->catatRiwayat($pengajuan, $pengajuan->getOriginal('status'), 'dokumen_ditolak', "Dokumen '{$dokumen->jenis_dokumen}' tidak lengkap: {$catatan}");
            $this->notifikasiPeserta($pengajuan, 'Dokumen Belum Lengkap', "Dokumen '{$dokumen->jenis_dokumen}' perlu diperbaiki: {$catatan}");

            return $dokumen;
        }

        // Jika seluruh dokumen sudah diverifikasi "lengkap", lanjutkan ke rekap nomor agenda.
        $semuaLengkap = $pengajuan->dokumenPersyaratans()->where('status_verifikasi', '!=', 'lengkap')->doesntExist();

        if ($semuaLengkap) {
            $pengajuan->update(['status' => 'verifikasi_dokumen']);
            $this->catatRiwayat($pengajuan, $pengajuan->getOriginal('status'), 'verifikasi_dokumen', 'Seluruh dokumen persyaratan telah diverifikasi lengkap.');
        }

        return $dokumen;
    }

    /**
     * PIC: ubah Bagian Tujuan pengajuan, mis. peserta salah pilih (jurusan
     * ternyata lebih cocok ke bagian lain, contoh: mengajukan ke SDM dan
     * Umum tapi sebenarnya cocoknya ke Pabrikasi). Hanya boleh SEBELUM
     * disposisi berjenjang dimulai (rekapDanMulaiApproval), karena begitu
     * approval berjalan, Kepala Bagian Tujuan tahap ke-4 sudah ditentukan
     * berdasarkan bagian_tujuan_id ini.
     */
    public function ubahBagianTujuan(Pengajuan $pengajuan, int $bagianTujuanBaruId, User $pic, ?string $alasan = null): Pengajuan
    {
        $this->pastikanRole($pic, RoleSlug::PIC);
        $this->pastikanStatus($pengajuan, ['diajukan', 'verifikasi_dokumen', 'dokumen_ditolak']);

        $bagianLama = $pengajuan->bagianTujuan?->nama_bagian ?? '-';

        $pengajuan->update(['bagian_tujuan_id' => $bagianTujuanBaruId]);

        $bagianBaru = $pengajuan->fresh()->bagianTujuan?->nama_bagian ?? '-';

        $this->catatRiwayat(
            $pengajuan,
            $pengajuan->status,
            $pengajuan->status,
            "Bagian Tujuan diubah oleh PIC dari '{$bagianLama}' ke '{$bagianBaru}'".($alasan ? ": {$alasan}" : '.')
        );

        return $pengajuan;
    }

    /**
     * Langkah 3: PIC melakukan rekap nomor agenda dan memulai disposisi berjenjang
     * (Staff SDM -> Kabag SDM -> GM).
     */
    public function rekapDanMulaiApproval(Pengajuan $pengajuan, string $nomorAgenda): Pengajuan
    {
        $this->pastikanStatus($pengajuan, ['verifikasi_dokumen']);

        DB::transaction(function () use ($pengajuan, $nomorAgenda) {
            $pengajuan->update([
                'nomor_agenda' => $nomorAgenda,
                'status' => 'proses_approval',
            ]);

            foreach (self::URUTAN_APPROVAL as $urutan => $roleSlug) {
                ApprovalWorkflow::create([
                    'pengajuan_id' => $pengajuan->id,
                    'urutan' => $urutan,
                    'status' => 'menunggu',
                ]);
            }
        });

        $this->catatRiwayat($pengajuan, 'verifikasi_dokumen', 'proses_approval', "Nomor agenda {$nomorAgenda} diterbitkan, disposisi dimulai.");
        $this->notifikasiLangkahApprovalBerikutnya($pengajuan->fresh());

        return $pengajuan->fresh();
    }

    /**
     * Langkah 4: Approver (GM / Kepala Bagian SDM / Staff SDM / Kepala
     * Bagian Tujuan) menandatangani satu tahapan disposisi.
     *
     * KEPUTUSAN BISNIS FINAL (mengikuti flowchart TO-BE): keempat tahap ini
     * HANYA "mengetahui dan menandatangani" surat pengajuan — bukan titik
     * approve/reject. TIDAK ADA opsi menolak di tahap manapun dari GM,
     * Kepala Bagian SDM, Staff SDM, atau Kepala Bagian Tujuan (method
     * tolakLangkah() sengaja dihapus dari service ini). Satu-satunya titik
     * penolakan dalam alur ini ada di verifikasiDokumen() (kewenangan PIC
     * saat memeriksa kelengkapan dokumen persyaratan).
     */
    /**
     * Menandatangani satu tahap disposisi (GM / Kabag SDM / Staff SDM /
     * Kepala Bagian Tujuan) -- SEMUA 4 tahap pakai mekanisme yang sama
     * persis. Satu-satunya pengecualian: tahap TERAKHIR (Kepala Bagian
     * Tujuan, urutan 4) WAJIB sekalian mengisi $catatanCalonPembimbing
     * (nama/rekomendasi calon Pembimbing Lapangan) -- ini bukan approval
     * baru, cuma digabung ke aksi tanda tangan yang sama supaya Kepala
     * Bagian tidak perlu buka halaman terpisah. Begitu tahap ke-4 ini
     * ditandatangani, catatan tsb langsung tersimpan & pengajuan lompat
     * ke status 'menunggu_penetapan_pembimbing' -- giliran PIC menetapkan
     * resmi dari data master (lihat usulkanPembimbing()).
     */
    public function tandatanganiLangkah(ApprovalWorkflow $step, User $approver, ?string $catatan = null, ?string $catatanCalonPembimbing = null): ApprovalWorkflow
    {
        return DB::transaction(function () use ($step, $approver, $catatan, $catatanCalonPembimbing): ApprovalWorkflow {
            $lockedStep = ApprovalWorkflow::query()->lockForUpdate()->findOrFail($step->id);

            if ($lockedStep->status !== 'menunggu') {
                throw new RuntimeException('Tahapan disposisi ini sudah diproses sebelumnya.');
            }

            $pengajuan = Pengajuan::query()->lockForUpdate()->findOrFail($lockedStep->pengajuan_id);
            $langkahAktif = $pengajuan->approvalWorkflows()
                ->where('status', 'menunggu')
                ->orderBy('urutan')
                ->lockForUpdate()
                ->first();

            if (! $langkahAktif || $langkahAktif->id !== $lockedStep->id) {
                throw new RuntimeException('Belum giliran tahapan ini untuk diproses (disposisi berjalan berurutan).');
            }

            $roleSlugTahapIni = self::URUTAN_APPROVAL[$lockedStep->urutan] ?? null;

            if ($roleSlugTahapIni === null || $approver->role?->slug !== $roleSlugTahapIni) {
                throw new RuntimeException('Anda tidak berwenang menandatangani tahapan disposisi ini.');
            }

            if ($roleSlugTahapIni === 'kepala_bagian') {
                if ($pengajuan->bagianTujuan?->kepala_bagian_id !== $approver->id) {
                    throw new RuntimeException('Tahapan ini hanya boleh ditandatangani Kepala Bagian Tujuan terkait.');
                }

                if (! filled($catatanCalonPembimbing)) {
                    throw new RuntimeException('Catatan calon Pembimbing Lapangan wajib diisi (tahap terakhir).');
                }
            }

            $lockedStep->update([
                'penandatangan_id' => $approver->id,
                'status' => 'ditandatangani',
                'catatan' => $catatan,
                'diproses_at' => now(),
            ]);

            $tahapBerikutnya = $pengajuan->approvalWorkflows()
                ->where('status', 'menunggu')
                ->orderBy('urutan')
                ->first();

            if ($tahapBerikutnya) {
                $this->notifikasiLangkahApprovalBerikutnya($pengajuan->fresh());

                return $lockedStep->fresh();
            }

            $pengajuan->update([
                'status' => 'menunggu_penetapan_pembimbing',
                'catatan_pembimbing' => $catatanCalonPembimbing,
                'catatan_pembimbing_oleh' => $approver->id,
                'catatan_pembimbing_at' => now(),
            ]);
            $this->catatRiwayat($pengajuan, 'proses_approval', 'menunggu_penetapan_pembimbing', "Seluruh disposisi selesai. Kepala Bagian Tujuan menuliskan catatan calon Pembimbing Lapangan: \"{$catatanCalonPembimbing}\".");

            $this->notifikasiRole($pengajuan, 'pic', 'Catatan Calon Pembimbing dari Kepala Bagian', "Kepala Bagian {$pengajuan->bagianTujuan?->nama_bagian} sudah memberi catatan calon Pembimbing Lapangan untuk {$pengajuan->peserta->user->name}. Silakan tetapkan lewat data master Pembimbing Lapangan.");

            return $lockedStep->fresh();
        }, 3);
    }

    /**
     * Langkah 5 (FINAL): PIC PKL/Penelitian MENETAPKAN Pembimbing Lapangan
     * dari dropdown data master -- berdasarkan catatan calon dari Kepala
     * Bagian Tujuan (lihat tandatanganiLangkah(), parameter
     * $catatanCalonPembimbing) -- sekaligus
     * menerbitkan Surat Balasan RESMI. Ini titik akhir penentuan pembimbing:
     * TIDAK ADA lagi persetujuan terpisah dari Kepala Bagian sesudah ini --
     * begitu PIC menetapkan, pengajuan LANGSUNG "berjalan" & peserta
     * dinotifikasi jadwalnya.
     *
     * Pembimbing Lapangan dipilih dari DROPDOWN data master
     * (PembimbingLapangan, lihat $pembimbingLapanganId) -- TIDAK WAJIB
     * punya akun User untuk login. Kalau nama pembimbing belum ada di
     * data master, PIC bisa daftarkan baru langsung dari dropdown itu
     * (lihat createOptionForm di usulkanPembimbingAction()); punya akun
     * login atau tidak sepenuhnya opsional dan bisa menyusul kapan saja.
     */
    public function usulkanPembimbing(
        Pengajuan $pengajuan,
        string $nomorSurat,
        string $filePathSurat,
        User $pic,
        int $pembimbingLapanganId,
    ): PenugasanPembimbing {
        $this->pastikanRole($pic, RoleSlug::PIC);
        $this->pastikanStatus($pengajuan, ['menunggu_penetapan_pembimbing']);

        $pembimbingLapangan = PembimbingLapangan::findOrFail($pembimbingLapanganId);

        $penugasan = DB::transaction(function () use ($pengajuan, $nomorSurat, $filePathSurat, $pic, $pembimbingLapangan) {
            $penugasan = PenugasanPembimbing::updateOrCreate(
                ['pengajuan_id' => $pengajuan->id],
                [
                    'pembimbing_lapangan_id' => $pembimbingLapangan->id,
                    'pembimbing_id' => $pembimbingLapangan->user_id, // ikut kesalin kalau kebetulan sudah punya akun
                    // snapshot nilainya saat ini, supaya riwayat pengajuan ini
                    // tidak berubah walau data master diedit belakangan
                    'nama_pembimbing' => $pembimbingLapangan->nama,
                    'jabatan_pembimbing' => $pembimbingLapangan->jabatan,
                    'no_hp_pembimbing' => $pembimbingLapangan->no_hp,
                    // Langsung final -- tidak ada lagi approval Kepala Bagian
                    // sesudah ini (lihat docblock method).
                    'status' => 'disetujui',
                    'diusulkan_oleh' => $pic->id,
                    'diusulkan_at' => now(),
                    'ditetapkan_oleh' => $pic->id,
                    'ditetapkan_at' => now(),
                ]
            );

            SuratBalasan::updateOrCreate(
                ['pengajuan_id' => $pengajuan->id],
                [
                    'nomor_surat' => $nomorSurat,
                    'file_path' => $filePathSurat,
                    'status' => 'terbit',
                    'generated_by' => $pic->id,
                    'generated_at' => now(),
                    'diterbitkan_oleh' => $pic->id,
                    'diterbitkan_at' => now(),
                ]
            );

            $pengajuan->update([
                'status' => 'berjalan',
                'nomor_surat_balasan' => $nomorSurat,
            ]);

            return $penugasan;
        });

        $this->catatRiwayat($pengajuan, 'menunggu_penetapan_pembimbing', 'berjalan', "PIC menetapkan Pembimbing Lapangan ({$penugasan->nama_tampil}) & menerbitkan Surat Balasan resmi.");

        $this->notifikasiPeserta($pengajuan, 'Daftar & Jadwal PKL/Penelitian', "Selamat! Anda ditempatkan di {$pengajuan->bagianTujuan?->nama_bagian} dengan Pembimbing Lapangan {$penugasan->nama_tampil}, periode {$pengajuan->tanggal_mulai?->format('d-m-Y')} s/d {$pengajuan->tanggal_selesai?->format('d-m-Y')}.");

        $kepalaBagian = $pengajuan->bagianTujuan?->kepalaBagian;
        if ($kepalaBagian) {
            $this->notifikasiUser($kepalaBagian, $pengajuan, 'Pembimbing Ditetapkan', "PIC telah menetapkan {$penugasan->nama_tampil} sebagai Pembimbing Lapangan untuk {$pengajuan->peserta->user->name}. PKL/Penelitian resmi berjalan.");
        }

        return $penugasan;
    }

    /**
     * Langkah 7a: PIC membuat & menerbitkan formulir evaluasi (Evaluasi
     * dibuat, jadwal belum ditentukan). Karena Pembimbing Lapangan tidak
     * wajib punya akun untuk login, PIC yang menyiapkan formulirnya --
     * idealnya disesuaikan dari format/kriteria penilaian institusi/kampus
     * asal peserta (lihat Pengajuan::nama_institusi / fakultas / program_studi)
     * -- lalu Pembimbing Lapangan tinggal menilai (fisik/di luar sistem).
     *
     * $aspekPenilaian: daftar nama aspek yang PIC tentukan manual sebelum
     * form dicetak (disesuaikan format kampus peserta). Kalau tidak diisi,
     * dikosongkan saja -- pemanggil (controller/action) yang menyediakan
     * default list-nya.
     *
     * @param  array<int, string>|null  $aspekPenilaian
     */
    public function buatFormulirEvaluasi(Pengajuan $pengajuan, User $pic, ?array $aspekPenilaian = null, bool $wajibUntukPerpanjangan = false): Evaluasi
    {
        $this->pastikanRole($pic, RoleSlug::PIC);
        $penugasan = $pengajuan->penugasanPembimbing;

        if (! $penugasan || $penugasan->status !== 'disetujui') {
            throw new RuntimeException('Pembimbing Lapangan belum disetujui Kepala Bagian, formulir evaluasi belum bisa dibuat.');
        }

        return Evaluasi::firstOrCreate(
            ['pengajuan_id' => $pengajuan->id],
            [
                'pembimbing_id' => $penugasan->pembimbing_id,
                'aspek_penilaian_default' => $aspekPenilaian,
                'wajib_untuk_perpanjangan' => $wajibUntukPerpanjangan,
            ],
        );
    }

    /**
     * Peserta: mengusulkan daftar aspek penilaian (aspek apa saja yang
     * ingin dinilai) SAAT PKL/Penelitian sedang berjalan. Kalau formulir
     * Evaluasi belum ada, otomatis dibuatkan (menggantikan PIC yang
     * dulunya harus mengisi aspek manual). Kalau sudah ada tapi belum
     * dinilai, daftar aspeknya cukup diperbarui saja. Bisa diedit
     * berkali-kali selama belum dinilai (dinilai_at masih kosong).
     *
     * Daftar ini juga yang otomatis ditampilkan ke Pembimbing Lapangan
     * (kalau dia punya akun) untuk dinilai langsung di sistem, dan tetap
     * bisa dilihat PIC (read-only) supaya semuanya tetap terkomputerisasi
     * meskipun Pembimbing Lapangan-nya tidak punya akun (kasus itu, PIC
     * yang input nilainya dari lembar fisik yang diberikan peserta).
     *
     * @param  array<int, string>  $aspek
     */
    public function usulkanAspekPenilaian(Pengajuan $pengajuan, array $aspek): Evaluasi
    {
        $this->pastikanStatus($pengajuan, ['berjalan']);

        $aspek = array_values(array_filter(array_map('trim', $aspek), fn ($item) => $item !== ''));

        if (empty($aspek)) {
            throw new RuntimeException('Minimal harus ada 1 aspek penilaian.');
        }

        $evaluasi = $pengajuan->evaluasi;

        if (! $evaluasi) {
            $penugasan = $pengajuan->penugasanPembimbing;

            if (! $penugasan || $penugasan->status !== 'disetujui') {
                throw new RuntimeException('Pembimbing Lapangan belum ditetapkan, aspek penilaian belum dapat diisi.');
            }

            $evaluasi = Evaluasi::create([
                'pengajuan_id' => $pengajuan->id,
                'pembimbing_id' => $penugasan->pembimbing_id,
                'aspek_penilaian_default' => $aspek,
                'wajib_untuk_perpanjangan' => false,
            ]);

            $this->notifikasiRole($pengajuan, 'pic', 'Aspek Penilaian Diisi', "Peserta mengisi daftar aspek penilaian untuk pengajuan {$pengajuan->nomor_agenda}.");

            return $evaluasi;
        }

        if ($evaluasi->dinilai_at !== null) {
            throw new RuntimeException('Aspek penilaian tidak bisa diubah lagi karena sudah dinilai.');
        }

        $evaluasi->update(['aspek_penilaian_default' => $aspek]);

        $this->notifikasiRole($pengajuan, 'pic', 'Aspek Penilaian Diperbarui', "Peserta memperbarui daftar aspek penilaian untuk pengajuan {$pengajuan->nomor_agenda}.");

        return $evaluasi->fresh();
    }

    /**
     * Langkah 7b: PIC menentukan jadwal evaluasi/presentasi akhir peserta
     * (menggantikan Pembimbing Lapangan yang tidak login ke sistem).
     */
    public function jadwalkanEvaluasi(Evaluasi $evaluasi, string $jadwalEvaluasi, User $pic): Evaluasi
    {
        $this->pastikanRole($pic, RoleSlug::PIC);
        $evaluasi->update(['jadwal_evaluasi' => $jadwalEvaluasi]);

        $this->notifikasiPeserta($evaluasi->pengajuan, 'Jadwal Evaluasi Ditentukan', "Presentasi evaluasi Anda dijadwalkan pada {$jadwalEvaluasi}.");

        return $evaluasi;
    }

    /**
     * Langkah 8: PIC merekap & meng-input nilai dari formulir penilaian
     * multi-aspek yang sudah diisi Pembimbing Lapangan secara fisik/di luar
     * sistem (tiap aspek dicatat sebagai FormulirPenilaian). Nilai akhir
     * dihitung otomatis dari rata-rata skor tiap aspek, lalu direkap untuk
     * memutuskan status lulus / perlu perpanjangan. $penilai di sini adalah
     * PIC yang menginput (dicatat di Evaluasi::dinilai_oleh) -- bukan
     * Pembimbing Lapangan, karena dia tidak login ke sistem.
     *
     * @param  array<int, array{aspek: string, skor: float}>  $aspekPenilaian
     */
    /**
     * $hasil ditentukan MANUAL oleh PIC (bukan dihitung otomatis dari
     * rata-rata skor vs KKM) — sesuai ketentuan masing-masing kasus.
     * $nilaiAkhir (rata-rata skor) tetap dihitung & disimpan sebagai
     * informasi/riwayat saja, tidak dipakai untuk memutuskan hasil.
     */
    public function inputPenilaian(Evaluasi $evaluasi, array $aspekPenilaian, User $penilai, string $hasil, ?string $catatan = null, ?string $fileBukti = null): Evaluasi
    {
        $this->pastikanPenilaiBerwenang($evaluasi->pengajuan, $penilai);
        if (empty($aspekPenilaian)) {
            throw new RuntimeException('Minimal harus ada 1 aspek penilaian.');
        }

        if (! in_array($hasil, ['selesai', 'perlu_perpanjangan'], true)) {
            throw new RuntimeException('Hasil harus dipilih: selesai atau perlu_perpanjangan.');
        }

        $nilaiAkhir = collect($aspekPenilaian)->pluck('skor')->filter(fn ($skor) => $skor !== null && $skor !== '')->avg();
        $nilaiAkhir = $nilaiAkhir !== null ? round($nilaiAkhir, 2) : null;

        DB::transaction(function () use ($evaluasi, $aspekPenilaian, $nilaiAkhir, $hasil, $catatan, $fileBukti, $penilai) {
            $evaluasi->formulirPenilaians()->delete();

            foreach ($aspekPenilaian as $item) {
                FormulirPenilaian::create([
                    'evaluasi_id' => $evaluasi->id,
                    'aspek_penilaian' => $item['aspek'],
                    'skor' => $item['skor'],
                ]);
            }

            $evaluasi->update([
                'nilai_akhir' => $nilaiAkhir,
                'hasil' => $hasil,
                'catatan' => $catatan,
                'file_bukti' => $fileBukti ?? $evaluasi->file_bukti,
                'dinilai_at' => now(),
                'dinilai_oleh' => $penilai->id,
            ]);
        });

        $pengajuan = $evaluasi->pengajuan;
        $statusBaru = $hasil === 'selesai' ? 'selesai' : 'perlu_perpanjangan';
        $statusLama = $pengajuan->status;
        $pengajuan->update(['status' => $statusBaru]);

        $this->catatRiwayat($pengajuan, $statusLama, $statusBaru, "Nilai rata-rata {$nilaiAkhir} (informasi), hasil ditentukan PIC: {$hasil}. Dinilai oleh {$penilai->name}.");

        if ($hasil === 'selesai') {
            $this->notifikasiPeserta($pengajuan, 'PKL/Penelitian Selesai', "Selamat, PKL/Penelitian Anda dinyatakan selesai.");
            $this->notifikasiRole($pengajuan, 'pic', 'Hasil Evaluasi: Selesai', "Pengajuan {$pengajuan->nomor_agenda} telah dinilai Pembimbing dengan hasil Selesai. Silakan terbitkan Surat Keterangan Selesai PKL.");
        } else {
            $this->notifikasiPeserta($pengajuan, 'Rekomendasi Perpanjangan', "PIC merekomendasikan perpanjangan berdasarkan hasil penilaian Anda. PIC akan menghubungi terkait kemungkinan perpanjangan.");
            $this->notifikasiRole($pengajuan, 'pic', 'Hasil Evaluasi: Perlu Perpanjangan', "Pengajuan {$pengajuan->nomor_agenda} telah dinilai Pembimbing dengan hasil Perlu Perpanjangan. Silakan ajukan perpanjangan.");
        }

        return $evaluasi;
    }

    /**
     * PIC mencatat hasil akhir manual ketika Pembimbing Lapangan tidak
     * mempunyai akun. Ini adalah varian ringkas dari inputPenilaian():
     * tidak ada skor per-aspek, tetapi status akhir dan audit trail tetap
     * diperlakukan sama.
     */
    public function inputHasilAkhirManual(
        Evaluasi $evaluasi,
        User $penilai,
        string $hasil,
        ?float $nilaiAkhir = null,
        ?string $catatan = null,
        ?string $fileBukti = null,
    ): Evaluasi {
        if (! in_array($hasil, ['selesai', 'perlu_perpanjangan'], true)) {
            throw new RuntimeException('Hasil harus dipilih: selesai atau perlu_perpanjangan.');
        }

        if ($nilaiAkhir !== null && ($nilaiAkhir < 0 || $nilaiAkhir > 100)) {
            throw new RuntimeException('Nilai akhir harus berada pada rentang 0 sampai 100.');
        }

        $evaluasi = DB::transaction(function () use ($evaluasi, $penilai, $hasil, $nilaiAkhir, $catatan, $fileBukti): Evaluasi {
            $locked = Evaluasi::query()->lockForUpdate()->findOrFail($evaluasi->id);

            if ($locked->dinilai_at !== null) {
                throw new RuntimeException('Evaluasi ini sudah memiliki hasil akhir.');
            }

            $locked->update([
                'nilai_akhir' => $nilaiAkhir,
                'hasil' => $hasil,
                'catatan' => $catatan,
                'file_bukti' => $fileBukti ?? $locked->file_bukti,
                'dinilai_at' => now(),
                'dinilai_oleh' => $penilai->id,
            ]);

            return $locked->fresh();
        }, 3);

        $pengajuan = $evaluasi->pengajuan;
        $statusLama = $pengajuan->status;
        $statusBaru = $hasil === 'selesai' ? 'selesai' : 'perlu_perpanjangan';
        $pengajuan->update(['status' => $statusBaru]);

        $nilaiLabel = $nilaiAkhir === null ? 'tanpa nilai numerik' : "nilai {$nilaiAkhir}";
        $this->catatRiwayat($pengajuan, $statusLama, $statusBaru, "Hasil akhir manual ({$nilaiLabel}) diinput oleh {$penilai->name}: {$hasil}.");

        if ($hasil === 'selesai') {
            $this->notifikasiPeserta($pengajuan, 'PKL/Penelitian Selesai', 'PKL/Penelitian Anda dinyatakan selesai berdasarkan hasil evaluasi akhir.');
            $this->notifikasiRole($pengajuan, 'pic', 'Hasil Evaluasi: Selesai', "Pengajuan {$pengajuan->nomor_agenda} telah memiliki hasil akhir. Silakan terbitkan Surat Keterangan Selesai.");
        } else {
            $this->notifikasiPeserta($pengajuan, 'Rekomendasi Perpanjangan', 'Hasil evaluasi akhir merekomendasikan perpanjangan periode PKL/Penelitian.');
            $this->notifikasiRole($pengajuan, 'pic', 'Hasil Evaluasi: Perlu Perpanjangan', "Pengajuan {$pengajuan->nomor_agenda} memerlukan tindak lanjut perpanjangan.");
        }

        return $evaluasi;
    }

    /**
     * PIC menandai pertemuan evaluasi perpanjangan telah dilaksanakan.
     * Tidak mengubah keputusan peserta; method ini hanya memenuhi gerbang
     * bahwa evaluasi sudah benar-benar dilakukan sebelum permohonan dapat
     * disetujui.
     */
    public function selesaikanEvaluasiPerpanjangan(Evaluasi $evaluasi, User $pic, ?string $catatan = null): Evaluasi
    {
        return DB::transaction(function () use ($evaluasi, $pic, $catatan): Evaluasi {
            $locked = Evaluasi::query()->lockForUpdate()->findOrFail($evaluasi->id);

            if ($locked->jadwal_evaluasi === null) {
                throw new RuntimeException('Jadwal evaluasi belum ditentukan.');
            }

            if ($locked->dinilai_at !== null) {
                throw new RuntimeException('Evaluasi ini sudah ditandai selesai.');
            }

            $locked->update([
                'dinilai_at' => now(),
                'dinilai_oleh' => $pic->id,
                'catatan' => $catatan ?? $locked->catatan,
            ]);

            return $locked->fresh();
        }, 3);
    }

    /**
     * PIC menyimpan scan Surat Keterangan Selesai yang sudah ditandatangani.
     * Satu pengajuan hanya memiliki satu surat aktif; upload ulang mengganti
     * metadata file pada record yang sama.
     */
    public function uploadSuratKeteranganSelesai(
        Pengajuan $pengajuan,
        User $pic,
        string $filePdf,
        ?string $nomorSurat = null,
    ): SuratKeterangan {
        $this->pastikanStatus($pengajuan, ['selesai']);

        $nomorSurat ??= 'SKL/'.$pengajuan->id.'/'.now()->format('m/Y');

        return SuratKeterangan::updateOrCreate(
            ['pengajuan_id' => $pengajuan->id],
            [
                'jenis' => 'selesai',
                'nomor_surat' => $nomorSurat,
                'file_path' => $filePdf,
                'generated_by' => $pic->id,
                'generated_at' => now(),
            ]
        );
    }

    /**
     * Peserta menyelesaikan PKL/Penelitian TANPA perpanjangan (kondisi
     * normal). Evaluasi bersifat OPSIONAL di jalur ini -- PIC boleh
     * langsung menutup pengajuan tanpa evaluasi, atau melakukan evaluasi
     * dulu (lihat buatFormulirEvaluasi()) sebelum menutupnya. Method ini
     * TIDAK membuat pengajuan baru dan TIDAK mensyaratkan evaluasi.
     */
    public function selesaikanTanpaPerpanjangan(Pengajuan $pengajuan, User $pic, ?string $catatan = null): Pengajuan
    {
        $statusLama = $pengajuan->status;
        $pengajuan->update(['status' => 'selesai']);

        $this->catatRiwayat($pengajuan, $statusLama, 'selesai', $catatan ?? 'Peserta menyelesaikan PKL/Penelitian tanpa perpanjangan.');
        $this->notifikasiPeserta($pengajuan, 'PKL/Penelitian Selesai', 'PKL/Penelitian Anda telah dinyatakan selesai.');

        return $pengajuan->fresh();
    }

    /**
     * PIC: upload/upload-ulang PDF formulir penilaian yang sudah diisi &
     * ditandatangani Pembimbing Lapangan secara fisik/di luar sistem
     * (template dari institusi/kampus atau perusahaan sendiri). Beda dari
     * inputPenilaian() (skor multi-aspek, hasil ditentukan PIC) -- di
     * jalur ini PESERTA sendiri yang memilih keputusan perpanjang/tidak
     * setelah melihat file PDF ini (lihat pilihKeputusanPerpanjangan()).
     *
     * Upload ulang MENIMPA file lama & mereset keputusan yang sudah
     * pernah dipilih peserta sebelumnya (supaya tidak ada keputusan lama
     * yang mengacu ke file yang sudah tidak berlaku).
     */
    public function uploadPenilaian(Pengajuan $pengajuan, User $pic, string $filePdf): Penilaian
    {
        $this->pastikanPenilaiBerwenang($pengajuan, $pic);
        $this->pastikanStatus($pengajuan, ['berjalan']);

        $penilaian = Penilaian::updateOrCreate(
            ['pengajuan_id' => $pengajuan->id],
            [
                'file_pdf' => $filePdf,
                'diupload_oleh' => $pic->id,
                'diupload_at' => now(),
                'keputusan' => null,
            ]
        );

        $this->notifikasiPeserta($pengajuan, 'Hasil Penilaian Terbit', 'PDF hasil penilaian PKL/Penelitian Anda sudah tersedia, silakan pilih keputusan perpanjangan.');

        return $penilaian;
    }

    /**
     * Peserta: memilih keputusan perpanjang/tidak setelah melihat hasil
     * penilaian PDF yang diupload PIC (uploadPenilaian()). Keputusan
     * hanya boleh dipilih SEKALI -- kalau sudah terisi, method ini
     * menolak supaya tidak berubah-ubah setelah pengajuan berikutnya
     * terlanjur diproses.
     *
     * - 'tidak_perpanjang' -> Pengajuan langsung 'selesai'.
     * - 'perpanjang' -> Pengajuan jadi 'perlu_perpanjangan', peserta lalu
     *   mengajukan permohonan resmi lewat ajukanPermohonanPerpanjangan().
     */
    public function pilihKeputusanPerpanjangan(Penilaian $penilaian, string $keputusan): Penilaian
    {
        if (! in_array($keputusan, ['perpanjang', 'tidak_perpanjang'], true)) {
            throw new RuntimeException('Keputusan harus dipilih: perpanjang atau tidak_perpanjang.');
        }

        return DB::transaction(function () use ($penilaian, $keputusan): Penilaian {
            $lockedPenilaian = Penilaian::query()->lockForUpdate()->findOrFail($penilaian->id);

            if ($lockedPenilaian->keputusan !== null) {
                throw new RuntimeException('Keputusan sudah pernah dipilih sebelumnya.');
            }

            $pengajuan = Pengajuan::query()->lockForUpdate()->findOrFail($lockedPenilaian->pengajuan_id);
            $this->pastikanStatus($pengajuan, ['berjalan']);

            $lockedPenilaian->update(['keputusan' => $keputusan]);

            $statusLama = $pengajuan->status;
            $statusBaru = $keputusan === 'perpanjang' ? 'perlu_perpanjangan' : 'selesai';
            $pengajuan->update(['status' => $statusBaru]);

            $this->catatRiwayat($pengajuan, $statusLama, $statusBaru, "Peserta memilih keputusan: {$keputusan} (berdasarkan PDF hasil penilaian).");

            if ($keputusan === 'tidak_perpanjang') {
                $this->notifikasiRole($pengajuan, 'pic', 'Peserta Memilih Tidak Perpanjang', "Pengajuan {$pengajuan->nomor_agenda} dinyatakan selesai atas pilihan peserta.");
            } else {
                $this->notifikasiRole($pengajuan, 'pic', 'Peserta Memilih Perpanjang', "Pengajuan {$pengajuan->nomor_agenda} perlu diajukan perpanjangan atas pilihan peserta.");
            }

            return $lockedPenilaian->fresh();
        }, 3);
    }

    /**
     * Backward-compatible facade used by the existing console command.
     * Reminder orchestration lives in a focused service.
     */
    public function kirimPengingatKeputusanPerpanjangan(?int $hHari = null): int
    {
        return $this->extensionReminders->send($hHari ?? self::AMBANG_PERINGATAN_HARI);
    }

    /**
     * PIC: hentikan/tolak PKL/Penelitian yang SEDANG BERJALAN karena
     * pelanggaran atau masalah serius (mis. tidak disiplin, melanggar
     * aturan perusahaan) -- beda dari selesaikanTanpaPerpanjangan() yang
     * untuk penyelesaian normal/wajar. Status jadi 'ditolak' (nilai enum
     * ini sudah ada di kolom sejak awal tapi belum pernah benar-benar
     * dipakai di service manapun -- makanya sebelumnya tidak ada tombol
     * Tolak untuk pengajuan yang sudah berjalan). Alasan WAJIB diisi
     * supaya ada jejak/riwayat kenapa dihentikan.
     */
    public function tolakPengajuanBerjalan(Pengajuan $pengajuan, User $pic, string $alasan): Pengajuan
    {
        $this->pastikanRole($pic, RoleSlug::PIC);
        $this->pastikanStatus($pengajuan, ['berjalan']);

        $pengajuan->update(['status' => 'ditolak']);

        $this->catatRiwayat($pengajuan, 'berjalan', 'ditolak', "PKL/Penelitian dihentikan oleh PIC: {$alasan}");
        $this->notifikasiPeserta($pengajuan, 'PKL/Penelitian Dihentikan', "PKL/Penelitian Anda dihentikan. Alasan: {$alasan}");

        return $pengajuan->fresh();
    }

    /**
     * PIC: batalkan pengajuan SELAMA MASIH DALAM PROSES APPROVAL (dari
     * disposisi GM/Kabag SDM/Staff SDM sampai menunggu persetujuan
     * Kepala Bagian atas pembimbing) -- beda dari tolakPengajuanBerjalan()
     * yang untuk PKL yang SUDAH berjalan. Begitu pengajuan mencapai status
     * 'berjalan', tombol ini otomatis hilang dari UI (lihat visible() di
     * batalkanPengajuanDalamProsesAction()).
     *
     * Semua tahap approval yang masih 'menunggu' dihapus (bukan sekadar
     * disembunyikan) supaya GM/Kabag SDM/Staff SDM yang belum sempat
     * tandatangan tidak bisa lagi memprosesnya setelah dibatalkan. Tahap
     * yang SUDAH ditandatangani tetap tersimpan sebagai riwayat.
     */
    public function batalkanPengajuanDalamProses(Pengajuan $pengajuan, User $pic, string $alasan): Pengajuan
    {
        $this->pastikanRole($pic, RoleSlug::PIC);
        $this->pastikanStatus($pengajuan, ['proses_approval', 'disetujui', 'menunggu_catatan_pembimbing', 'menunggu_penetapan_pembimbing']);

        $statusLama = $pengajuan->status;
        $pengajuan->update(['status' => 'ditolak']);
        $pengajuan->approvalWorkflows()->where('status', 'menunggu')->delete();

        $this->catatRiwayat($pengajuan, $statusLama, 'ditolak', "Pengajuan dibatalkan PIC saat proses approval: {$alasan}");
        $this->notifikasiPeserta($pengajuan, 'Pengajuan Dibatalkan', "Pengajuan PKL/Penelitian Anda dibatalkan. Alasan: {$alasan}");

        return $pengajuan->fresh();
    }

    /**
     * Peserta mengajukan PERMOHONAN perpanjangan masa PKL/Penelitian.
     *
     * ATURAN BISNIS (direvisi): peserta boleh mengajukan permohonan ini
     * KAPAN SAJA selama pengajuan masih 'berjalan' (atau sudah ditandai
     * 'perlu_perpanjangan' lewat inputPenilaian()) -- TIDAK ADA lagi
     * syarat evaluasi harus selesai dulu. Evaluasi baru diwajibkan
     * belakangan, sebagai syarat PIC MENYETUJUI permohonan ini (lihat
     * putuskanPerpanjangan()), bukan syarat mengajukan. Tidak boleh ada
     * 2 permohonan yang sama-sama masih 'menunggu' untuk satu pengajuan.
     *
     * ATURAN BISNIS (direvisi lagi): di tahap PERMOHONAN ini peserta CUKUP
     * mengisi tanggal mulai & tanggal selesai periode baru (divalidasi
     * maksimal self::MASA_PKL_MAKSIMAL_BULAN bulan lewat
     * validasiDurasiMaksimal()). Alasan perpanjangan & surat pengantar dari
     * kampus TIDAK lagi disyaratkan di sini -- keduanya baru diminta
     * sebagai "dokumen pelengkap" belakangan, saat peserta melengkapi
     * Pengajuan periode baru SETELAH PIC menyetujui permohonan ini (lihat
     * kolom alasan_perpanjangan & file_surat_kampus_perpanjangan pada
     * Pengajuan hasil buatPengajuanPerpanjanganBaru()).
     */
    public function ajukanPermohonanPerpanjangan(
        Pengajuan $pengajuan,
        string $tanggalMulaiBaru,
        string $tanggalSelesaiBaru,
    ): Perpanjangan {
        $this->validasiDurasiMaksimal($tanggalMulaiBaru, $tanggalSelesaiBaru);

        return DB::transaction(function () use ($pengajuan, $tanggalMulaiBaru, $tanggalSelesaiBaru): Perpanjangan {
            $lockedPengajuan = Pengajuan::query()->lockForUpdate()->findOrFail($pengajuan->id);
            $this->pastikanStatus($lockedPengajuan, ['berjalan', 'perlu_perpanjangan']);

            if ($lockedPengajuan->perpanjangans()->where('status', 'menunggu')->exists()) {
                throw new RuntimeException('Sudah ada permohonan perpanjangan yang masih menunggu keputusan PIC.');
            }

            $perpanjangan = Perpanjangan::create([
                'pengajuan_id' => $lockedPengajuan->id,
                'tanggal_mulai_baru' => $tanggalMulaiBaru,
                'tanggal_selesai_baru' => $tanggalSelesaiBaru,
                'status' => 'menunggu',
            ]);

            $this->notifikasiRole($lockedPengajuan, 'pic', 'Permohonan Perpanjangan PKL', "Permohonan perpanjangan untuk {$lockedPengajuan->peserta->user->name} menunggu keputusan Anda (cek ketersediaan slot/kuota Bagian).");

            return $perpanjangan;
        }, 3);
    }

    /**
     * Keputusan PIC atas permohonan perpanjangan (berdasarkan ketersediaan
     * slot/kuota di Bagian Tujuan). BUKAN lagi kewenangan Kepala Bagian
     * Tujuan -- Kepala Bagian tetap terlibat di approval PENGAJUAN BARU
     * hasil perpanjangan (lihat buatPengajuanPerpanjanganBaru()), tapi
     * keputusan disetujui/ditolaknya PERMOHONAN perpanjangan ini sendiri
     * ada di tangan PIC.
     *
     * ATURAN BISNIS WAJIB: perpanjangan BUKAN menambah tanggal_selesai
     * pada pengajuan lama. Kalau disetujui, sistem membuat PENGAJUAN BARU
     * (status 'draft', ditautkan lewat pengajuan_asal_id) yang wajib
     * melalui approval dari awal (PIC -> GM -> Kabag SDM -> Staff SDM ->
     * Kepala Bagian) -- lihat buatPengajuanPerpanjanganBaru(). Pengajuan
     * lama ditutup jadi 'selesai' dan datanya tetap tersimpan sebagai
     * riwayat, tidak pernah dihapus/ditimpa.
     *
     * ATURAN BISNIS (direvisi): evaluasi (Evaluasi::dinilai_at terisi,
     * lihat inputPenilaian()) HANYA diwajibkan pada cabang 'disetujui' --
     * PIC tidak bisa menyetujui permohonan sebelum peserta dinilai.
     * Cabang 'ditolak' TIDAK mensyaratkan evaluasi sama sekali, karena
     * pengajuan langsung ditutup tanpa periode baru.
     */
    public function putuskanPerpanjangan(Perpanjangan $perpanjangan, string $keputusan, ?User $pic = null): Perpanjangan
    {
        if (! $pic || ! $pic->hasRole(RoleSlug::PIC)) {
            throw new RuntimeException('Hanya PIC yang dapat memutuskan permohonan perpanjangan.');
        }

        if (! in_array($keputusan, ['disetujui', 'ditolak'], true)) {
            throw new RuntimeException('Keputusan perpanjangan harus disetujui atau ditolak.');
        }

        return DB::transaction(function () use ($perpanjangan, $keputusan): Perpanjangan {
            $lockedPerpanjangan = Perpanjangan::query()->lockForUpdate()->findOrFail($perpanjangan->id);

            if ($lockedPerpanjangan->status !== 'menunggu') {
                throw new RuntimeException('Permohonan perpanjangan ini sudah diputuskan.');
            }

            $pengajuan = Pengajuan::query()->lockForUpdate()->findOrFail($lockedPerpanjangan->pengajuan_id);
            $statusLama = $pengajuan->status;

            if ($keputusan === 'disetujui') {
                $evaluasi = Evaluasi::where('pengajuan_id', $pengajuan->id)->latest()->first();

                if (! $evaluasi || ! $evaluasi->dinilai_at) {
                    throw new RuntimeException('Evaluasi wajib dilakukan terlebih dahulu sebelum menyetujui permohonan perpanjangan.');
                }

                $pengajuanBaru = $this->buatPengajuanPerpanjanganBaru(
                    $pengajuan,
                    $lockedPerpanjangan->tanggal_mulai_baru->toDateString(),
                    $lockedPerpanjangan->tanggal_selesai_baru->toDateString(),
                );

                $lockedPerpanjangan->update([
                    'status' => 'disetujui',
                    'pengajuan_baru_id' => $pengajuanBaru->id,
                ]);
                $pengajuan->update(['status' => 'selesai']);

                $this->catatRiwayat($pengajuan, $statusLama, 'selesai', 'Perpanjangan disetujui PIC (slot/kuota tersedia); periode ini ditutup dan digantikan pengajuan baru.');
                $this->notifikasiPeserta($pengajuan, 'Perpanjangan Disetujui', 'Permohonan perpanjangan Anda disetujui. Silakan lengkapi pengajuan periode baru, termasuk alasan perpanjangan & surat pengantar dari kampus, untuk diproses dari awal.');
            } else {
                $lockedPerpanjangan->update(['status' => 'ditolak']);
                $pengajuan->update(['status' => 'selesai']);

                $this->catatRiwayat($pengajuan, $statusLama, 'selesai', 'Perpanjangan tidak disetujui PIC (slot/kuota tidak tersedia), PKL/Penelitian dinyatakan selesai.');
                $this->notifikasiPeserta($pengajuan, 'Perpanjangan Tidak Disetujui', 'Permohonan perpanjangan Anda tidak disetujui. PKL/Penelitian dinyatakan selesai.');
            }

            return $lockedPerpanjangan->fresh();
        }, 3);
    }

    /**
     * Membuat PENGAJUAN BARU untuk periode perpanjangan (bukan mengedit
     * tanggal_selesai pengajuan lama). Menyalin data biodata/akademik dari
     * pengajuan lama supaya peserta tidak perlu mengetik ulang semuanya,
     * tapi status dikembalikan ke 'draft' -- peserta tetap wajib melengkapi
     * dokumen pelengkap perpanjangan (alasan_perpanjangan & surat pengantar
     * dari kampus, lihat PengajuanResource step "Upload Dokumen
     * Persyaratan") & mengajukan ulang lewat ajukan(), lalu melalui
     * seluruh approval dari awal seperti pengajuan biasa.
     */
    public function buatPengajuanPerpanjanganBaru(Pengajuan $pengajuanLama, string $tanggalMulaiBaru, string $tanggalSelesaiBaru): Pengajuan
    {
        $this->validasiDurasiMaksimal($tanggalMulaiBaru, $tanggalSelesaiBaru);

        $dataDisalin = $pengajuanLama->only([
            'peserta_id', 'bagian_tujuan_id', 'jenis_pengajuan', 'judul_penelitian', 'motivasi',
            'keahlian_skill', 'sumber_informasi', 'nama_lengkap', 'jenis_kelamin', 'tempat_lahir',
            'tanggal_lahir', 'nik', 'no_hp', 'email_aktif', 'nama_institusi', 'fakultas',
            'program_studi', 'jenjang_pendidikan', 'semester', 'nim_nisn', 'ipk_nilai', 'tujuan',
            'nama_pembimbing_akademik', 'no_hp_pembimbing_akademik', 'email_pembimbing_akademik',
            'rekomendasi_dari', 'setuju_data_benar', 'setuju_patuh_aturan',
        ]);

        return Pengajuan::create(array_merge($dataDisalin, [
            'pengajuan_asal_id' => $pengajuanLama->id,
            'tanggal_mulai' => $tanggalMulaiBaru,
            'tanggal_selesai' => $tanggalSelesaiBaru,
            'status' => 'draft',
        ]));
    }

    /**
     * Menyimpan/memperbarui form "CHECK LIST PERSIAPAN PELAKSANAAN
     * EVALUASI MAGANG/PKL". $checklist berisi pasangan label => bool
     * (Checked/Belum Checked); label yang tidak diisi otomatis dianggap
     * memakai daftar default (self::ITEM_CHECKLIST_PERSIAPAN_EVALUASI).
     *
     * @param  array<string, bool>  $checklist
     */
    public function simpanChecklistPersiapanEvaluasi(
        Evaluasi $evaluasi,
        array $checklist,
        ?string $tempatPelaksanaan = null,
        ?string $namaRekanKerja = null,
        ?string $namaPendampingSdm = null,
    ): Evaluasi {
        $item = collect($checklist)
            ->map(fn (bool $checked, string $label) => ['label' => $label, 'checked' => $checked])
            ->values()
            ->all();

        $evaluasi->update([
            'checklist_persiapan' => $item,
            'tempat_pelaksanaan' => $tempatPelaksanaan ?? $evaluasi->tempat_pelaksanaan,
            'nama_rekan_kerja' => $namaRekanKerja ?? $evaluasi->nama_rekan_kerja,
            'nama_pendamping_sdm' => $namaPendampingSdm ?? $evaluasi->nama_pendamping_sdm,
        ]);

        return $evaluasi;
    }

    /**
     * PIC/Admin: buatkan akun login untuk Pembimbing Lapangan yang
     * sebelumnya TIDAK punya akun (Kondisi C pada dokumen aturan bisnis:
     * pembimbing minta akses SETELAH PKL sudah berjalan). Akun baru ini
     * otomatis disambungkan ke SEMUA penugasan yang sudah ada sebelumnya
     * untuk pembimbing ini (backfill pembimbing_id yang masih null) --
     * peserta TIDAK perlu mengulang pengajuan atau approval, dan begitu
     * login pembimbing langsung bisa melihat aktivitas & file lama yang
     * sudah tersimpan.
     */
    /**
     * PIC TIDAK perlu mengisi email sama sekali -- kolom `email` di tabel
     * `users` memang wajib diisi & unik (bawaan sistem auth Laravel/
     * Filament, bukan sesuatu yang kita pakai), jadi nilainya di-generate
     * OTOMATIS dari NIP di sini (mis. "198501012024@pembimbing.internal").
     * Email ini murni formalitas database -- Pembimbing TETAP login pakai
     * NIP (lihat App\Filament\Pages\Auth\Login), bukan pakai email ini.
     */
    public function buatkanAkunPembimbing(PembimbingLapangan $pembimbingLapangan, string $nip, string $password): User
    {
        if ($pembimbingLapangan->user_id) {
            throw new RuntimeException('Pembimbing Lapangan ini sudah punya akun.');
        }

        $rolePembimbing = Role::where('slug', 'pembimbing_lapangan')->firstOrFail();
        $emailOtomatis = $nip.'@pembimbing.internal';

        return DB::transaction(function () use ($pembimbingLapangan, $emailOtomatis, $nip, $password, $rolePembimbing) {
            $user = User::create([
                'name' => $pembimbingLapangan->nama,
                // Sekadar mengisi kolom wajib & unik di tabel users -- TIDAK
                // dipakai untuk login maupun dikirimi email apa pun.
                'email' => $emailOtomatis,
                // NIP inilah yang dipakai untuk login (lihat
                // App\Filament\Pages\Auth\Login) -- bukan email.
                'nip' => $nip,
                'password' => bcrypt($password),
                'role_id' => $rolePembimbing->id,
                'bagian_id' => $pembimbingLapangan->bagian_id,
                'is_active' => true,
            ]);

            $pembimbingLapangan->update(['user_id' => $user->id]);

            // Sambungkan akun baru ke SEMUA penugasan lama yang belum
            // tertaut (dibuat sebelum pembimbing ini punya akun).
            PenugasanPembimbing::where('pembimbing_lapangan_id', $pembimbingLapangan->id)
                ->whereNull('pembimbing_id')
                ->update(['pembimbing_id' => $user->id]);

            return $user;
        });
    }

    /**
     * Sisa hari sebelum tanggal_selesai pengajuan (negatif kalau sudah
     * lewat). Dipakai untuk peringatan "masa PKL akan segera berakhir".
     */
    public function sisaHariPkl(Pengajuan $pengajuan): ?int
    {
        if (! $pengajuan->tanggal_selesai) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($pengajuan->tanggal_selesai->startOfDay(), false);
    }

    /**
     * True kalau pengajuan sudah mendekati/lewat tanggal_selesai (dalam
     * ambang self::AMBANG_PERINGATAN_HARI hari) dan masih berjalan.
     */
    public function masaPklHampirBerakhir(Pengajuan $pengajuan): bool
    {
        if ($pengajuan->status !== 'berjalan') {
            return false;
        }

        $sisaHari = $this->sisaHariPkl($pengajuan);

        return $sisaHari !== null && $sisaHari <= self::AMBANG_PERINGATAN_HARI;
    }

    /**
     * Hitung berapa pengajuan yang SEDANG berada di setiap tahap disposisi
     * (urutan 1=GM, 2=Kabag SDM, 3=Staff SDM) — yaitu tahap dengan urutan
     * paling kecil yang statusnya masih "menunggu" untuk tiap pengajuan.
     * Dipakai oleh dashboard (mis. funnel/statistik alur) agar logikanya
     * konsisten dengan query SQL pada TugasSaya::scopeGiliranSaya().
     *
     * @return array<int, int> urutan => jumlah pengajuan
     */
    public function hitungTahapAktif(): array
    {
        $activeStepPerSubmission = ApprovalWorkflow::query()
            ->selectRaw('pengajuan_id, MIN(urutan) as active_urutan')
            ->where('status', 'menunggu')
            ->groupBy('pengajuan_id');

        return DB::query()
            ->fromSub($activeStepPerSubmission, 'active_steps')
            ->selectRaw('active_urutan, COUNT(*) as total')
            ->groupBy('active_urutan')
            ->pluck('total', 'active_urutan')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    // ------------------------------------------------------------------
    // Helper internal
    // ------------------------------------------------------------------

    /**
     * Aturan bisnis wajib: masa PKL/Penelitian maksimal
     * self::MASA_PKL_MAKSIMAL_BULAN bulan. Berlaku untuk pengajuan biasa
     * MAUPUN pengajuan hasil perpanjangan (perpanjangan tidak boleh
     * dipakai untuk mengakali batas ini dengan periode yang lebih panjang).
     */
    private function validasiDurasiMaksimal(string $tanggalMulai, string $tanggalSelesai): void
    {
        $mulai = \Illuminate\Support\Carbon::parse($tanggalMulai);
        $selesai = \Illuminate\Support\Carbon::parse($tanggalSelesai);
        $batasMaksimal = $mulai->copy()->addMonths(self::MASA_PKL_MAKSIMAL_BULAN);

        if ($selesai->gt($batasMaksimal)) {
            throw new RuntimeException('Masa PKL/Penelitian maksimal '.self::MASA_PKL_MAKSIMAL_BULAN.' bulan.');
        }
    }

    private function pastikanRole(User $user, RoleSlug ...$roles): void
    {
        if (! $user->hasAnyRole($roles)) {
            throw new RuntimeException('Anda tidak memiliki kewenangan untuk menjalankan aksi ini.');
        }
    }

    private function pastikanPenilaiBerwenang(Pengajuan $pengajuan, User $user): void
    {
        if ($user->hasRole(RoleSlug::PIC)) {
            return;
        }

        if ($user->hasRole(RoleSlug::PEMBIMBING_LAPANGAN)
            && $pengajuan->penugasanPembimbing?->pembimbing_id === $user->id) {
            return;
        }

        throw new RuntimeException('Hanya PIC atau Pembimbing Lapangan yang ditugaskan yang dapat mengisi penilaian.');
    }

    private function pastikanStatus(Pengajuan $pengajuan, array $statusDiperbolehkan): void
    {
        if (! in_array($pengajuan->status, $statusDiperbolehkan, true)) {
            throw new RuntimeException("Aksi ini tidak dapat dilakukan pada status '{$pengajuan->status}'.");
        }
    }

    private function catatRiwayat(Pengajuan $pengajuan, ?string $statusSebelumnya, string $statusBaru, ?string $keterangan = null): void
    {
        RiwayatStatus::create([
            'pengajuan_id' => $pengajuan->id,
            'changed_by' => auth()->id(),
            'status_sebelumnya' => $statusSebelumnya,
            'status_baru' => $statusBaru,
            'keterangan' => $keterangan,
        ]);
    }

    /**
     * Notifikasi giliran tahap disposisi berikutnya. PENTING: role
     * 'kepala_bagian' TIDAK boleh lewat notifikasiRole() biasa (yang
     * broadcast ke SEMUA user role itu) karena Kepala Bagian ada BANYAK
     * orangnya (satu per bagian) -- harus tepat sasaran ke Kepala Bagian
     * dari bagian_tujuan pengajuan ini saja, pakai notifikasiUser().
     */
    private function notifikasiLangkahApprovalBerikutnya(Pengajuan $pengajuan): void
    {
        $tahap = $pengajuan->approvalWorkflows()->where('status', 'menunggu')->orderBy('urutan')->first();
        if (! $tahap) {
            return;
        }

        $tahapSelesai = $pengajuan->approvalWorkflows()
            ->where('status', 'ditandatangani')
            ->orderByDesc('urutan')
            ->first();
        $namaRoleSelesai = $tahapSelesai ? self::LABEL_ROLE_APPROVAL[self::URUTAN_APPROVAL[$tahapSelesai->urutan] ?? ''] ?? 'Tahap sebelumnya' : null;

        $roleSlug = self::URUTAN_APPROVAL[$tahap->urutan] ?? null;
        if ($roleSlug === 'kepala_bagian') {
            $kepalaBagian = $pengajuan->bagianTujuan?->kepalaBagian;
            if ($kepalaBagian) {
                $this->notifikasiUser($kepalaBagian, $pengajuan, 'Menunggu Disposisi Anda', "Pengajuan {$pengajuan->nomor_agenda} menunggu tanda tangan Anda (tahap terakhir, sekalian isi catatan calon Pembimbing Lapangan).");
            }
        } elseif ($roleSlug) {
            $this->notifikasiRole($pengajuan, $roleSlug, 'Menunggu Disposisi Anda', "Pengajuan {$pengajuan->nomor_agenda} menunggu persetujuan Anda.");
        }

        // Sesuai flowchart AS-IS: PIC menerima kabar hasil approval di SETIAP
        // tahap (bukan cuma di akhir), lalu status "diteruskan" ke tahap
        // berikutnya. Sistem meneruskan disposisi otomatis, tapi PIC tetap
        // perlu tahu setiap kali ada tahap yang selesai.
        if ($namaRoleSelesai) {
            $this->notifikasiRole($pengajuan, 'pic', 'Disposisi Diteruskan', "Pengajuan {$pengajuan->nomor_agenda} telah disetujui {$namaRoleSelesai}, diteruskan ke tahap berikutnya.");
        }
    }

    private function notifikasiRole(Pengajuan $pengajuan, string $roleSlug, string $judul, string $pesan): void
    {
        $this->notifications->role($pengajuan, $roleSlug, $judul, $pesan);
    }

    private function notifikasiPeserta(Pengajuan $pengajuan, string $judul, string $pesan): void
    {
        $this->notifications->participant($pengajuan, $judul, $pesan);
    }

    private function notifikasiUser(User $user, Pengajuan $pengajuan, string $judul, string $pesan): void
    {
        $this->notifications->user($user, $pengajuan, $judul, $pesan);
    }
}