<?php

namespace App\Filament\Pages;

use App\Enums\RoleSlug;
use App\Filament\Resources\Pengajuans\PengajuanResource;
use App\Models\ApprovalWorkflow;
use App\Models\Pengajuan;
use App\Services\PengajuanWorkflowService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TugasSaya extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.tugas-saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?int $navigationSort = -1;

    public static function getNavigationLabel(): string
    {
        return Auth::user()?->role?->slug === 'gm' ? 'Persetujuan Pengajuan' : 'Tugas Saya';
    }

    /**
     * Halaman ini hanya relevan & muncul di sidebar untuk role yang
     * tugasnya cuma "tandatangani disposisi" atau "tetapkan pembimbing" —
     * bukan role yang perlu lihat tabel pengajuan lengkap (PIC, pembimbing).
     */
    public static function shouldRegisterNavigation(): bool
    {
        return in_array(Auth::user()?->role?->slug, ['gm', 'kabag_sdm', 'staff_sdm', 'kepala_bagian']);
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->queryPerRole())
            ->columns([
                TextColumn::make('peserta.user.name')->label('Nama Peserta'),
                TextColumn::make('jenis_pengajuan')->label('Jenis'),
                TextColumn::make('bagian.nama_bagian')->label('Bagian Tujuan'),
                TextColumn::make('created_at')->label('Diajukan')->dateTime('d M Y'),
            ])
            ->recordActions([
                Action::make('lihatDetail')
                    ->authorize(fn (Pengajuan $record) => Auth::user()?->can('view', $record) === true)
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Pengajuan $record) => PengajuanResource::getUrl('view', ['record' => $record])),
                ...$this->aksiUtama(),
            ])
            ->emptyStateHeading('Tidak ada tugas yang menunggu')
            ->emptyStateDescription('Semua pengajuan yang menjadi giliran Anda sudah diproses.')
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle);
    }

    protected function queryPerRole(): Builder
    {
        $user = Auth::user();
        $roleSlug = $user?->role?->slug;

        $query = Pengajuan::query();

        return match ($roleSlug) {
            'gm', 'kabag_sdm', 'staff_sdm' => $this->scopeGiliranSaya(
                $query->where('status', 'proses_approval'),
                $roleSlug,
            ),

            // Kepala Bagian Tujuan sekarang jadi tahap ke-4 di rantai
            // disposisi yang sama (mekanisme identik gm/kabag_sdm/staff_sdm)
            // -- bedanya wajib di-scope ke bagian yang dia pimpin saja
            // (role ini ada banyak orangnya, satu per bagian). Perpanjangan
            // TETAP jadi wewenang khusus Kepala Bagian, tidak berubah.
            'kepala_bagian' => $query->whereHas('bagianTujuan', fn ($q) => $q->where('kepala_bagian_id', $user->id))
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $this->scopeGiliranSaya(
                            $q2->where('status', 'proses_approval'),
                            'kepala_bagian',
                        );
                    })->orWhere(function ($q2) {
                        $q2->where('status', 'perlu_perpanjangan')
                            ->whereHas('perpanjangans', fn ($q3) => $q3->where('status', 'menunggu'));
                    });
                }),

            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Batasi query ke pengajuan yang benar-benar sedang menjadi giliran role
     * ini. Dikerjakan seluruhnya di SQL agar tidak terjadi N+1 query ketika
     * jumlah approval bertambah.
     */
    protected function scopeGiliranSaya(Builder $query, string $roleSlug): Builder
    {
        $urutanSaya = array_search($roleSlug, PengajuanWorkflowService::URUTAN_APPROVAL, true);

        if ($urutanSaya === false) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereHas('approvalWorkflows', fn ($approval) => $approval
                ->where('urutan', $urutanSaya)
                ->where('status', 'menunggu'))
            ->whereDoesntHave('approvalWorkflows', fn ($approval) => $approval
                ->where('status', 'menunggu')
                ->where('urutan', '<', $urutanSaya));
    }

    /**
     * Tombol aksi:
     * - "Tandatangani" -> dipakai SEMUA 4 role disposisi (gm/kabag_sdm/
     *   staff_sdm/kepala_bagian), mekanisme identik (sesuai flowchart
     *   TO-BE: hanya "mengetahui dan menandatangani", TIDAK ADA opsi
     *   menolak di tahap manapun -- satu-satunya titik penolakan ada di
     *   verifikasi dokumen oleh PIC). Khusus Kepala Bagian Tujuan (tahap
     *   TERAKHIR), form-nya nambah 1 field wajib: catatan calon Pembimbing
     *   Lapangan untuk PIC -- digabung ke aksi yang sama, bukan tombol
     *   terpisah.
     * - "Putuskan Perpanjangan" -> DIPINDAH ke PIC (lihat
     *   PengajuansTable::putuskanPerpanjanganAction()), BUKAN lagi
     *   kewenangan Kepala Bagian Tujuan.
     *
     * @return array<int, Action>
     */
    protected function aksiUtama(): array
    {
        $roleSlug = Auth::user()->role?->slug;

        $aksi = [
            Action::make('tandatangani')
                ->authorize(fn (Pengajuan $record) => Auth::user()?->can('view', $record) === true
                    && Auth::user()->hasAnyRole([RoleSlug::GM, RoleSlug::KABAG_SDM, RoleSlug::STAFF_SDM, RoleSlug::KEPALA_BAGIAN]))
                ->label('Tandatangani')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Tandatangani Disposisi')
                ->modalDescription($roleSlug === 'kepala_bagian'
                    ? 'Ini tahap persetujuan terakhir. Cantumkan calon pembimbing lapangan agar PIC dapat menindaklanjuti penempatan.'
                    : 'Tanda tangan akan dicatat dan pengajuan diteruskan ke tahap persetujuan berikutnya.')
                ->modalSubmitActionLabel('Tandatangani & Lanjutkan')
                ->schema(function () use ($roleSlug) {
                    $fields = [
                        Textarea::make('catatan')
                            ->label('Catatan (Opsional)')
                            ->helperText('Isi hanya jika ada informasi yang perlu dibaca pada tahap berikutnya.')
                            ->placeholder('Contoh: Dokumen telah diperiksa; lanjutkan sesuai prosedur.')
                            ->rows(3),
                    ];

                    if ($roleSlug === 'kepala_bagian') {
                        $fields[] = Textarea::make('catatan_calon_pembimbing')
                            ->label('Calon Pembimbing Lapangan')
                            ->helperText('Tuliskan nama atau keterangan calon pembimbing yang dapat ditindaklanjuti PIC.')
                            ->placeholder('Contoh: Budi Santoso — Kepala Regu Maintenance.')
                            ->rows(3)
                            ->required();
                    }

                    return $fields;
                })
                ->action(function (Pengajuan $record, array $data) use ($roleSlug) {
                    $this->prosesTandaTangan($record, $roleSlug, $data['catatan'] ?? null, $data['catatan_calon_pembimbing'] ?? null);
                }),
        ];

        return $aksi;
    }

    /**
     * Logika aksi Tandatangani: cari tahap disposisi yang jadi giliran role
     * ini, lalu proses lewat PengajuanWorkflowService::tandatanganiLangkah()
     * supaya seluruh efek samping (notifikasi, riwayat status, dsb) konsisten.
     */
    protected function prosesTandaTangan(Pengajuan $record, ?string $roleSlug, ?string $catatan, ?string $catatanCalonPembimbing = null): void
    {
        $step = $this->cariGiliranSaya($record, $roleSlug);

        if (! $step) {
            return;
        }

        try {
            app(PengajuanWorkflowService::class)->tandatanganiLangkah(
                $step,
                Auth::user(),
                $catatan,
                $catatanCalonPembimbing
            );

            Notification::make()
                ->title('Disposisi berhasil ditandatangani')
                ->success()
                ->send();
        } catch (\RuntimeException $e) {
            Notification::make()->title('Gagal diproses')->body($e->getMessage())->danger()->send();
        }
    }

    /**
     * Cari tahap disposisi yang sedang menunggu untuk role ini pada
     * pengajuan tertentu. Khusus 'kepala_bagian': tambahan cek bagian
     * (role ini ada banyak orangnya, satu per bagian -- beda dari
     * gm/kabag_sdm/staff_sdm yang cuma satu orang per role).
     */
    protected function cariGiliranSaya(Pengajuan $record, ?string $roleSlug): ?ApprovalWorkflow
    {
        $urutan = array_search($roleSlug, PengajuanWorkflowService::URUTAN_APPROVAL, true);

        if ($roleSlug === 'kepala_bagian' && $record->bagianTujuan?->kepala_bagian_id !== Auth::id()) {
            Notification::make()->title('Bukan giliran Anda')->danger()->send();

            return null;
        }

        $step = $record->approvalWorkflows()
            ->where('urutan', $urutan)
            ->where('status', 'menunggu')
            ->first();

        if (! $step) {
            Notification::make()->title('Bukan giliran Anda')->danger()->send();

            return null;
        }

        return $step;
    }
}
