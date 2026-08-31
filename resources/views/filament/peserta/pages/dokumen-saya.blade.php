<x-filament-panels::page>
    @if (! $pengajuan)
        <section class="sipkl-card sipkl-empty-state" aria-labelledby="dokumen-empty-title">
            <div class="sipkl-empty-icon" aria-hidden="true"><x-heroicon-o-folder-open /></div>
            <h2 id="dokumen-empty-title" class="sipkl-card-title">Belum ada dokumen pengajuan</h2>
            <p>Dokumen akan tampil di halaman ini setelah Anda membuat pengajuan.</p>
        </section>
    @elseif (empty($dokumen))
        <section class="sipkl-card sipkl-empty-state" aria-labelledby="dokumen-none-title">
            <div class="sipkl-empty-icon" aria-hidden="true"><x-heroicon-o-document /></div>
            <h2 id="dokumen-none-title" class="sipkl-card-title">Belum ada dokumen yang tercatat</h2>
            <p>Lengkapi dokumen melalui halaman pengajuan. Status verifikasi akan muncul di sini setelah dokumen tersimpan.</p>
        </section>
    @else
        <section class="sipkl-card" aria-labelledby="dokumen-title">
            <div class="sipkl-card-heading-row">
                <div>
                    <p class="sipkl-eyebrow">Pengajuan {{ $pengajuan->nomor_agenda ?? 'tanpa nomor agenda' }}</p>
                    <h2 id="dokumen-title" class="sipkl-card-title">Status Dokumen Persyaratan</h2>
                </div>
                @if ($pengajuan && in_array($pengajuan->status, ['draft', 'dokumen_ditolak']))
                    <a href="{{ \App\Filament\Peserta\Resources\PengajuanResource::getUrl('edit', ['record' => $pengajuan]) }}" class="sipkl-text-link">Buka pengajuan</a>
                @endif
            </div>

            <p class="sipkl-muted">Tabel dapat digeser secara horizontal pada layar kecil. Dokumen yang perlu revisi dapat diperbaiki dari kolom tindakan.</p>

            <div class="sipkl-table-wrap" role="region" aria-label="Daftar status dokumen" tabindex="0">
                <table class="sipkl-table">
                    <caption class="sipkl-sr-only">Daftar dokumen persyaratan dan status verifikasinya</caption>
                    <thead>
                        <tr>
                            <th scope="col">Dokumen</th>
                            <th scope="col">Status</th>
                            <th scope="col">Catatan PIC</th>
                            <th scope="col">Berkas</th>
                            <th scope="col">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dokumen as $d)
                            <tr>
                                <td>{{ $d['jenis_dokumen'] }}</td>
                                <td><span class="sipkl-badge {{ $this->warnaStatusDokumen($d['status_verifikasi']) }}">{{ $this->labelStatusDokumen($d['status_verifikasi']) }}</span></td>
                                <td>{{ $d['catatan_verifikasi'] ?: 'Tidak ada catatan.' }}</td>
                                <td>
                                    <a href="{{ route('documents.persyaratan', $d['id']) }}" target="_blank" rel="noopener" class="sipkl-link-btn">
                                        Buka berkas <span class="sipkl-sr-only">{{ $d['jenis_dokumen'] }} di tab baru</span>
                                    </a>
                                </td>
                                <td>
                                    @if ($d['status_verifikasi'] === 'tidak_lengkap')
                                        <x-filament::button
                                            size="sm"
                                            color="danger"
                                            icon="heroicon-o-arrow-up-tray"
                                            wire:click="mountAction('perbaikiDokumen', { dokumenId: {{ $d['id'] }} })"
                                        >
                                            Unggah Perbaikan
                                        </x-filament::button>
                                    @else
                                        <span class="sipkl-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
