<?php

namespace App\Support\Ui;

final class PengajuanStatusPresenter
{
    public static function label(?string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'diajukan' => 'Menunggu Verifikasi PIC',
            'verifikasi_dokumen' => 'Verifikasi Dokumen',
            'dokumen_ditolak' => 'Dokumen Perlu Revisi',
            'proses_approval' => 'Proses Persetujuan',
            'menunggu_persetujuan_pembimbing' => 'Menunggu Persetujuan Pembimbing',
            'menunggu_catatan_pembimbing' => 'Menunggu Catatan Pembimbing',
            'menunggu_penetapan_pembimbing' => 'Menunggu Penetapan Pembimbing',
            'menunggu_konfirmasi_peserta' => 'Menunggu Konfirmasi Peserta',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'berjalan' => 'Sedang Berjalan',
            'selesai' => 'Selesai',
            'perlu_perpanjangan' => 'Perlu Tindak Lanjut Perpanjangan',
            default => $status ? ucwords(str_replace('_', ' ', $status)) : 'Belum tersedia',
        };
    }

    public static function description(?string $status): string
    {
        return match ($status) {
            'draft' => 'Pengajuan belum dikirim. Lengkapi data dan dokumen, lalu kirim saat sudah siap.',
            'diajukan' => 'Pengajuan sudah dikirim dan menunggu pemeriksaan awal oleh PIC.',
            'verifikasi_dokumen' => 'PIC sedang memeriksa kelengkapan dan kesesuaian dokumen.',
            'dokumen_ditolak' => 'Ada dokumen yang perlu diperbaiki sebelum proses dapat dilanjutkan.',
            'proses_approval' => 'Dokumen lengkap dan sedang melalui tahapan persetujuan internal.',
            'menunggu_persetujuan_pembimbing' => 'Penempatan menunggu persetujuan pembimbing lapangan.',
            'menunggu_catatan_pembimbing' => 'Proses menunggu catatan dari pembimbing lapangan.',
            'menunggu_penetapan_pembimbing' => 'Pengajuan disetujui dan menunggu penetapan pembimbing lapangan.',
            'menunggu_konfirmasi_peserta' => 'Sistem menunggu konfirmasi dari peserta sebelum proses dilanjutkan.',
            'disetujui' => 'Pengajuan telah disetujui. Pantau informasi penempatan dan pembimbing.',
            'ditolak' => 'Pengajuan tidak dapat dilanjutkan. Buka detail pengajuan untuk melihat catatan terkait.',
            'berjalan' => 'Kegiatan sedang berlangsung. Pantau jadwal, dokumen, dan informasi dari pembimbing.',
            'selesai' => 'Kegiatan dan proses administrasi pengajuan telah selesai.',
            'perlu_perpanjangan' => 'Ada tindak lanjut perpanjangan yang perlu diselesaikan sebelum proses ditutup.',
            default => 'Buka detail pengajuan untuk melihat informasi terbaru.',
        };
    }

    public static function color(?string $status): string
    {
        return match ($status) {
            'dokumen_ditolak', 'ditolak' => 'danger',
            'diajukan', 'verifikasi_dokumen', 'proses_approval',
            'menunggu_persetujuan_pembimbing', 'menunggu_catatan_pembimbing',
            'menunggu_penetapan_pembimbing', 'menunggu_konfirmasi_peserta',
            'perlu_perpanjangan' => 'warning',
            'disetujui', 'berjalan', 'selesai' => 'success',
            default => 'gray',
        };
    }
}
