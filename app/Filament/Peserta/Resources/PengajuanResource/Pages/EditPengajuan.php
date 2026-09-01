<?php

namespace App\Filament\Peserta\Resources\PengajuanResource\Pages;

use App\Filament\Peserta\Resources\PengajuanResource;
use App\Models\DokumenPersyaratan;
use App\Models\Pengajuan;
use App\Services\PengajuanWorkflowService;
use Filament\Resources\Pages\EditRecord;

/** @property Pengajuan $record */
class EditPengajuan extends EditRecord
{
    protected static string $resource = PengajuanResource::class;

    /**
     * Peserta hanya boleh mengedit pengajuan yang masih draft
     * atau diminta revisi dokumen — mencegah edit setelah masuk proses approval.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        abort_unless(
            in_array($this->record->status, ['draft', 'dokumen_ditolak']),
            403,
            'Pengajuan ini sudah masuk proses dan tidak dapat diedit.'
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Sama seperti CreatePengajuan — Wizard di form ini sudah punya tombol
     * submit sendiri di step terakhir, jadi footer bawaan dimatikan supaya
     * tidak dobel.
     */
    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * Status 'draft' cuma dipakai untuk PENGAJUAN BARU hasil perpanjangan
     * (lihat PengajuanWorkflowService::buatPengajuanPerpanjanganBaru()) --
     * dibuat otomatis saat Kepala Bagian menyetujui permohonan perpanjangan,
     * dan peserta wajib melengkapi ulang + submit dari awal. Sebelum fix
     * ini, menyimpan form Edit TIDAK PERNAH mengubah status keluar dari
     * 'draft', jadi pengajuan perpanjangan itu tidak akan pernah masuk ke
     * antrean verifikasi PIC. Status 'dokumen_ditolak' sengaja TIDAK
     * disentuh di sini karena sudah ada mekanismenya sendiri per-dokumen
     * lewat halaman "Dokumen Saya" (PengajuanWorkflowService::perbaikiDokumen()).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Draft hasil perpanjangan tetap draft selama proses penyimpanan.
        // Transition ke `diajukan`, timestamp, riwayat, dan notifikasi PIC
        // dilakukan satu pintu oleh PengajuanWorkflowService::ajukan().
        return $data;
    }

    /**
     * Sama seperti CreatePengajuan::afterCreate() — kalau pengajuan draft
     * ini baru pertama kali disubmit (jadi belum punya baris
     * DokumenPersyaratan sama sekali, karena pengajuan hasil perpanjangan
     * dibuat sistem tanpa dokumen), catat tiap file yang diunggah supaya
     * PIC bisa memverifikasinya. Pakai updateOrCreate supaya aman kalau
     * peserta membuka & menyimpan form ini lebih dari sekali.
     */
    protected function afterSave(): void
    {
        $pengajuan = $this->record;

        if (! $pengajuan->dokumenPersyaratans()->exists()) {
            foreach (CreatePengajuan::FIELD_DOKUMEN as $field => $label) {
                $filePath = $pengajuan->getAttribute($field);

                if (blank($filePath)) {
                    continue;
                }

                DokumenPersyaratan::updateOrCreate(
                    ['pengajuan_id' => $pengajuan->id, 'jenis_dokumen' => $label],
                    ['file_path' => $filePath, 'status_verifikasi' => 'menunggu'],
                );
            }
        }

        if ($pengajuan->status === 'draft') {
            app(PengajuanWorkflowService::class)->ajukan($pengajuan);
        }
    }
}
