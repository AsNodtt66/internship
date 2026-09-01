<?php

namespace App\Filament\Peserta\Resources\PengajuanResource\Pages;

use App\Enums\RoleSlug;
use App\Filament\Peserta\Resources\PengajuanResource;
use App\Models\Pengajuan;
use App\Services\PengajuanWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/** @property Pengajuan $record */
class ViewPengajuan extends ViewRecord
{
    protected static string $resource = PengajuanResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Status Pengajuan')
                ->columns(3)
                ->schema([
                    TextEntry::make('nomor_agenda')->label('Nomor Agenda')->placeholder('Belum diterbitkan'),
                    TextEntry::make('created_at')->label('Tanggal Pengajuan')->dateTime('d M Y'),
                    TextEntry::make('status')->label('Status Saat Ini')->badge(),
                ]),

            Section::make('Informasi Pengajuan')
                ->columns(2)
                ->schema([
                    TextEntry::make('jenis_pengajuan')->label('Jenis Pengajuan'),
                    TextEntry::make('bagian.nama_bagian')->label('Bagian yang Dituju'),
                    TextEntry::make('tanggal_mulai')->label('Periode Mulai')->date('d M Y'),
                    TextEntry::make('tanggal_selesai')->label('Periode Selesai')->date('d M Y'),
                    TextEntry::make('judul_penelitian')->label('Topik / Judul')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('tujuan')->label('Tujuan PKL / Magang / Penelitian')->placeholder('-')->columnSpanFull(),
                ]),

            Section::make('Data Pribadi')
                ->columns(2)
                ->schema([
                    TextEntry::make('nama_lengkap')->label('Nama Lengkap')->placeholder('-'),
                    TextEntry::make('jenis_kelamin')->label('Jenis Kelamin')->placeholder('-'),
                    TextEntry::make('tempat_lahir')->label('Tempat Lahir')->placeholder('-'),
                    TextEntry::make('tanggal_lahir')->label('Tanggal Lahir')->date('d M Y')->placeholder('-'),
                    TextEntry::make('nik')->label('NIK')->placeholder('-'),
                    TextEntry::make('no_hp')->label('Nomor HP / WhatsApp Aktif')->placeholder('-'),
                    TextEntry::make('email_aktif')->label('Email Aktif')->placeholder('-'),
                ]),

            Section::make('Informasi Akademik')
                ->columns(2)
                ->schema([
                    TextEntry::make('nama_institusi')->label('Nama Perguruan Tinggi / Sekolah')->placeholder('-'),
                    TextEntry::make('fakultas')->label('Fakultas')->placeholder('-'),
                    TextEntry::make('program_studi')->label('Program Studi / Jurusan')->placeholder('-'),
                    TextEntry::make('jenjang_pendidikan')->label('Jenjang Pendidikan')->placeholder('-'),
                    TextEntry::make('semester')->label('Semester')->placeholder('-'),
                    TextEntry::make('nim_nisn')->label('NIM / NISN')->placeholder('-'),
                    TextEntry::make('ipk_nilai')->label('IPK / Nilai Terakhir')->placeholder('-'),
                ]),

            Section::make('Dosen / Guru Pembimbing')
                ->columns(3)
                ->schema([
                    TextEntry::make('nama_pembimbing_akademik')->label('Nama')->placeholder('-'),
                    TextEntry::make('no_hp_pembimbing_akademik')->label('Nomor HP')->placeholder('-'),
                    TextEntry::make('email_pembimbing_akademik')->label('Email')->placeholder('-'),
                ]),

            Section::make('Hasil Penilaian')
                ->visible(fn (Pengajuan $record) => $record->penilaian !== null)
                ->schema([
                    ViewEntry::make('penilaian')
                        ->hiddenLabel()
                        ->view('filament.infolists.hasil-penilaian'),
                ]),

            Section::make('Hasil Evaluasi')
                ->visible(fn (Pengajuan $record) => $record->evaluasi?->nilai_akhir !== null)
                ->schema([
                    ViewEntry::make('evaluasi')
                        ->hiddenLabel()
                        ->view('filament.infolists.hasil-evaluasi'),
                ]),

            Section::make('Aspek Penilaian')
                ->icon('heroicon-o-list-bullet')
                ->visible(fn (Pengajuan $record) => filled(data_get($record->evaluasi, 'aspek_penilaian_default')) && data_get($record->evaluasi, 'dinilai_at') === null)
                ->schema([
                    TextEntry::make('evaluasi.aspek_penilaian_default')
                        ->label('Aspek yang akan dinilai (belum ada hasil)')
                        ->listWithLineBreaks()
                        ->bulleted(),
                ]),

            Section::make('Dokumen Persyaratan')
                ->columns(3)
                ->schema(self::entriDokumen()),

            Section::make('Informasi Tambahan')
                ->columns(2)
                ->schema([
                    TextEntry::make('keahlian_skill')->label('Keahlian yang Dimiliki')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('motivasi')->label('Motivasi')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('sumber_informasi')->label('Sumber Informasi')->placeholder('-'),
                    TextEntry::make('rekomendasi_dari')->label('Direkomendasikan Oleh')->placeholder('-'),
                ]),
        ]);
    }

    /**
     * Entri untuk 6 dokumen persyaratan yang diunggah peserta saat pengajuan
     * (disimpan langsung di kolom pengajuans, bukan tabel dokumen_persyaratans
     * terpisah). Tiap dokumen jadi link "Lihat Dokumen" kalau sudah diunggah.
     *
     * @return array<int, TextEntry>
     */
    protected static function entriDokumen(): array
    {
        $dokumen = [
            'file_surat_pengantar' => 'Surat Pengantar Resmi',
            'file_cv' => 'Curriculum Vitae (CV)',
            'file_proposal' => 'Proposal PKL / Magang / Penelitian',
            'file_ktp_ktm' => 'KTP atau KTM',
            'file_transkrip' => 'Transkrip Nilai Terbaru',
            'file_pas_foto' => 'Pas Foto 3×4',
        ];

        return collect($dokumen)
            ->map(fn (string $label, string $kolom) => TextEntry::make($kolom)
                ->label($label)
                ->formatStateUsing(fn (?string $state) => $state ? 'Lihat Dokumen' : 'Belum diunggah')
                ->url(fn (?string $state, Pengajuan $record) => $state ? route('documents.pengajuan', ['pengajuan' => $record, 'field' => $kolom]) : null)
                ->openUrlInNewTab()
                ->color(fn (?string $state) => $state ? 'primary' : 'gray'))
            ->values()
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => in_array($this->record->status, ['draft', 'dokumen_ditolak'])),

            Action::make('downloadSuratBalasan')
                ->authorize(fn () => Auth::user()?->can('view', $this->record) === true)
                ->label('Unduh Surat Balasan')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => $this->record->suratBalasan !== null)
                ->url(fn () => route('documents.surat-balasan', $this->record->suratBalasan))
                ->openUrlInNewTab(),

            Action::make('downloadSuratKeterangan')
                ->authorize(fn () => Auth::user()?->can('view', $this->record) === true)
                ->label(fn () => $this->record->suratKeterangan?->isSelesai()
                    ? 'Unduh Surat Keterangan Selesai'
                    : 'Unduh Surat Perpanjangan')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->record->suratKeterangan !== null)
                ->url(fn () => route('documents.surat-keterangan', $this->record->suratKeterangan))
                ->openUrlInNewTab(),

            Action::make('downloadPenilaian')
                ->authorize(fn () => Auth::user()?->can('view', $this->record) === true)
                ->label('Unduh Penilaian (PDF)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => $this->record->penilaian !== null)
                ->url(fn () => route('documents.penilaian', $this->record->penilaian))
                ->openUrlInNewTab(),

            Action::make('pilihPerpanjang')
                ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PESERTA) === true
                    && Auth::user()->can('view', $this->record) === true)
                ->label('Pilih Perpanjangan')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Pilih Perpanjangan')
                ->modalDescription('Pilih ini jika Anda akan melanjutkan kegiatan melalui proses perpanjangan. Setelah keputusan tersimpan, ikuti langkah pengajuan perpanjangan yang tersedia.')
                ->modalSubmitActionLabel('Ya, Pilih Perpanjangan')
                ->visible(fn () => $this->record->penilaian !== null && $this->record->penilaian->keputusan === null
                    && $this->record->pengajuan_asal_id === null)
                ->action(function () {
                    app(PengajuanWorkflowService::class)->pilihKeputusanPerpanjangan($this->record->penilaian, 'perpanjang');

                    Notification::make()->title('Pilihan perpanjangan tersimpan')->body('Lanjutkan dengan mengajukan periode perpanjangan pada tindakan yang tersedia.')->success()->send();

                    $this->record->refresh()->load('penilaian');
                }),

            Action::make('pilihTidakPerpanjang')
                ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PESERTA) === true
                    && Auth::user()->can('view', $this->record) === true)
                ->label('Selesaikan Tanpa Perpanjangan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Tanpa Perpanjangan')
                ->modalDescription('Pilih ini jika Anda tidak akan memperpanjang kegiatan. Keputusan ini akan menutup proses perpanjangan untuk pengajuan tersebut.')
                ->modalSubmitActionLabel('Ya, Selesaikan')
                ->visible(fn () => $this->record->penilaian !== null && $this->record->penilaian->keputusan === null)
                ->action(function () {
                    app(PengajuanWorkflowService::class)->pilihKeputusanPerpanjangan($this->record->penilaian, 'tidak_perpanjang');

                    Notification::make()->title('Keputusan tersimpan')->body('Anda memilih menyelesaikan kegiatan tanpa perpanjangan.')->success()->send();

                    $this->record->refresh()->load('penilaian');
                }),

            Action::make('usulkanAspekPenilaian')
                ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PESERTA) === true
                    && Auth::user()->can('view', $this->record) === true)
                ->label(fn () => $this->record->evaluasi?->aspek_penilaian_default ? 'Ubah Aspek Penilaian' : 'Isi Aspek Penilaian')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->modalDescription('Tentukan aspek yang relevan dengan kegiatan Anda. Pembimbing lapangan akan menilai aspek tersebut melalui sistem jika memiliki akun; jika tidak, PIC dapat merekap hasil dari lembar penilaian.')
                ->visible(fn () => $this->record->status === 'berjalan' && $this->record->evaluasi?->dinilai_at === null)
                ->fillForm(fn () => [
                    'aspek' => collect($this->record->evaluasi?->aspek_penilaian_default ?: [
                        'Disiplin', 'Kemampuan bekerja sama', 'Kemampuan bekerja secara mandiri',
                        'Kecepatan kerja', 'Kemampuan berkomunikasi',
                    ])->map(fn (string $nama) => ['nama' => $nama])->all(),
                ])
                ->schema([
                    Repeater::make('aspek')
                        ->label('Aspek Penilaian')
                        ->schema([
                            TextInput::make('nama')->label('Nama Aspek')->required(),
                        ])
                        ->addActionLabel('Tambah Aspek')
                        ->reorderable()
                        ->minItems(1),
                ])
                ->action(function (array $data) {
                    try {
                        app(PengajuanWorkflowService::class)->usulkanAspekPenilaian(
                            $this->record,
                            collect($data['aspek'])->pluck('nama')->all(),
                        );

                        Notification::make()->title('Aspek penilaian tersimpan')->success()->send();

                        $this->record->refresh()->load('evaluasi');
                    } catch (\RuntimeException $e) {
                        Notification::make()->title('Aspek penilaian belum tersimpan')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('ajukanPerpanjangan')
                ->authorize(fn () => Auth::user()->hasRole(RoleSlug::PESERTA)
                    && Auth::user()->can('view', $this->record))
                ->label('Ajukan Perpanjangan')
                ->icon('heroicon-o-document-arrow-up')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'perlu_perpanjangan'
                    && $this->record->evaluasi?->dinilai_at !== null
                    && $this->record->pengajuan_asal_id === null
                    && ! $this->record->perpanjangans()->where('status', 'menunggu')->exists())
                ->schema([
                    DatePicker::make('tanggal_mulai_baru')->label('Tanggal Mulai Periode Baru')->required(),
                    DatePicker::make('tanggal_selesai_baru')->label('Tanggal Selesai Periode Baru')->required()->afterOrEqual('tanggal_mulai_baru'),
                ])
                ->modalHeading('Ajukan Perpanjangan')
                ->modalDescription('Tentukan periode baru. Jika PIC menyetujui permohonan, sistem membuat pengajuan periode baru yang perlu Anda lengkapi dengan alasan dan surat pengantar kampus.')
                ->action(function (array $data) {
                    app(PengajuanWorkflowService::class)->ajukanPermohonanPerpanjangan(
                        $this->record,
                        $data['tanggal_mulai_baru'],
                        $data['tanggal_selesai_baru'],
                    );

                    Notification::make()->title('Permohonan perpanjangan diajukan')->body('Permohonan menunggu keputusan PIC setelah pengecekan slot dan kuota bagian.')->success()->send();

                    $this->record->refresh();
                }),
        ];
    }
}
