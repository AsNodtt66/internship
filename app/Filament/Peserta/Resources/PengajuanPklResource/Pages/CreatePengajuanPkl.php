<?php

namespace App\Filament\Peserta\Resources\PengajuanPklResource\Pages;

use App\Filament\Peserta\Resources\PengajuanPklResource;
use App\Filament\Peserta\Resources\PengajuanResource\Pages\CreatePengajuan as BaseCreatePengajuan;

class CreatePengajuanPkl extends BaseCreatePengajuan
{
    protected static string $resource = PengajuanPklResource::class;

    /**
     * Jaga-jaga: pastikan pengajuan yang dibuat dari menu "Pengajuan
     * PKL/Magang" selalu tersimpan sebagai jenis 'PKL/Magang', apa pun
     * yang dipilih di step Radio "Jenis Pengajuan" pada form (form-nya
     * sendiri tetap diwarisi apa adanya dari PengajuanResource, tidak
     * diduplikasi/diubah).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        $data['jenis_pengajuan'] = 'PKL/Magang';

        return $data;
    }
}
