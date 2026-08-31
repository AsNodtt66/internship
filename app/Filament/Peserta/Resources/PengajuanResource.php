<?php

namespace App\Filament\Peserta\Resources;

use App\Filament\Peserta\Resources\PengajuanPenelitianResource;
use App\Filament\Peserta\Resources\PengajuanPklResource;
use App\Filament\Peserta\Resources\PengajuanResource\Pages;
use App\Models\Pengajuan;
use App\Support\Ui\PengajuanStatusPresenter;
use App\Support\Authorization\PengajuanAccess;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class PengajuanResource extends Resource
{
    protected static ?string $model = Pengajuan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pengajuan PKL / Magang';

    protected static ?string $pluralModelLabel = 'Pengajuan Saya';

    /**
     * Resource generik ini TETAP terdaftar (lihat PesertaPanelProvider)
     * supaya route view/edit/create-nya tetap ada -- dipakai oleh
     * PengajuanResource::getUrl() di Dashboard.php & PesertaQuickActions.php,
     * dan diwarisi oleh PengajuanPklResource & PengajuanPenelitianResource.
     * Tapi item menu sidebar-nya sendiri disembunyikan karena sudah
     * digantikan 2 menu terpisah: "Pengajuan PKL/Magang" & "Pengajuan
     * Penelitian" (dikembalikan setelah sempat di-unify di patch 14 --
     * user minta menu lama jangan dihapus).
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /**
     * Batasi SELURUH akses resource ini (list, view, edit) hanya ke pengajuan
     * milik peserta yang sedang login. Ini level proteksi paling dasar —
     * berlaku untuk semua halaman, bukan cuma tabel list.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        return $user ? PengajuanAccess::scope($query, $user) : $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('Jenis Pengajuan')
                    ->description('Pilih jalur pengajuan dan pahami ketentuannya.')
                    ->schema([
                        // Kalau diakses lewat menu "Pengajuan PKL/Magang" atau
                        // "Pengajuan Penelitian" (bukan resource generik),
                        // jenisnya sudah pasti ditentukan oleh menu yang
                        // dipilih peserta -- field ini dikunci (disabled) &
                        // di-default sesuai menu supaya tidak bisa diubah
                        // manual, sehingga aturan wajib/tidaknya Topik/Judul
                        // Penelitian di bawah selalu ikut menu yang benar.
                        // mutateFormDataBeforeCreate() di masing-masing
                        // resource tetap memaksa nilai final sebagai jaring
                        // pengaman kedua.
                        Radio::make('jenis_pengajuan')
                            ->label('Jenis Pengajuan')
                            ->options([
                                'PKL/Magang' => 'PKL/Magang',
                                'Penelitian' => 'Penelitian',
                            ])
                            ->default(fn () => match (static::class) {
                                PengajuanPklResource::class => 'PKL/Magang',
                                PengajuanPenelitianResource::class => 'Penelitian',
                                default => null,
                            })
                            ->disabled(fn () => static::class !== self::class)
                            ->dehydrated()
                            ->live()
                            ->required(),

                        Placeholder::make('ketentuan')
                            ->label('Ketentuan PKL/Magang')
                            ->content('Pengajuan diperiksa berdasarkan kelengkapan dokumen, kesesuaian kompetensi, kebutuhan unit kerja, kuota, dan kondisi operasional perusahaan. Urutan pengajuan menjadi salah satu pertimbangan, tetapi tidak menjamin penerimaan. Informasi tindak lanjut disampaikan melalui sistem dan kontak aktif yang Anda cantumkan.'),
                    ]),

                Step::make('Data Pribadi')
                    ->description('Isi identitas dan kontak aktif yang dapat diverifikasi.')
                    ->schema([
                        TextInput::make('nama_lengkap')->label('Nama Lengkap')->autocomplete('name')->required()->maxLength(255),
                        Radio::make('jenis_kelamin')
                            ->options(['Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan'])
                            ->required(),
                        TextInput::make('tempat_lahir')->label('Tempat Lahir')->required()->maxLength(100),
                        DatePicker::make('tanggal_lahir')->label('Tanggal Lahir')->required()->before('today'),
                        TextInput::make('nik')->label('NIK')->required()->length(16)->rule('regex:/^[0-9]{16}$/')->extraInputAttributes(['inputmode' => 'numeric']),
                        TextInput::make('no_hp')->label('Nomor HP / WhatsApp Aktif')->helperText('Gunakan nomor yang dapat dihubungi selama proses pengajuan.')->required()->tel()->autocomplete('tel'),
                        TextInput::make('email_aktif')->label('Email Aktif')->helperText('Gunakan email yang rutin Anda periksa.')->required()->email()->autocomplete('email'),
                    ])->columns(2),

                Step::make('Data Akademik')
                    ->description('Lengkapi informasi sekolah atau perguruan tinggi.')
                    ->schema([
                        TextInput::make('nama_institusi')->label('Perguruan Tinggi atau Sekolah')->autocomplete('organization')->required(),
                        TextInput::make('fakultas')->label('Fakultas')->nullable(),
                        TextInput::make('program_studi')->label('Program Studi atau Jurusan')->required(),
                        Select::make('jenjang_pendidikan')
                            ->options([
                                'SMK' => 'SMK', 'D3' => 'D3', 'D4' => 'D4',
                                'S1' => 'S1', 'S2' => 'S2', 'S3' => 'S3',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('semester', null)),
                        Select::make('semester')
                            ->options(function (Get $get) {
                                $jumlahSemester = match ($get('jenjang_pendidikan')) {
                                    'D3' => 6,
                                    'S2' => 4,
                                    'S3' => 6,
                                    default => 8, // SMK, D4, S1, dan default
                                };

                                return array_combine(
                                    range(1, $jumlahSemester),
                                    array_map(fn ($i) => "Semester $i", range(1, $jumlahSemester))
                                );
                            })
                            ->disabled(fn (Get $get) => $get('jenjang_pendidikan') === 'SMK')
                            ->nullable(),
                        TextInput::make('nim_nisn')->label('NIM atau NISN')->helperText('Masukkan nomor induk yang digunakan oleh institusi pendidikan Anda.')->required(),
                        TextInput::make('ipk_nilai')
                            ->label(fn (Get $get) => $get('jenjang_pendidikan') === 'SMK' ? 'Nilai Terakhir' : 'IPK Terakhir')
                            ->helperText(fn (Get $get) => $get('jenjang_pendidikan') === 'SMK' ? 'Gunakan skala 0–100.' : 'Gunakan skala 0–4.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn (Get $get) => $get('jenjang_pendidikan') === 'SMK' ? 100 : 4)
                            ->required(),
                    ])->columns(2),

                Step::make('Rencana Kegiatan')
                    ->description('Tentukan periode, bagian tujuan, dan rencana kegiatan.')
                    ->schema([
                        DatePicker::make('tanggal_mulai')->label('Tanggal Mulai')->required()->afterOrEqual('today')->live(),
                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->after('tanggal_mulai')
                            ->live()
                            // Aturan bisnis: masa PKL/Magang/Penelitian maksimal
                            // sesuai PengajuanWorkflowService::MASA_PKL_MAKSIMAL_BULAN
                            // (3 bulan). Kalau ingin periode lebih panjang,
                            // peserta wajib mengajukan pengajuan baru untuk
                            // perpanjangan (lihat catatan di step Pernyataan &
                            // alur ajukanPermohonanPerpanjangan() di service).
                            ->maxDate(fn (Get $get) => filled($get('tanggal_mulai'))
                                ? \Carbon\Carbon::parse($get('tanggal_mulai'))->addMonths(\App\Services\PengajuanWorkflowService::MASA_PKL_MAKSIMAL_BULAN)
                                : null)
                            ->helperText('Maksimal '.\App\Services\PengajuanWorkflowService::MASA_PKL_MAKSIMAL_BULAN.' bulan sejak tanggal mulai. Jika kegiatan perlu diperpanjang, gunakan alur perpanjangan setelah periode berjalan.'),
                        Placeholder::make('durasi')
                            ->content(function (Get $get) {
                                if (! $get('tanggal_mulai') || ! $get('tanggal_selesai')) {
                                    return '-';
                                }
                                $mulai = \Carbon\Carbon::parse($get('tanggal_mulai'));
                                $selesai = \Carbon\Carbon::parse($get('tanggal_selesai'));

                                return $mulai->diffInDays($selesai) . ' hari';
                            }),
                        Select::make('bagian_tujuan_id')
                            ->label('Bagian Tujuan')
                            ->relationship('bagian', 'nama_bagian')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('judul_penelitian')
                            ->label('Topik atau Judul Kegiatan')
                            ->helperText('Wajib untuk penelitian. Untuk PKL/magang, isi jika sudah memiliki topik khusus.')
                            ->requiredIf('jenis_pengajuan', 'Penelitian'),
                        Placeholder::make('info_helm_safety')
                            ->label('Informasi Penempatan Pabrik')
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('jenis_pengajuan') === 'PKL/Magang')
                            ->content('Jika ditempatkan di area pabrik, peserta wajib membawa dan mengenakan helm keselamatan berwarna hijau selama kegiatan.'),
                        Textarea::make('tujuan')->label('Tujuan Kegiatan')->helperText('Jelaskan hasil atau pengalaman yang ingin dicapai selama kegiatan.')->rows(4)->required()->columnSpanFull(),
                    ])->columns(2),

                Step::make('Pembimbing Akademik')
                    ->description('Cantumkan dosen atau guru yang membimbing dari institusi Anda.')
                    ->schema([
                        TextInput::make('nama_pembimbing_akademik')->label('Nama Dosen atau Guru Pembimbing')->required(),
                        TextInput::make('no_hp_pembimbing_akademik')->label('Nomor HP Pembimbing (Opsional)')->nullable()->tel(),
                        TextInput::make('email_pembimbing_akademik')->label('Email Pembimbing')->required()->email(),
                    ]),

                Step::make('Dokumen Persyaratan')
                    ->description('Unggah dokumen yang diminta sesuai jenis pengajuan.')
                    ->schema([
                        FileUpload::make('file_surat_pengantar')->label('Surat Pengantar Resmi')->helperText('Format PDF, maksimal 10 MB.')->disk(config('filesystems.private_documents_disk', 'documents'))->visibility('private')->acceptedFileTypes(['application/pdf'])->maxSize(10240)->required(),
                        FileUpload::make('file_cv')->label('Curriculum Vitae (CV)')->helperText('Format PDF, maksimal 10 MB.')->disk(config('filesystems.private_documents_disk', 'documents'))->visibility('private')->acceptedFileTypes(['application/pdf'])->maxSize(10240)->required(),
                        FileUpload::make('file_proposal')->label('Proposal Kegiatan')->helperText('Format PDF, maksimal 10 MB.')->disk(config('filesystems.private_documents_disk', 'documents'))->visibility('private')->acceptedFileTypes(['application/pdf'])->maxSize(10240)->required(),
                        FileUpload::make('file_ktp_ktm')->label('KTP atau KTM')->helperText('Format PDF, maksimal 10 MB.')->disk(config('filesystems.private_documents_disk', 'documents'))->visibility('private')->acceptedFileTypes(['application/pdf'])->maxSize(10240)->required(),
                        FileUpload::make('file_transkrip')->label('Transkrip Nilai Terbaru')->helperText('Format PDF, maksimal 10 MB.')->disk(config('filesystems.private_documents_disk', 'documents'))->visibility('private')->acceptedFileTypes(['application/pdf'])->maxSize(10240)->required(),
                        FileUpload::make('file_pas_foto')
                            ->label('Pas Foto 3×4')
                            ->helperText('Format foto JPG atau PNG, maksimal 5 MB.')
                            ->disk(config('filesystems.private_documents_disk', 'documents'))
                            ->visibility('private')
                            ->image()
                            ->imageEditor()
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->required(),

                        // Khusus menu "Pengajuan Penelitian" -- data yang
                        // dibutuhkan peserta untuk diteliti, supaya PIC bisa
                        // mengecek kesiapan/kesesuaian data sebelum disetujui.
                        FileUpload::make('file_data_penelitian')
                            ->label('Data yang Dibutuhkan untuk Diteliti')
                            ->helperText('Khusus Penelitian. Format PDF, Excel (XLSX), atau CSV, maksimal 10 MB.')
                            ->disk(config('filesystems.private_documents_disk', 'documents'))
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                            ])
                            ->maxSize(10240)
                            ->visible(fn (Get $get) => $get('jenis_pengajuan') === 'Penelitian')
                            ->requiredIf('jenis_pengajuan', 'Penelitian'),

                        // Khusus menu "Pengajuan PKL/Magang" -- kepemilikan
                        // BPJS Ketenagakerjaan. Kalau tidak punya, boleh
                        // di-skip (nomor & foto tidak wajib).
                        Checkbox::make('punya_bpjs_ketenagakerjaan')
                            ->label('Saya memiliki BPJS Ketenagakerjaan')
                            ->helperText('Kosongkan jika Anda belum memiliki BPJS Ketenagakerjaan.')
                            ->live()
                            ->visible(fn (Get $get) => $get('jenis_pengajuan') === 'PKL/Magang')
                            ->columnSpanFull(),
                        TextInput::make('no_bpjs_ketenagakerjaan')
                            ->label('Nomor BPJS Ketenagakerjaan')
                            ->visible(fn (Get $get) => $get('jenis_pengajuan') === 'PKL/Magang' && $get('punya_bpjs_ketenagakerjaan'))
                            ->requiredIf('punya_bpjs_ketenagakerjaan', true),
                        FileUpload::make('file_bpjs_ketenagakerjaan')
                            ->label('Salinan Kartu BPJS Ketenagakerjaan')
                            ->helperText('Format PDF, JPG, atau PNG, maksimal 5 MB. Digunakan PIC untuk mencocokkan dengan nomor yang diisi.')
                            ->disk(config('filesystems.private_documents_disk', 'documents'))
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->visible(fn (Get $get) => $get('jenis_pengajuan') === 'PKL/Magang' && $get('punya_bpjs_ketenagakerjaan'))
                            ->requiredIf('punya_bpjs_ketenagakerjaan', true),

                        // Dokumen pelengkap KHUSUS pengajuan hasil PERPANJANGAN
                        // (baris ini punya pengajuan_asal_id, dibuat otomatis
                        // saat PIC menyetujui permohonan perpanjangan -- lihat
                        // PengajuanWorkflowService::buatPengajuanPerpanjanganBaru()).
                        // Peserta pengajuan BARU (bukan perpanjangan) tidak
                        // pernah melihat 2 field ini.
                        Textarea::make('alasan_perpanjangan')
                            ->label('Alasan Perpanjangan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (?Pengajuan $record) => $record?->pengajuan_asal_id !== null)
                            ->required(fn (?Pengajuan $record) => $record?->pengajuan_asal_id !== null),
                        FileUpload::make('file_surat_kampus_perpanjangan')
                            ->label('Surat Pengantar Perpanjangan dari Kampus')
                            ->helperText('Format PDF, maksimal 10 MB.')
                            ->disk(config('filesystems.private_documents_disk', 'documents'))
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->visible(fn (?Pengajuan $record) => $record?->pengajuan_asal_id !== null)
                            ->required(fn (?Pengajuan $record) => $record?->pengajuan_asal_id !== null),
                    ])->columns(2),

                Step::make('Informasi Tambahan')
                    ->description('Tambahkan keahlian, motivasi, dan informasi pendukung.')
                    ->schema([
                        Textarea::make('keahlian_skill')->label('Keahlian yang Relevan')->helperText('Tuliskan keterampilan yang berkaitan dengan bidang tujuan.')->rows(3)->nullable(),
                        Textarea::make('motivasi')->label('Motivasi')->helperText('Jelaskan alasan memilih PT Rajawali I Unit PG Krebet Baru dan kaitannya dengan tujuan akademik Anda.')->rows(4)->required(),
                        TextInput::make('sumber_informasi')->label('Sumber Informasi')->placeholder('Contoh: kampus, sekolah, website, atau rekomendasi')->nullable(),
                        TextInput::make('rekomendasi_dari')->label('Direkomendasikan Oleh (Opsional)')->nullable(),
                        ...app(\App\Services\DynamicFormFieldBuilder::class)->buildFor('pengajuan'),
                    ]),

                Step::make('Pernyataan')
                    ->description('Periksa kembali data sebelum mengirim pengajuan.')
                    ->schema([
                        Checkbox::make('setuju_data_benar')
                            ->label('Saya menyatakan bahwa seluruh data yang saya berikan adalah benar dan dapat dipertanggungjawabkan.')
                            ->accepted()
                            ->required(),
                        Checkbox::make('setuju_patuh_aturan')
                            ->label('Saya bersedia mematuhi peraturan perusahaan selama mengikuti kegiatan PKL, magang, atau penelitian.')
                            ->accepted()
                            ->required(),
                    ]),
            ])
                ->persistStepInQueryString('tahap')
                ->previousAction(fn (Action $action) => $action->label('Kembali'))
                ->nextAction(fn (Action $action) => $action->label('Lanjut'))
                ->submitAction(new \Illuminate\Support\HtmlString(
                    \Illuminate\Support\Facades\Blade::render(<<<'BLADE'
                        <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                            Kirim Pengajuan
                        </x-filament::button>
                    BLADE)
                ))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereHas('peserta', fn ($q) => $q->where('user_id', Auth::id()))
                ->with(['pengajuanAsal', 'pengajuanPerpanjangan']))
            ->columns([
                TextColumn::make('nomor_agenda')
                    ->label('No. Agenda')
                    ->placeholder('Belum diterbitkan')
                    ->icon('heroicon-o-hashtag')
                    ->searchable(),
                TextColumn::make('jenis_pengajuan')->badge(),
                TextColumn::make('bagian.nama_bagian')->label('Bagian Tujuan')->placeholder('—')->searchable(),
                TextColumn::make('tanggal_mulai')->date('d M Y')->sortable(),
                TextColumn::make('tanggal_selesai')->date('d M Y')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::labelStatus($state))
                    ->color(fn (string $state): string => PengajuanStatusPresenter::color($state)),
                // Penanda visual supaya peserta langsung sadar kalau baris
                // ini bagian dari rantai perpanjangan (bukan pengajuan
                // baru yang berdiri sendiri) -- lihat detail lengkapnya di
                // Section "Riwayat Perpanjangan" pada halaman Lihat Detail.
                TextColumn::make('keterangan_perpanjangan')
                    ->label('Keterangan')
                    ->state(function ($record) {
                        if ($record->pengajuanPerpanjangan?->status === 'draft') {
                            return 'Perlu dilengkapi (perpanjangan)';
                        }

                        if ($record->pengajuanPerpanjangan) {
                            return 'Sudah diperpanjang';
                        }

                        if ($record->pengajuan_asal_id !== null) {
                            return 'Perpanjangan dari periode sebelumnya';
                        }

                        return null;
                    })
                    ->badge()
                    ->color(fn ($record) => $record->pengajuanPerpanjangan?->status === 'draft' ? 'warning' : 'gray')
                    ->placeholder('—'),
                TextColumn::make('created_at')->label('Diajukan')->dateTime('d M Y H:i')->sortable(),
            ])
            // Filter status & jenis supaya peserta yang punya banyak
            // riwayat pengajuan (mis. bekas perpanjangan berkali-kali)
            // bisa cepat mempersempit tampilan tanpa scroll manual.
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'diajukan' => 'Menunggu Verifikasi PIC',
                    'verifikasi_dokumen' => 'Verifikasi Dokumen',
                    'dokumen_ditolak' => 'Dokumen Perlu Revisi',
                    'proses_approval' => 'Proses Persetujuan',
                    'menunggu_catatan_pembimbing' => 'Menunggu Catatan Pembimbing',
                    'menunggu_penetapan_pembimbing' => 'Menunggu Penetapan Pembimbing',
                    'ditolak' => 'Ditolak',
                    'berjalan' => 'Sedang Berjalan',
                    'selesai' => 'Selesai',
                    'perlu_perpanjangan' => 'Perlu Tindak Lanjut Perpanjangan',
                ]),
                SelectFilter::make('jenis_pengajuan')
                    ->label('Jenis Pengajuan')
                    ->options([
                        'PKL/Magang' => 'PKL/Magang',
                        'Penelitian' => 'Penelitian',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat Detail'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Label status versi peserta -- dipakai kolom status di tabel supaya
     * konsisten dengan istilah yang sudah dikenal peserta di Dashboard
     * (lihat Dashboard::labelStatus(), duplikasi kecil ini sengaja
     * dibiarkan karena Dashboard bukan bagian dari Resource ini).
     */
    protected static function labelStatus(string $status): string
    {
        return PengajuanStatusPresenter::label($status);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuans::route('/'),
            'create' => Pages\CreatePengajuan::route('/create'),
            'view' => Pages\ViewPengajuan::route('/{record}'),
            'edit' => Pages\EditPengajuan::route('/{record}/edit'),
        ];
    }
}