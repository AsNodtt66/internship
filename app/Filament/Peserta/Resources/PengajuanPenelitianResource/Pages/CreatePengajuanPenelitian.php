<?php

namespace App\Filament\Peserta\Resources\PengajuanPenelitianResource\Pages;

use App\Filament\Peserta\Resources\PengajuanPenelitianResource;
use App\Filament\Peserta\Resources\PengajuanResource\Pages\CreatePengajuan as BaseCreatePengajuan;

class CreatePengajuanPenelitian extends BaseCreatePengajuan
{
    protected static string $resource = PengajuanPenelitianResource::class;

    /**
     * Jaga-jaga: pastikan pengajuan yang dibuat dari menu "Pengajuan
     * Penelitian" selalu tersimpan sebagai jenis 'Penelitian'. Lihat
     * komentar yang sama di CreatePengajuanPkl.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data['jenis_pengajuan'] = 'Penelitian';

        return $data;
    }
}
