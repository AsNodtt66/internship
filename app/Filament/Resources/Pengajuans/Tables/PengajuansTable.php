<?php

namespace App\Filament\Resources\Pengajuans\Tables;

use App\Enums\RoleSlug;
use App\Models\ApprovalWorkflow;
use App\Models\Bagian;
use App\Models\Evaluasi;
use App\Models\PembimbingLapangan;
use App\Models\Pengajuan;
use App\Models\Role;
use App\Models\User;
use App\Services\PengajuanWorkflowService;
use App\Support\Ui\PengajuanStatusPresenter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PengajuansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_agenda')
                    ->label('No. Agenda')
                    ->placeholder('Belum diterbitkan')
                    ->icon('heroicon-o-hashtag')
                    ->weight('semibold')
                    ->copyable()
                    ->copyMessage('Nomor agenda disalin')
                    ->description(fn (Pengajuan $record) => $record->pengajuan_asal_id
                        ? '↳ Perpanjangan dari #'.($record->pengajuanAsal->nomor_agenda ?? $record->pengajuan_asal_id)
                        : ($record->pengajuanPerpanjangan ? '⤷ Sudah diperpanjang' : null))
                    ->searchable(),

                TextColumn::make('peserta.user.name')
                    ->label('Peserta')
                    ->weight('medium')
                    ->description(fn (Pengajuan $record) => $record->peserta?->universitas)
                    ->searchable(),

                TextColumn::make('jenis_pengajuan')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state) => str_contains($state, 'PKL') ? 'info' : 'purple'),

                TextColumn::make('bagian.nama_bagian')
                    ->label('Bagian Tujuan')
                    ->icon('heroicon-o-building-office-2')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::labelStatus($state))
                    ->color(fn (string $state) => self::warnaStatus($state)),

                TextColumn::make('tanggal_selesai')
                    ->label('Target Selesai')
                    ->visible(fn () => Auth::user()?->hasRole(RoleSlug::PIC))
                    ->date('d M Y')
                    ->description(fn (Pengajuan $record) => $record->status === 'berjalan'
                        ? ($record->tanggal_selesai->isPast()
                            ? 'Sudah lewat '.$record->tanggal_selesai->diffInDays(now()).' hari — segera selesaikan'
                            : ($record->tanggal_selesai->diffInDays(now()) <= 7
                                ? 'Sisa '.$record->tanggal_selesai->diffInDays(now()).' hari lagi'
                                : null))
                        : null)
                    ->color(fn (Pengajuan $record) => $record->status === 'berjalan'
                        ? ($record->tanggal_selesai->isPast() ? 'danger' : ($record->tanggal_selesai->diffInDays(now()) <= 7 ? 'warning' : null))
                        : null)
                    ->weight(fn (Pengajuan $record) => $record->status === 'berjalan' && $record->tanggal_selesai->isPast() ? 'bold' : null)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y')
                    ->description(fn (Pengajuan $record) => $record->created_at?->diffForHumans())
                    ->sortable(),
            ])
            ->striped()
            ->filters([
                Filter::make('perlu_diselesaikan')
                    ->label('Perlu Diselesaikan (Target Selesai Terlewat)')
                    ->query(fn ($query) => $query->where('status', 'berjalan')->whereDate('tanggal_selesai', '<=', now()))
                    ->toggle(),

                SelectFilter::make('status')->options([
                    'diajukan' => 'Diajukan',
                    'verifikasi_dokumen' => 'Verifikasi Dokumen',
                    'dokumen_ditolak' => 'Dokumen Perlu Revisi',
                    'proses_approval' => 'Proses Persetujuan',
                    'disetujui' => 'Disetujui',
                    'menunggu_catatan_pembimbing' => 'Menunggu Catatan Pembimbing',
                    'menunggu_penetapan_pembimbing' => 'Menunggu Penetapan Pembimbing',
                    'ditolak' => 'Ditolak',
                    'berjalan' => 'Berjalan',
                    'selesai' => 'Selesai',
                    'perlu_perpanjangan' => 'Perlu Tindak Lanjut Perpanjangan',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                self::verifikasiDokumenAction(),
                self::ubahBagianTujuanAction(),
                self::rekapDanMulaiApprovalAction(),
                self::prosesApprovalAction(),
                self::batalkanPengajuanDalamProsesAction(),
                self::usulkanPembimbingAction(),
                self::uploadPenilaianAction(),
                self::jadwalkanEvaluasiAction(),
                self::checklistPersiapanEvaluasiAction(),
                self::inputPenilaianPembimbingAction(),
                self::inputPenilaianPicAction(),
                self::putuskanPerpanjanganAction(),
                self::selesaikanEvaluasiPerpanjanganAction(),
                self::selesaikanTanpaPerpanjanganAction(),
                self::tolakPengajuanBerjalanAction(),
                self::cetakSuratKeteranganSelesaiAction(),
                self::cetakSuratPerpanjanganAction(),
                self::toggleAkunPesertaAction(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * PIC: verifikasi tiap dokumen persyaratan (lengkap / perlu revisi).
     */
    protected static function verifikasiDokumenAction(): Action
    {
        return Action::make('verifikasiDokumen')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Verifikasi Dokumen')
            ->icon('heroicon-o-document-magnifying-glass')
            ->color('gray')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && in_array($record->status, ['diajukan', 'dokumen_ditolak']))
            ->fillForm(fn (Pengajuan $record) => [
                'dokumen' => $record->dokumenPersyaratans->map(fn ($doc) => [
                    'id' => $doc->id,
                    'jenis_dokumen' => $doc->jenis_dokumen,
                    'status_verifikasi' => $doc->status_verifikasi,
                    'catatan_verifikasi' => $doc->catatan_verifikasi,
                    'file_path' => $doc->file_path,
                ])->toArray(),
            ])
            ->schema([
                Repeater::make('dokumen')
                    ->label('')
                    ->schema([
                        Hidden::make('id'),
                        Hidden::make('file_path'),
                        TextInput::make('jenis_dokumen')
                            ->label('Dokumen')
                            ->disabled()
                            ->suffixAction(
                                Action::make('lihatFile')
                                    ->icon('heroicon-o-eye')
                                    ->tooltip('Lihat file yang diunggah peserta')
                                    ->url(fn (Get $get) => $get('id') ? route('documents.persyaratan', (int) $get('id')) : null)
                                    ->openUrlInNewTab()
                            ),
                        Select::make('status_verifikasi')
                            ->label('Status')
                            ->options([
                                'lengkap' => 'Lengkap',
                                'tidak_lengkap' => 'Perlu Revisi',
                            ])
                            ->required(),
                        Textarea::make('catatan_verifikasi')
                            ->label('Catatan (wajib jika Perlu Revisi)')
                            ->rows(1),
                    ])
                    ->columns(3)
                    ->deletable(false)
                    ->addable(false)
                    ->reorderable(false),
            ])
            ->action(function (Pengajuan $record, array $data) {
                $service = app(PengajuanWorkflowService::class);

                foreach ($data['dokumen'] as $row) {
                    $dokumen = $record->dokumenPersyaratans()->find($row['id']);

                    if ($dokumen) {
                        $service->verifikasiDokumen(
                            $dokumen,
                            $row['status_verifikasi'],
                            Auth::user(),
                            $row['catatan_verifikasi'] ?? null
                        );
                    }
                }

                Notification::make()
                    ->title('Verifikasi dokumen disimpan')
                    ->success()
                    ->send();
            });
    }

    /**
     * PIC: ubah Bagian Tujuan kalau ternyata jurusan/latar belakang peserta
     * tidak cocok dengan bagian yang dipilihnya sendiri saat mengajukan
     * (mis. mengajukan ke SDM dan Umum tapi lebih cocok ke Pabrikasi).
     * Hanya tersedia SEBELUM disposisi berjenjang dimulai.
     */
    protected static function ubahBagianTujuanAction(): Action
    {
        return Action::make('ubahBagianTujuan')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Ubah Bagian Tujuan')
            ->icon('heroicon-o-arrows-right-left')
            ->color('gray')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && in_array($record->status, ['diajukan', 'verifikasi_dokumen', 'dokumen_ditolak'], true))
            ->modalDescription(fn (Pengajuan $record) => 'Bagian Tujuan saat ini: '.($record->bagianTujuan->nama_bagian ?? '-').'. Gunakan ini kalau jurusan/latar belakang peserta ternyata lebih cocok ke bagian lain.')
            ->fillForm(fn (Pengajuan $record) => [
                'bagian_tujuan_id' => $record->bagian_tujuan_id,
            ])
            ->schema([
                Select::make('bagian_tujuan_id')
                    ->label('Bagian Tujuan Baru')
                    ->options(fn () => Bagian::orderBy('nama_bagian')->pluck('nama_bagian', 'id'))
                    ->searchable()
                    ->required(),
                Textarea::make('alasan')
                    ->label('Alasan (opsional)')
                    ->placeholder('Contoh: Jurusan peserta lebih cocok ditempatkan di Pabrikasi.')
                    ->rows(2),
            ])
            ->action(function (Pengajuan $record, array $data) {
                app(PengajuanWorkflowService::class)->ubahBagianTujuan(
                    $record,
                    (int) $data['bagian_tujuan_id'],
                    Auth::user(),
                    $data['alasan'] ?? null,
                );

                Notification::make()
                    ->title('Bagian Tujuan berhasil diubah')
                    ->success()
                    ->send();
            });
    }

    /**
     * PIC: setelah dokumen lengkap, rekap nomor agenda & mulai disposisi berjenjang.
     */
    protected static function rekapDanMulaiApprovalAction(): Action
    {
        return Action::make('rekapDanMulaiApproval')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Rekap & Mulai Persetujuan')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('primary')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->status === 'verifikasi_dokumen')
            ->schema([
                TextInput::make('nomor_agenda')
                    ->label('Nomor Agenda')
                    ->required(),
            ])
            ->action(function (Pengajuan $record, array $data) {
                app(PengajuanWorkflowService::class)->rekapDanMulaiApproval($record, $data['nomor_agenda']);

                Notification::make()
                    ->title('Disposisi dimulai')
                    ->body('Pengajuan diteruskan ke GM.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Staff SDM / Kabag SDM / GM: proses tahap disposisi miliknya, HANYA jika
     * sedang giliran dia (urutan sesuai role & status 'menunggu').
     */
    protected static function prosesApprovalAction(): Action
    {
        return Action::make('prosesApproval')
            ->authorize(fn (Pengajuan $record) => self::langkahApprovalMilikSaya($record) !== null)
            ->label('Tandatangani')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Tandatangani Surat Pengajuan')
            ->modalDescription(function (Pengajuan $record) {
                $step = self::langkahApprovalMilikSaya($record);

                return $step && $step->urutan === 4
                    ? 'Ini tahap TERAKHIR -- sekalian tuliskan catatan calon Pembimbing Lapangan untuk PIC.'
                    : 'Pengajuan akan diteruskan ke tahap disposisi berikutnya.';
            })
            ->visible(function (Pengajuan $record) {
                $step = self::langkahApprovalMilikSaya($record);

                return $step !== null;
            })
            ->schema(function (Pengajuan $record) {
                $step = self::langkahApprovalMilikSaya($record);

                $fields = [
                    Textarea::make('catatan')
                        ->label('Catatan (opsional)'),
                ];

                // Urutan 4 = Kepala Bagian Tujuan, tahap terakhir: wajib
                // sekalian isi catatan calon Pembimbing Lapangan untuk PIC.
                if ($step && $step->urutan === 4) {
                    $fields[] = Textarea::make('catatan_calon_pembimbing')
                        ->label('Catatan Calon Pembimbing Lapangan (Wajib)')
                        ->placeholder('Contoh: Pak Budi (Kepala Regu Maintenance), sudah pernah bimbing 2 kali sebelumnya.')
                        ->rows(3)
                        ->required();
                }

                return $fields;
            })
            ->action(function (Pengajuan $record, array $data) {
                $step = self::langkahApprovalMilikSaya($record);

                if (! $step) {
                    Notification::make()->title('Belum Giliran Anda')->body('Tahapan disposisi ini belum menjadi giliran Anda untuk diproses.')->danger()->send();

                    return;
                }

                app(PengajuanWorkflowService::class)->tandatanganiLangkah(
                    $step,
                    Auth::user(),
                    $data['catatan'] ?? null,
                    $data['catatan_calon_pembimbing'] ?? null,
                );

                Notification::make()
                    ->title('Disposisi ditandatangani')
                    ->success()
                    ->send();
            });
    }

    /**
     * PIC PKL/Penelitian: setelah Kepala Bagian Tujuan menandatangani tahap
     * disposisi terakhir sekaligus menuliskan catatan calon Pembimbing
     * Lapangan (lihat PengajuanWorkflowService::tandatanganiLangkah()),
     * PIC MENETAPKAN Pembimbing Lapangan dari dropdown data master
     * berdasarkan catatan itu, sekaligus menerbitkan Surat Balasan RESMI.
     * Ini titik akhir -- begitu ditetapkan, pengajuan LANGSUNG "berjalan",
     * TIDAK ADA lagi persetujuan terpisah dari Kepala Bagian sesudah ini.
     *
     * Pembimbing Lapangan TIDAK WAJIB punya akun User: cukup pilih/daftarkan
     * dari dropdown data master. Kalau memang sudah punya akun (opsional),
     * tetap konsisten karena diambil dari tabel PembimbingLapangan yang sama.
     *
     * NB: TIDAK ADA aksi "Tolak" di tahap disposisi manapun (GM/Kabag SDM/
     * Staff SDM/Kepala Bagian Tujuan) sesuai keputusan bisnis final --
     * keempatnya cuma menandatangani (lihat prosesApprovalAction() di atas).
     */
    protected static function usulkanPembimbingAction(): Action
    {
        return Action::make('usulkanPembimbing')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Tetapkan Pembimbing & Terbitkan Surat Balasan')
            ->icon('heroicon-o-user-plus')
            ->color('warning')
            ->modalDescription(fn (Pengajuan $record) => $record->catatan_pembimbing
                ? 'Catatan dari '.($record->catatanPembimbingOleh->name ?? 'Kepala Bagian').': "'.$record->catatan_pembimbing.'"'
                : ($record->pengajuanAsal?->penugasanPembimbing
                    ? 'Ini perpanjangan -- Pembimbing Lapangan sudah otomatis disamakan dengan periode sebelumnya ('.$record->pengajuanAsal->penugasanPembimbing->nama_tampil.'). Ubah kalau memang perlu ganti.'
                    : null))
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->status === 'menunggu_penetapan_pembimbing')
            ->fillForm(fn (Pengajuan $record) => [
                'pembimbing_lapangan_id' => $record->pengajuanAsal?->penugasanPembimbing?->pembimbing_lapangan_id,
            ])
            ->schema(fn (Pengajuan $record) => [
                Select::make('pembimbing_lapangan_id')
                    ->label('Pembimbing Lapangan')
                    ->helperText('Belum ada namanya di daftar? Klik "+" untuk daftarkan baru.')
                    ->options(fn () => self::opsiPembimbingLapangan($record))
                    ->searchable()
                    ->required()
                    ->createOptionForm(self::formPembimbingLapanganBaru($record))
                    ->createOptionUsing(fn (array $data) => self::buatPembimbingLapanganBaru($data)),
                TextInput::make('nomor_surat')
                    ->label('Nomor Surat Balasan')
                    ->required(),
                FileUpload::make('file_surat')
                    ->label('File Surat Balasan (PDF)')
                    ->disk(config('filesystems.private_documents_disk', 'documents'))
                    ->visibility('private')
                    ->directory('surat-balasan')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240)
                    ->required(),
            ])
            ->action(function (Pengajuan $record, array $data) {
                try {
                    app(PengajuanWorkflowService::class)->usulkanPembimbing(
                        $record,
                        $data['nomor_surat'],
                        $data['file_surat'],
                        Auth::user(),
                        (int) $data['pembimbing_lapangan_id'],
                    );

                    Notification::make()
                        ->title('Pembimbing ditetapkan')
                        ->body('Surat Balasan resmi terbit, PKL/Penelitian berjalan.')
                        ->success()
                        ->send();
                } catch (\RuntimeException $e) {
                    Notification::make()->title('Gagal Diproses')->body($e->getMessage())->danger()->send();
                }
            });
    }

    /**
     * Opsi dropdown Pembimbing Lapangan: diutamakan yang satu bagian dengan
     * bagian tujuan pengajuan ini, tapi pembimbing dari bagian lain (atau
     * yang belum diset bagiannya) tetap ditampilkan di bawahnya -- supaya
     * PIC tidak "kehilangan" nama yang sebenarnya ingin dipakai lintas
     * bagian. Nonaktif (is_active = false) tidak dimunculkan.
     */
    protected static function opsiPembimbingLapangan(Pengajuan $record): array
    {
        return PembimbingLapangan::where('is_active', true)
            ->orderByRaw('bagian_id = ? DESC', [$record->bagian_tujuan_id])
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (PembimbingLapangan $p) => [
                $p->id => $p->nama.($p->jabatan ? " ({$p->jabatan})" : '').($p->user_id ? ' 🔑' : ''),
            ])
            ->all();
    }

    /**
     * Form mini "Daftarkan Pembimbing Lapangan Baru" yang muncul saat PIC
     * klik "+" di dropdown (karena namanya belum ada). Bagian otomatis
     * kekunci sesuai bagian tujuan pengajuan ini supaya rapi dikelompokkan.
     *
     * Akun login SEPENUHNYA OPSIONAL (toggle "Buatkan Akun Login" default
     * mati) -- kalau dinyalakan baru muncul field email & password.
     *
     * @return array<int, mixed>
     */
    protected static function formPembimbingLapanganBaru(Pengajuan $record): array
    {
        return [
            Hidden::make('bagian_id')->default($record->bagian_tujuan_id),

            TextInput::make('nama')
                ->label('Nama Lengkap')
                ->required(),

            TextInput::make('jabatan')
                ->label('Jabatan (opsional)'),

            TextInput::make('no_hp')
                ->label('No. HP (opsional)')
                ->tel(),

            Toggle::make('buatkan_akun')
                ->label('Buatkan Akun Login (opsional)')
                ->helperText('Kalau dimatikan, pembimbing ini tetap bisa dipakai normal tanpa akun login sama sekali.')
                ->live()
                ->default(false),

            TextInput::make('nip')
                ->label('NIP')
                ->required()
                ->rule('digits:6')
                ->unique(table: 'users', column: 'nip', ignoreRecord: false)
                ->helperText('6 digit angka. Pembimbing Lapangan login pakai NIP, bukan email.')
                ->visible(fn (Get $get) => $get('buatkan_akun')),

            TextInput::make('password')
                ->label('Password Awal')
                ->password()
                ->revealable()
                ->default(fn () => Str::password(10, symbols: false))
                ->helperText('Sampaikan password ini ke pembimbing yang bersangkutan untuk login pertama kali.')
                ->visible(fn (Get $get) => $get('buatkan_akun')),
        ];
    }

    /**
     * Simpan data master Pembimbing Lapangan baru, dan kalau toggle
     * "Buatkan Akun" dinyalakan, sekalian buatkan akun User dengan role
     * pembimbing_lapangan lalu dihubungkan lewat user_id.
     */
    protected static function buatPembimbingLapanganBaru(array $data): int
    {
        $userId = null;

        if (! empty($data['buatkan_akun'])) {
            $role = Role::where('slug', 'pembimbing_lapangan')->first();

            $user = User::create([
                'name' => $data['nama'],
                // Kolom email tetap wajib & unique di tabel users, tapi
                // khusus akun Pembimbing Lapangan ini cuma placeholder --
                // login sebenarnya pakai NIP (lihat App\Filament\Pages\Auth\Login).
                'email' => $data['nip'].'@nip.internal',
                'nip' => $data['nip'],
                'password' => $data['password'] ?: Str::password(10, symbols: false),
                'role_id' => $role?->id,
                'bagian_id' => $data['bagian_id'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
                'is_active' => true,
            ]);

            $userId = $user->id;

            Notification::make()
                ->title('Akun login dibuat')
                ->body("{$user->name} bisa login dengan NIP {$user->nip}.")
                ->success()
                ->send();
        }

        $pembimbing = PembimbingLapangan::create([
            'nama' => $data['nama'],
            'jabatan' => $data['jabatan'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'bagian_id' => $data['bagian_id'] ?? null,
            'user_id' => $userId,
            'is_active' => true,
        ]);

        return $pembimbing->id;
    }

    /**
     * (Fungsi "Beri Catatan Calon Pembimbing" yang sebelumnya jadi tombol
     * TERPISAH di sini SUDAH DIHAPUS -- digabung ke prosesApprovalAction()
     * di atas, karena Kepala Bagian Tujuan sekarang jadi tahap ke-4 di
     * rantai tanda tangan yang sama dengan GM/Kabag SDM/Staff SDM.)
     */

    /**
     * PIC: nonaktifkan akun login peserta setelah PKL-nya selesai (peserta
     * tidak bisa login lagi), TAPI semua data pengajuan/dokumen/nilai
     * peserta tetap tersimpan utuh dan tetap bisa dilihat PIC kapan saja.
     * Bisa diaktifkan lagi kalau ternyata perlu (mis. salah nonaktifkan,
     * atau peserta lanjut ke jalur pengajuan baru).
     */
    protected static function toggleAkunPesertaAction(): Action
    {
        return Action::make('toggleAkunPeserta')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label(fn (Pengajuan $record) => $record->peserta?->user?->is_active ? 'Nonaktifkan Akun Peserta' : 'Aktifkan Kembali Akun Peserta')
            ->icon(fn (Pengajuan $record) => $record->peserta?->user?->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
            ->color(fn (Pengajuan $record) => $record->peserta?->user?->is_active ? 'danger' : 'gray')
            ->requiresConfirmation()
            ->modalDescription(fn (Pengajuan $record) => $record->peserta?->user?->is_active
                ? 'Peserta tidak akan bisa login lagi setelah ini. Semua data pengajuan, dokumen, dan hasil evaluasinya TETAP tersimpan dan tetap bisa Anda lihat.'
                : 'Peserta akan bisa login kembali ke akunnya.')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->status === 'selesai'
                && $record->peserta?->user)
            ->action(function (Pengajuan $record) {
                $user = $record->peserta->user;
                $user->update(['is_active' => ! $user->is_active]);

                Notification::make()
                    ->title($user->is_active ? 'Akun peserta diaktifkan kembali' : 'Akun peserta dinonaktifkan')
                    ->success()
                    ->send();
            });
    }

    /**
     * PIC: upload PDF hasil penilaian akhir peserta setelah PKL selesai
     * dijalani. Formulir & templatnya milik institusi/kampus atau
     * perusahaan sendiri (bukan dibuat sistem) -- diisi & ditandatangani
     * Pembimbing Lapangan secara fisik/di luar sistem, PIC tinggal
     * upload hasil scan-nya. Peserta hanya bisa melihat & mengunduh.
     * Kalau sudah pernah upload, tombolnya jadi "Upload Ulang" (menimpa
     * file sebelumnya, mis. kalau ada revisi).
     */
    protected static function uploadPenilaianAction(): Action
    {
        return Action::make('uploadPenilaian')
            ->authorize(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC) === true
                || (Auth::user()?->hasRole(RoleSlug::PEMBIMBING_LAPANGAN) === true
                    && $record->penugasanPembimbing?->pembimbing_id === Auth::id()))
            ->label(fn (Pengajuan $record) => $record->penilaian ? 'Unggah Ulang PDF Penilaian' : 'Unggah PDF Penilaian')
            ->icon(fn (Pengajuan $record) => $record->penilaian ? 'heroicon-o-arrow-up-tray' : 'heroicon-o-document-plus')
            ->color('gray')
            ->visible(fn (Pengajuan $record) => $record->status === 'berjalan'
                && (
                    Auth::user()?->hasRole(RoleSlug::PIC)
                    || (Auth::user()?->hasRole(RoleSlug::PEMBIMBING_LAPANGAN) && $record->penugasanPembimbing?->pembimbing_id === Auth::id())
                ))
            ->modalDescription('Unggah PDF formulir penilaian yang sudah diisi dan ditandatangani Pembimbing Lapangan. Gunakan dokumen dari institusi pendidikan atau perusahaan sesuai proses yang berlaku.')
            ->schema([
                FileUpload::make('file_pdf')
                    ->label('File PDF Penilaian')
                    ->disk(config('filesystems.private_documents_disk', 'documents'))
                    ->visibility('private')
                    ->directory('penilaian')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240)
                    ->required(),
            ])
            ->action(function (Pengajuan $record, array $data) {
                app(PengajuanWorkflowService::class)->uploadPenilaian($record, Auth::user(), $data['file_pdf']);

                Notification::make()->title('PDF penilaian tersimpan')->success()->send();
            });
    }

    /**
     * PIC: isi form "CHECK LIST PERSIAPAN PELAKSANAAN EVALUASI MAGANG/PKL"
     * (identitas, jadwal/tempat, peralatan/dokumen persiapan evaluasi).
     * Bisa dibuka kapan saja setelah evaluasi perpanjangan dimulai -- label
     * tombolnya menampilkan progres "x/y selesai" seperti diminta.
     */
    protected static function checklistPersiapanEvaluasiAction(): Action
    {
        return Action::make('checklistPersiapanEvaluasi')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label(function (Pengajuan $record) {
                $progres = self::progresChecklistEvaluasi($record->evaluasi);

                return $progres
                    ? "Checklist Persiapan Evaluasi ({$progres['selesai']}/{$progres['total']} selesai)"
                    : 'Checklist Persiapan Evaluasi';
            })
            ->icon('heroicon-o-clipboard-document-list')
            ->color('gray')
            ->modalHeading('Checklist Persiapan Pelaksanaan Evaluasi Magang/PKL')
            ->modalDescription(fn (Pengajuan $record) => "Peserta: {$record->peserta?->user?->name} — Bagian: {$record->bagianTujuan?->nama_bagian}"
                .($record->penugasanPembimbing ? " — Pembimbing Lapang: {$record->penugasanPembimbing->nama_tampil}" : ''))
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC) && $record->evaluasi)
            ->fillForm(function (Pengajuan $record) {
                $tersimpan = collect($record->evaluasi->checklist_persiapan ?: [])->keyBy('label');

                $itemDefault = collect(PengajuanWorkflowService::ITEM_CHECKLIST_PERSIAPAN_EVALUASI)
                    ->map(fn ($label) => [
                        'label' => $label,
                        'checked' => (bool) ($tersimpan->get($label)['checked'] ?? false),
                    ]);

                $itemTambahan = $tersimpan
                    ->reject(fn ($item, $label) => in_array($label, PengajuanWorkflowService::ITEM_CHECKLIST_PERSIAPAN_EVALUASI, true))
                    ->values();

                return [
                    'nama_rekan_kerja' => $record->evaluasi->nama_rekan_kerja,
                    'nama_pendamping_sdm' => $record->evaluasi->nama_pendamping_sdm,
                    'tempat_pelaksanaan' => $record->evaluasi->tempat_pelaksanaan,
                    'checklist' => $itemDefault->merge($itemTambahan)->values()->all(),
                ];
            })
            ->schema([
                TextInput::make('nama_rekan_kerja')->label('Nama Rekan Kerja'),
                TextInput::make('nama_pendamping_sdm')->label('Nama Pendamping dari Bagian SDM'),
                TextInput::make('tempat_pelaksanaan')->label('Tempat Pelaksanaan Evaluasi'),
                Repeater::make('checklist')
                    ->label('Checklist Persiapan')
                    ->schema([
                        TextInput::make('label')->label('Item')->required()->columnSpan(3),
                        Toggle::make('checked')->label('Checked')->inline(false)->columnSpan(1),
                    ])
                    ->columns(4)
                    ->addActionLabel('Tambah Item')
                    ->reorderable(false),
            ])
            ->action(function (Pengajuan $record, array $data) {
                $checklist = collect($data['checklist'] ?? [])
                    ->filter(fn ($item) => filled($item['label'] ?? null))
                    ->mapWithKeys(fn ($item) => [$item['label'] => (bool) ($item['checked'] ?? false)])
                    ->all();

                app(PengajuanWorkflowService::class)->simpanChecklistPersiapanEvaluasi(
                    $record->evaluasi,
                    $checklist,
                    $data['tempat_pelaksanaan'] ?? null,
                    $data['nama_rekan_kerja'] ?? null,
                    $data['nama_pendamping_sdm'] ?? null,
                );

                Notification::make()->title('Checklist persiapan evaluasi tersimpan')->success()->send();
            });
    }

    /**
     * Hitung progres "x/y selesai" dari checklist_persiapan yang tersimpan,
     * dipakai untuk label tombol checklistPersiapanEvaluasiAction().
     *
     * @return array{selesai: int, total: int}|null
     */
    protected static function progresChecklistEvaluasi(?Evaluasi $evaluasi): ?array
    {
        if (! $evaluasi || ! $evaluasi->checklist_persiapan) {
            return null;
        }

        $total = count($evaluasi->checklist_persiapan);
        $selesai = collect($evaluasi->checklist_persiapan)->where('checked', true)->count();

        return ['selesai' => $selesai, 'total' => $total];
    }

    /**
     * Skema Repeater skor per aspek, dipakai bersama oleh Pembimbing
     * Lapangan (online) & PIC (input dari lembar manual). Aspeknya sudah
     * ditentukan peserta (lihat PengajuanWorkflowService::usulkanAspekPenilaian()),
     * jadi nama aspek tinggal ditampilkan (disabled), tidak bisa diubah
     * lagi di titik ini.
     *
     * @return array<int, mixed>
     */
    protected static function schemaInputSkorAspek(): array
    {
        return [
            Repeater::make('aspek')
                ->label('Skor per Aspek')
                ->schema([
                    TextInput::make('aspek')->label('Aspek')->disabled()->dehydrated(),
                    TextInput::make('skor')->label('Skor (0-100)')->numeric()->minValue(0)->maxValue(100)->required(),
                ])
                ->columns(2)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false),
            Select::make('hasil')
                ->label('Hasil')
                ->options([
                    'selesai' => 'Selesai (Lulus)',
                    'perlu_perpanjangan' => 'Perlu Tindak Lanjut Perpanjangan',
                ])
                ->required(),
            Textarea::make('catatan')
                ->label('Catatan (opsional)')
                ->rows(2),
        ];
    }

    /**
     * Pembimbing Lapangan (kalau punya akun login): input skor per aspek
     * langsung online, menggantikan lembar penilaian fisik. Hanya
     * pembimbing yang ditugaskan di pengajuan ini yang bisa mengisi.
     */
    protected static function inputPenilaianPembimbingAction(): Action
    {
        return Action::make('inputPenilaianPembimbing')
            ->authorize(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PEMBIMBING_LAPANGAN) === true
                && $record->penugasanPembimbing?->pembimbing_id === Auth::id())
            ->label('Input Nilai Penilaian')
            ->icon('heroicon-o-pencil-square')
            ->color('primary')
            ->modalHeading('Input Nilai Penilaian PKL/Penelitian')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PEMBIMBING_LAPANGAN)
                && $record->penugasanPembimbing?->pembimbing_id === Auth::id()
                && $record->evaluasi
                && $record->evaluasi->dinilai_at === null
                && filled($record->evaluasi->aspek_penilaian_default))
            ->fillForm(fn (Pengajuan $record) => [
                'aspek' => collect($record->evaluasi->aspek_penilaian_default)->map(fn (string $aspek) => ['aspek' => $aspek, 'skor' => null])->all(),
            ])
            ->schema(self::schemaInputSkorAspek())
            ->action(function (Pengajuan $record, array $data) {
                app(PengajuanWorkflowService::class)->inputPenilaian(
                    $record->evaluasi,
                    collect($data['aspek'])->map(fn ($item) => ['aspek' => $item['aspek'], 'skor' => $item['skor']])->all(),
                    Auth::user(),
                    $data['hasil'],
                    $data['catatan'] ?? null,
                );

                Notification::make()->title('Nilai penilaian tersimpan')->success()->send();
            });
    }

    /**
     * PIC: input skor per aspek dari lembar penilaian FISIK yang diisi
     * Pembimbing Lapangan di luar sistem -- dipakai kalau Pembimbing
     * Lapangan TIDAK punya akun login (kalau punya akun, dia yang input
     * sendiri lewat inputPenilaianPembimbingAction() di atas).
     */
    protected static function inputPenilaianPicAction(): Action
    {
        return Action::make('inputPenilaianPic')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Input Hasil Akhir (Manual)')
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->modalHeading('Input Hasil Akhir Penilaian (Manual)')
            ->modalDescription('Gunakan ini kalau Pembimbing Lapangan tidak punya akun login -- PIC cukup rekap kesimpulan akhir dari lembar penilaian fisik, tidak perlu input skor per aspek.')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->penugasanPembimbing?->pembimbing_id === null
                && $record->evaluasi
                && $record->evaluasi->dinilai_at === null)
            ->schema([
                TextInput::make('nilai_akhir')
                    ->label('Nilai Akhir (opsional)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                Select::make('hasil')
                    ->label('Hasil')
                    ->options([
                        'selesai' => 'Selesai (Lulus)',
                        'perlu_perpanjangan' => 'Perlu Tindak Lanjut Perpanjangan',
                    ])
                    ->required(),
                Textarea::make('catatan')
                    ->label('Catatan (opsional)')
                    ->rows(2),
                FileUpload::make('file_bukti')
                    ->label('Scan Lembar Penilaian (opsional)')
                    ->disk(config('filesystems.private_documents_disk', 'documents'))
                    ->visibility('private')
                    ->directory('bukti-penilaian')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240),
            ])
            ->action(function (Pengajuan $record, array $data) {
                app(PengajuanWorkflowService::class)->inputHasilAkhirManual(
                    $record->evaluasi,
                    Auth::user(),
                    $data['hasil'],
                    $data['nilai_akhir'] !== null && $data['nilai_akhir'] !== '' ? (float) $data['nilai_akhir'] : null,
                    $data['catatan'] ?? null,
                    $data['file_bukti'] ?? null,
                );

                Notification::make()->title('Hasil akhir tersimpan')->success()->send();
            });
    }

    /**
     * PIC memutuskan permohonan perpanjangan yang diajukan peserta.
     * Cabang setuju tetap divalidasi service: evaluasi harus sudah selesai
     * sebelum periode baru boleh dibuat.
     */
    protected static function putuskanPerpanjanganAction(): Action
    {
        return Action::make('putuskanPerpanjangan')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Putuskan Perpanjangan')
            ->icon('heroicon-o-arrows-right-left')
            ->color('warning')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->perpanjangans()->where('status', 'menunggu')->exists())
            ->schema([
                Select::make('keputusan')
                    ->label('Keputusan')
                    ->options([
                        'disetujui' => 'Setujui Perpanjangan',
                        'ditolak' => 'Tolak Perpanjangan',
                    ])
                    ->required(),
            ])
            ->action(function (Pengajuan $record, array $data) {
                $perpanjangan = $record->perpanjangans()
                    ->where('status', 'menunggu')
                    ->oldest()
                    ->firstOrFail();

                app(PengajuanWorkflowService::class)->putuskanPerpanjangan(
                    $perpanjangan,
                    $data['keputusan'],
                    Auth::user(),
                );

                Notification::make()
                    ->title($data['keputusan'] === 'disetujui' ? 'Perpanjangan disetujui' : 'Perpanjangan ditolak')
                    ->success()
                    ->send();
            });
    }

    /**
     * PIC: tutup periode PKL/Penelitian TANPA perpanjangan (kondisi
     * normal, evaluasi bersifat opsional di jalur ini -- lihat
     * PengajuanWorkflowService::selesaikanTanpaPerpanjangan()). Beda dari
     * permohonan perpanjangan resmi (diajukan PESERTA sendiri lewat
     * ViewPengajuan miliknya begitu status 'perlu_perpanjangan' dan
     * evaluasi sudah dinilai -- lihat
     * PengajuanWorkflowService::ajukanPermohonanPerpanjangan()): tombol
     * ini muncul selama pengajuan masih 'berjalan', supaya PIC bisa
     * menutupnya kapan saja tanpa mewajibkan evaluasi.
     */
    protected static function selesaikanTanpaPerpanjanganAction(): Action
    {
        return Action::make('selesaikanTanpaPerpanjangan')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Selesaikan tanpa Perpanjangan')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Selesaikan Periode PKL')
            ->modalDescription('Apakah Anda yakin ingin menyelesaikan periode PKL ini tanpa perpanjangan?')
            ->modalSubmitActionLabel('Ya, Selesaikan')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->status === 'berjalan')
            ->action(function (Pengajuan $record) {
                app(PengajuanWorkflowService::class)->selesaikanTanpaPerpanjangan($record, Auth::user());

                Notification::make()->title('Periode PKL berhasil diselesaikan.')->body('Status: Selesai')->success()->send();
            });
    }

    /**
     * PIC: hentikan/tolak PKL/Penelitian yang SEDANG BERJALAN karena
     * pelanggaran/masalah serius -- beda dari selesaikanTanpaPerpanjanganAction()
     * yang untuk penyelesaian normal. Alasan wajib diisi.
     */
    protected static function tolakPengajuanBerjalanAction(): Action
    {
        return Action::make('tolakPengajuanBerjalan')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Tolak')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Hentikan PKL/Penelitian Ini')
            ->modalDescription('PKL/Penelitian akan dihentikan (status: Ditolak). Aksi ini untuk kasus pelanggaran/masalah serius, bukan penyelesaian normal.')
            ->modalSubmitActionLabel('Ya, Hentikan')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->status === 'berjalan')
            ->schema([
                Textarea::make('alasan')
                    ->label('Alasan Penghentian')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (Pengajuan $record, array $data) {
                app(PengajuanWorkflowService::class)->tolakPengajuanBerjalan($record, Auth::user(), $data['alasan']);

                Notification::make()->title('PKL/Penelitian dihentikan.')->body('Status: Ditolak')->success()->send();
            });
    }

    /**
     * PIC: batalkan pengajuan SELAMA MASIH DALAM PROSES APPROVAL (dari
     * disposisi GM/Kabag SDM/Staff SDM sampai menunggu persetujuan
     * Kepala Bagian atas pembimbing). Begitu semua tahap approval selesai
     * dan status jadi 'berjalan', tombol ini otomatis HILANG (beda dari
     * tolakPengajuanBerjalanAction() yang baru muncul justru setelah itu).
     */
    protected static function batalkanPengajuanDalamProsesAction(): Action
    {
        return Action::make('batalkanPengajuanDalamProses')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Tolak')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Batalkan Pengajuan Ini')
            ->modalDescription('Pengajuan yang masih dalam proses approval (GM s.d. Kepala Bagian) akan dibatalkan (status: Ditolak). Tahap approval yang belum ditandatangani otomatis dibatalkan.')
            ->modalSubmitActionLabel('Ya, Batalkan')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && in_array($record->status, ['proses_approval', 'disetujui', 'menunggu_catatan_pembimbing', 'menunggu_penetapan_pembimbing'], true))
            ->schema([
                Textarea::make('alasan')
                    ->label('Alasan Pembatalan')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (Pengajuan $record, array $data) {
                app(PengajuanWorkflowService::class)->batalkanPengajuanDalamProses($record, Auth::user(), $data['alasan']);

                Notification::make()->title('Pengajuan dibatalkan.')->body('Status: Ditolak')->success()->send();
            });
    }

    /**
     * PIC: tentukan jadwal evaluasi pada formulir yang sudah dibuat
     * (jadwal_evaluasi masih kosong). Menggantikan peran Pembimbing
     * Lapangan yang tidak login ke sistem.
     */
    protected static function jadwalkanEvaluasiAction(): Action
    {
        return Action::make('jadwalkanEvaluasi')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Jadwalkan Evaluasi')
            ->icon('heroicon-o-calendar')
            ->color('primary')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->evaluasi
                && $record->evaluasi->jadwal_evaluasi === null)
            ->schema([
                DatePicker::make('jadwal_evaluasi')
                    ->label('Jadwal Evaluasi/Presentasi')
                    ->required()
                    ->minDate(now()),
            ])
            ->action(function (Pengajuan $record, array $data) {
                app(PengajuanWorkflowService::class)->jadwalkanEvaluasi(
                    $record->evaluasi,
                    $data['jadwal_evaluasi'],
                    Auth::user()
                );

                Notification::make()->title('Jadwal evaluasi ditentukan')->success()->send();
            });
    }

    /**
     * PIC: tandai evaluasi perpanjangan sudah dilaksanakan (dinilai_at
     * terisi) -- syarat wajib sebelum permohonan perpanjangan resmi boleh
     * diajukan. Tidak ada lagi input skor -- keputusan perpanjang/tidak
     * sudah ditentukan peserta sendiri lewat Penilaian::keputusan.
     */
    protected static function selesaikanEvaluasiPerpanjanganAction(): Action
    {
        return Action::make('selesaikanEvaluasiPerpanjangan')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Selesaikan Evaluasi Perpanjangan')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('success')
            ->modalDescription('Tandai bahwa pertemuan/evaluasi perpanjangan sudah dilaksanakan.')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->evaluasi
                && $record->evaluasi->jadwal_evaluasi !== null
                && $record->evaluasi->dinilai_at === null)
            ->schema([
                Textarea::make('catatan')
                    ->label('Catatan (opsional)')
                    ->rows(3),
            ])
            ->action(function (Pengajuan $record, array $data) {
                app(PengajuanWorkflowService::class)->selesaikanEvaluasiPerpanjangan(
                    $record->evaluasi,
                    Auth::user(),
                    $data['catatan'] ?? null,
                );

                Notification::make()->title('Evaluasi perpanjangan ditandai selesai')->success()->send();
            });
    }

    /**
     * PIC: upload Surat Keterangan Selesai PKL/Penelitian yang sudah
     * ditandatangani & discan -- flowchart langkah 17-18, cabang "Lulus".
     * Bukan lagi generate otomatis dari template sistem.
     */
    protected static function cetakSuratKeteranganSelesaiAction(): Action
    {
        return Action::make('cetakSuratKeteranganSelesai')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label(fn (Pengajuan $record) => $record->suratKeterangan ? 'Unggah Ulang Surat Keterangan Selesai' : 'Unggah Surat Keterangan Selesai')
            ->icon('heroicon-o-document-check')
            ->color('success')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->status === 'selesai')
            ->modalDescription('Unggah PDF Surat Keterangan Selesai yang sudah ditandatangani dan distempel.')
            ->fillForm(fn (Pengajuan $record) => [
                'nomor_surat' => $record->suratKeterangan?->nomor_surat,
            ])
            ->schema([
                TextInput::make('nomor_surat')
                    ->label('Nomor Surat (opsional)')
                    ->helperText('Kosongkan untuk pakai format otomatis SKL/{id}/{bulan}/{tahun}.'),
                FileUpload::make('file_pdf')
                    ->label('File PDF Surat Keterangan Selesai')
                    ->disk(config('filesystems.private_documents_disk', 'documents'))
                    ->visibility('private')
                    ->directory('surat-keterangan')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240)
                    ->required(),
            ])
            ->action(function (Pengajuan $record, array $data) {
                app(PengajuanWorkflowService::class)->uploadSuratKeteranganSelesai(
                    $record,
                    Auth::user(),
                    $data['file_pdf'],
                    $data['nomor_surat'] ?: null,
                );

                Notification::make()->title('Surat Keterangan Selesai tersimpan')->success()->send();
            });
    }

    /**
     * PIC: cetak Surat Perpanjangan PKL — flowchart langkah 17-18, cabang
     * "Belum memenuhi standar". Hanya aktif setelah Kepala Bagian Tujuan
     * menyetujui pengajuan perpanjangannya.
     */
    protected static function cetakSuratPerpanjanganAction(): Action
    {
        return Action::make('cetakSuratPerpanjangan')
            ->authorize(fn () => Auth::user()?->hasRole(RoleSlug::PIC) === true)
            ->label('Cetak Surat Perpanjangan')
            ->icon('heroicon-o-document-check')
            ->color('warning')
            ->visible(fn (Pengajuan $record) => Auth::user()?->hasRole(RoleSlug::PIC)
                && $record->perpanjangans()->where('status', 'disetujui')->exists())
            ->url(fn (Pengajuan $record) => route(
                'perpanjangan.surat-perpanjangan',
                $record->perpanjangans()->where('status', 'disetujui')->latest()->first()
            ))
            ->openUrlInNewTab();
    }

    /**
     * Cari tahap approval yang jadi giliran user yang login SEKARANG,
     * berdasarkan role-nya dan status 'menunggu' yang urutan-nya paling
     * kecil. Khusus role 'kepala_bagian' (urutan 4): TIDAK cukup cocok
     * role saja -- role ini ada BANYAK orangnya (satu per bagian), jadi
     * wajib dicek juga apakah dia benar Kepala Bagian dari bagian_tujuan
     * pengajuan ini (beda dari gm/kabag_sdm/staff_sdm yang cuma satu
     * orang per role di seluruh perusahaan).
     */
    protected static function langkahApprovalMilikSaya(Pengajuan $record): ?ApprovalWorkflow
    {
        $roleSlug = Auth::user()?->role?->slug;

        $urutanRole = array_search($roleSlug, PengajuanWorkflowService::URUTAN_APPROVAL, true);

        if ($urutanRole === false) {
            return null;
        }

        if ($roleSlug === 'kepala_bagian' && $record->bagianTujuan?->kepala_bagian_id !== Auth::id()) {
            return null;
        }

        $langkahAktif = $record->approvalWorkflows()
            ->where('status', 'menunggu')
            ->orderBy('urutan')
            ->first();

        if (! $langkahAktif || $langkahAktif->urutan !== $urutanRole) {
            return null;
        }

        return $langkahAktif;
    }

    public static function labelStatus(string $status): string
    {
        return PengajuanStatusPresenter::label($status);
    }

    public static function warnaStatus(string $status): string
    {
        return PengajuanStatusPresenter::color($status);
    }
}
