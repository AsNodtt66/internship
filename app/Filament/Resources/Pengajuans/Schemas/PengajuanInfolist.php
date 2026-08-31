<?php

namespace App\Filament\Resources\Pengajuans\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PengajuanInfolist
{
    protected static function warnaStatus(string $status): string
    {
        return match ($status) {
            'selesai' => 'success',
            'ditolak' => 'danger',
            'perlu_perpanjangan' => 'warning',
            'draft' => 'gray',
            default => 'primary',
        };
    }

    protected static function ikonStatus(string $status): string
    {
        return match ($status) {
            'selesai' => 'heroicon-o-check-circle',
            'ditolak' => 'heroicon-o-x-circle',
            'perlu_perpanjangan' => 'heroicon-o-arrow-path',
            'berjalan' => 'heroicon-o-play-circle',
            default => 'heroicon-o-clock',
        };
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)
                ->schema([
                    TextEntry::make('status')
                        ->label('')
                        ->badge()
                        ->icon(fn (string $state) => self::ikonStatus($state))
                        ->color(fn (string $state) => self::warnaStatus($state))
                        ->extraAttributes(['class' => 'text-base'])
                        ->columnSpanFull(),
                ]),

            Section::make('Progress Pengajuan')
                ->icon('heroicon-o-signal')
                ->schema([
                    ViewEntry::make('progress_timeline')
                        ->label('')
                        ->view('filament.infolists.progress-timeline'),
                ]),

            Section::make('Data Peserta')
                ->icon('heroicon-o-user')
                ->columns(3)
                ->schema([
                    ViewEntry::make('foto_peserta')
                        ->label('Pas Foto')
                        ->view('filament.infolists.foto-peserta')
                        ->visible(fn ($record) => $record->dokumenPersyaratans()->where('jenis_dokumen', 'Pas Foto 3x4')->exists()),
                    TextEntry::make('peserta.user.name')->label('Nama')->icon('heroicon-o-identification'),
                    TextEntry::make('peserta.nim')->label('NIM'),
                    TextEntry::make('peserta.universitas')->label('Universitas')->icon('heroicon-o-academic-cap'),
                ]),

            Section::make('Detail Pengajuan')
                ->icon('heroicon-o-document-text')
                ->columns(3)
                ->schema([
                    TextEntry::make('nomor_agenda')->label('Nomor Agenda')->placeholder('Belum diterbitkan'),
                    TextEntry::make('jenis_pengajuan')->label('Jenis'),
                    TextEntry::make('bagian.nama_bagian')->label('Bagian Tujuan')->icon('heroicon-o-building-office-2'),
                    TextEntry::make('tanggal_mulai')->label('Mulai')->date('d M Y')->icon('heroicon-o-calendar'),
                    TextEntry::make('tanggal_selesai')->label('Selesai')->date('d M Y')->icon('heroicon-o-calendar'),
                    TextEntry::make('judul_penelitian')
                        ->label('Topik / Judul')
                        ->placeholder('-')
                        ->visible(fn ($record) => filled($record->judul_penelitian)),
                    TextEntry::make('no_bpjs_ketenagakerjaan')
                        ->label('Nomor BPJS Ketenagakerjaan')
                        ->placeholder('-')
                        ->visible(fn ($record) => $record->jenis_pengajuan === 'PKL/Magang'),
                ]),

            Section::make('Data Tambahan')
                ->icon('heroicon-o-list-bullet')
                ->columns(3)
                ->visible(fn ($record) => \App\Models\FormFieldDefinition::whereIn('target', ['registrasi_peserta', 'pengajuan'])->exists())
                ->schema(function ($record) {
                    // Gabungkan definisi field dari registrasi peserta + pengajuan,
                    // lalu ambil nilainya dari data_tambahan masing-masing tabel.
                    $definisi = \App\Models\FormFieldDefinition::orderBy('urutan')->get();

                    return $definisi->map(function ($field) use ($record) {
                        $sumber = $field->target === 'registrasi_peserta'
                            ? ($record->peserta->data_tambahan ?? [])
                            : ($record->data_tambahan ?? []);

                        $nilai = $sumber[$field->key] ?? null;

                        return TextEntry::make("data_tambahan_display_{$field->id}")
                            ->label($field->label)
                            ->state(is_array($nilai) ? implode(', ', $nilai) : ($nilai !== null && $nilai !== '' ? (string) $nilai : '-'));
                    })->all();
                }),

            Section::make('Status Dokumen')
                ->icon('heroicon-o-folder')
                ->schema([
                    RepeatableEntry::make('dokumenPersyaratans')
                        ->label('')
                        ->columns(4)
                        ->schema([
                            TextEntry::make('jenis_dokumen')->label('Dokumen'),
                            TextEntry::make('status_verifikasi')->label('Status')->badge(),
                            TextEntry::make('catatan_verifikasi')->label('Catatan')->placeholder('-'),
                            TextEntry::make('file_path')
                                ->label('Berkas')
                                ->formatStateUsing(fn () => 'Lihat File')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->url(fn ($record) => route('documents.persyaratan', $record))
                                ->openUrlInNewTab()
                                ->color('primary'),
                        ]),
                ]),

            Section::make('Riwayat Disposisi/Approval')
                ->icon('heroicon-o-clipboard-document-check')
                ->visible(fn ($record) => $record->approvalWorkflows()->exists())
                ->schema([
                    RepeatableEntry::make('approvalWorkflows')
                        ->label('')
                        ->columns(5)
                        ->schema([
                            TextEntry::make('urutan')
                                ->label('Tahap')
                                ->formatStateUsing(fn (int $state) => \App\Services\PengajuanWorkflowService::URUTAN_APPROVAL[$state] ?? $state),
                            TextEntry::make('status')->label('Status')->badge(),
                            TextEntry::make('penandatangan.name')->label('Diproses Oleh')->placeholder('-'),
                            TextEntry::make('diproses_at')->label('Waktu')->dateTime('d M Y H:i')->placeholder('-'),
                            TextEntry::make('id')
                                ->label('Lembar Disposisi')
                                ->formatStateUsing(fn () => 'Cetak PDF')
                                ->icon('heroicon-o-printer')
                                ->color('primary')
                                ->visible(fn ($record) => $record->status === 'ditandatangani')
                                ->url(fn ($record) => route('disposisi.cetak', $record))
                                ->openUrlInNewTab(),
                        ]),
                ]),

            Section::make('Pembimbing Lapangan')
                ->icon('heroicon-o-user-group')
                ->columns(3)
                ->visible(fn ($record) => $record->penugasanPembimbing !== null)
                ->schema([
                    TextEntry::make('penugasanPembimbing.nama_tampil')->label('Nama Pembimbing'),
                    TextEntry::make('penugasanPembimbing.jabatan_pembimbing')->label('Jabatan')->placeholder('-'),
                    TextEntry::make('penugasanPembimbing.status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => $state === 'disetujui' ? 'Disetujui Kepala Bagian' : 'Diusulkan, Menunggu Persetujuan')
                        ->color(fn (?string $state) => $state === 'disetujui' ? 'success' : 'warning'),
                    TextEntry::make('penugasanPembimbing.diusulkanOleh.name')->label('Diusulkan Oleh (PIC)')->placeholder('-'),
                    TextEntry::make('penugasanPembimbing.diusulkan_at')->label('Diusulkan Pada')->dateTime('d M Y')->placeholder('-'),
                    TextEntry::make('penugasanPembimbing.ditetapkan_at')->label('Disetujui Pada')->dateTime('d M Y')->placeholder('-'),
                ]),
            Section::make('Surat Balasan')
                ->icon('heroicon-o-document-text')
                ->columns(3)
                ->visible(fn ($record) => $record->suratBalasan !== null)
                ->schema([
                    TextEntry::make('suratBalasan.nomor_surat')->label('Nomor Surat')->placeholder('-'),
                    TextEntry::make('suratBalasan.status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => $state === 'terbit' ? 'Terbit (Resmi)' : 'Draft')
                        ->color(fn (?string $state) => $state === 'terbit' ? 'success' : 'warning'),
                    TextEntry::make('suratBalasan.file_path')
                        ->label('Berkas')
                        ->formatStateUsing(fn (?string $state) => $state ? 'Lihat File' : null)
                        ->placeholder('-')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('primary')
                        ->url(fn ($record) => $record->suratBalasan?->file_path ? route('documents.surat-balasan', $record->suratBalasan) : null)
                        ->openUrlInNewTab(),
                ]),
            Section::make('Aspek Penilaian (Diusulkan Peserta)')
                ->icon('heroicon-o-list-bullet')
                ->visible(fn ($record) => filled($record->evaluasi?->aspek_penilaian_default) && $record->evaluasi?->dinilai_at === null)
                ->schema([
                    TextEntry::make('evaluasi.aspek_penilaian_default')
                        ->label('')
                        ->listWithLineBreaks()
                        ->bulleted(),
                ]),
            Section::make('Hasil Evaluasi')
                ->icon('heroicon-o-clipboard-document-check')
                ->visible(fn ($record) => $record->evaluasi?->nilai_akhir !== null)
                ->schema([
                    ViewEntry::make('evaluasi')
                        ->label('')
                        ->view('filament.infolists.hasil-evaluasi'),
                ]),
            Section::make('Timeline Pengajuan')
                ->icon('heroicon-o-clock')
                ->visible(fn ($record) => $record->riwayatStatus()->exists())
                ->schema([
                    ViewEntry::make('riwayatStatus')
                        ->label('')
                        ->view('filament.infolists.timeline-pengajuan'),
                ]),

            Section::make()
                ->visible(fn ($record) => in_array($record->status, ['disetujui', 'berjalan', 'selesai']))
                ->schema([
                    ViewEntry::make('status')
                        ->label('')
                        ->view('filament.infolists.kartu-sukses'),
                ]),
        ]);
    }
}