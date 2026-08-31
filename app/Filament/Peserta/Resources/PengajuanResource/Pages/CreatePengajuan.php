<?php

namespace App\Filament\Peserta\Resources\PengajuanResource\Pages;

use App\Filament\Peserta\Resources\PengajuanResource;
use App\Models\DokumenPersyaratan;
use App\Services\PengajuanWorkflowService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePengajuan extends CreateRecord
{
    protected static string $resource = PengajuanResource::class;

    /**
     * Wizard di form ini sudah punya tombol submit sendiri di step
     * terakhir (lihat ->submitAction() di PengajuanResource::form()),
     * jadi tombol Create/Create & create another/Cancel bawaan Filament
     * yang biasanya nempel di footer halaman (dan ikut muncul di SEMUA
     * step) sengaja dimatikan di sini.
     */
    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * Field upload di Wizard form (key) -> label jenis dokumen yang tersimpan
     * ke tabel dokumen_persyaratans supaya bisa diverifikasi PIC.
     */
    public const FIELD_DOKUMEN = [
        'file_surat_pengantar' => 'Surat Pengantar Resmi',
        'file_cv' => 'Curriculum Vitae (CV)',
        'file_proposal' => 'Proposal PKL / Magang / Penelitian',
        'file_ktp_ktm' => 'KTP atau KTM',
        'file_transkrip' => 'Transkrip Nilai Terbaru',
        'file_pas_foto' => 'Pas Foto 3x4',
        'file_data_penelitian' => 'Data yang Dibutuhkan untuk Diteliti',
        'file_bpjs_ketenagakerjaan' => 'Kartu BPJS Ketenagakerjaan',
        // Dokumen pelengkap perpanjangan (hanya terisi pada Pengajuan hasil
        // perpanjangan -- lihat PengajuanResource step "Upload Dokumen
        // Persyaratan"), ikut tercatat sebagai DokumenPersyaratan otomatis
        // lewat konstanta ini supaya PIC bisa memverifikasinya juga.
        'file_surat_kampus_perpanjangan' => 'Surat Pengantar Perpanjangan dari Kampus',
    ];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pakai firstOrCreate, bukan Auth::user()->peserta->id langsung -- supaya
        // tidak crash kalau (karena sebab apa pun) baris Peserta belum ada untuk
        // user ini. universitas/jurusan diberi placeholder kalau kosong karena
        // wajib diisi (NOT NULL) di skema tabel pesertas.
        $peserta = Auth::user()->peserta ?? \App\Models\Peserta::firstOrCreate(
            ['user_id' => Auth::id()],
            ['universitas' => '-', 'jurusan' => '-']
        );

        $data['peserta_id'] = $peserta->id;
        // Simpan sebagai draft dulu. Transition resmi ke `diajukan` dilakukan
        // setelah semua DokumenPersyaratan berhasil dicatat, melalui service.
        $data['status'] = 'draft';

        return $data;
    }

    /**
     * Setelah pengajuan tersimpan, catat tiap file yang diupload sebagai
     * baris DokumenPersyaratan (status awal: menunggu) supaya PIC bisa
     * memverifikasinya satu per satu lewat menu Pengajuan di Admin panel.
     */
    protected function afterCreate(): void
    {
        $pengajuan = $this->record;

        foreach (self::FIELD_DOKUMEN as $field => $label) {
            $filePath = $pengajuan->getAttribute($field);

            if (blank($filePath)) {
                continue;
            }

            DokumenPersyaratan::create([
                'pengajuan_id' => $pengajuan->id,
                'jenis_dokumen' => $label,
                'file_path' => $filePath,
                'status_verifikasi' => 'menunggu',
            ]);
        }

        app(PengajuanWorkflowService::class)->ajukan($pengajuan);
    }
}