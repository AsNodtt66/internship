<?php

namespace App\Services;

use App\Models\Pengajuan;

class PengajuanTimelineService
{
    /**
     * Urutan tahap yang ditampilkan ke peserta.
     * key => label yang muncul di UI.
     * Urutan approval mengikuti dokumen Business Process AS-IS resmi:
     * GM -> Kepala Bagian SDM -> Staff SDM.
     */
    protected const STEPS = [
        'pengajuan' => 'Pengajuan',
        'verifikasi_pic' => 'Verifikasi PIC',
        'gm' => 'Persetujuan GM',
        'kabag_sdm' => 'Persetujuan Kepala Bagian SDM',
        'staff_sdm' => 'Persetujuan Staf SDM',
        'kepala_bagian' => 'Persetujuan Kepala Bagian Tujuan',
        'pembimbing' => 'Penetapan Pembimbing',
        'surat_balasan' => 'Surat Balasan',
        'selesai' => 'Penyelesaian Administrasi',
    ];

    /**
     * Bangun array tahapan siap-tampil untuk satu pengajuan.
     * Return: ['key' => ['label' => ..., 'state' => 'selesai|sedang_diproses|belum_diproses|ditolak']]
     */
    public function build(Pengajuan $pengajuan): array
    {
        $status = $pengajuan->status;
        $approvals = $pengajuan->approvalWorkflows()->orderBy('urutan')->get(['urutan', 'status']);

        $result = [];

        foreach (self::STEPS as $key => $label) {
            $result[$key] = [
                'label' => $label,
                'state' => $this->resolveState($key, $status, $approvals, $pengajuan),
            ];
        }

        return $result;
    }

    protected function resolveState(string $key, string $status, $approvals, Pengajuan $pengajuan): string
    {
        return match ($key) {
            'pengajuan' => $status === 'draft' ? 'belum_diproses' : 'selesai',

            'verifikasi_pic' => match (true) {
                $status === 'draft' => 'belum_diproses',
                $status === 'diajukan' => 'sedang_diproses',
                $status === 'dokumen_ditolak' => 'ditolak',
                default => 'selesai',
            },

            'gm' => $this->resolveApprovalStep(1, $status, $approvals),
            'kabag_sdm' => $this->resolveApprovalStep(2, $status, $approvals),
            'staff_sdm' => $this->resolveApprovalStep(3, $status, $approvals),
            // Kepala Bagian Tujuan sekarang tahap disposisi ke-4, mekanisme
            // SAMA PERSIS dengan GM/Kabag SDM/Staff SDM (tanda tangan) --
            // bedanya begitu tahap ini selesai, catatan calon Pembimbing
            // ikut tersimpan otomatis (lihat PengajuanWorkflowService::
            // tandatanganiLangkah()).
            'kepala_bagian' => $this->resolveApprovalStep(4, $status, $approvals),

            'pembimbing' => match (true) {
                $pengajuan->penugasanPembimbing?->status === 'disetujui' => 'selesai',
                in_array($status, ['menunggu_penetapan_pembimbing', 'berjalan']) => 'sedang_diproses',
                default => 'belum_diproses',
            },

            'surat_balasan' => match (true) {
                $pengajuan->suratBalasan?->status === 'terbit' => 'selesai',
                $status === 'menunggu_penetapan_pembimbing' => 'sedang_diproses',
                default => 'belum_diproses',
            },

            'selesai' => match (true) {
                $pengajuan->suratKeterangan !== null => 'selesai',
                in_array($status, ['selesai', 'perlu_perpanjangan']) => 'sedang_diproses',
                default => 'belum_diproses',
            },

            default => 'belum_diproses',
        };
    }

    /**
     * Ambil state satu tahap approval berdasarkan urutan (1=GM, 2=Kepala Bagian SDM, 3=Staff SDM).
     * Hanya kolom 'status' yang dibaca — 'catatan' dan 'penandatangan_id' TIDAK pernah diekspos.
     */
    protected function resolveApprovalStep(int $urutan, string $status, $approvals): string
    {
        if ($status === 'ditolak') {
            return 'ditolak';
        }

        if (! in_array($status, ['proses_approval', 'disetujui', 'menunggu_persetujuan_pembimbing', 'menunggu_catatan_pembimbing', 'menunggu_penetapan_pembimbing', 'berjalan', 'selesai', 'perlu_perpanjangan'])) {
            return 'belum_diproses';
        }

        $step = $approvals->firstWhere('urutan', $urutan);

        if (! $step) {
            // Sudah lewat status proses_approval tapi record belum ada -> anggap selesai
            return in_array($status, ['disetujui', 'menunggu_persetujuan_pembimbing', 'menunggu_catatan_pembimbing', 'menunggu_penetapan_pembimbing', 'berjalan', 'selesai']) ? 'selesai' : 'belum_diproses';
        }

        // Tahap ini "sedang diproses" hanya jika ini tahap 'menunggu' pertama
        // (disposisi berjalan berurutan, sesuai PengajuanWorkflowService::tandatanganiLangkah).
        // Tahap disposisi (GM/Kabag SDM/Staff SDM) hanya mengetahui & menandatangani,
        // tidak ada opsi menolak di sini.
        $langkahAktif = $approvals->firstWhere('status', 'menunggu');

        return match ($step->status) {
            'ditandatangani' => 'selesai',
            'menunggu' => ($langkahAktif && $langkahAktif->urutan === $step->urutan)
                ? 'sedang_diproses'
                : 'belum_diproses',
            default => 'belum_diproses',
        };
    }
}
