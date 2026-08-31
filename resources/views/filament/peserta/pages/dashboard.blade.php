<x-filament-panels::page>
    @php
        $totalDokumen = count($dokumen);
        $dokumenLengkap = collect($dokumen)->where('status_verifikasi', 'lengkap')->count();
        $statusPengajuan = $pengajuan?->status;
        $isDisetujui = $pengajuan && in_array($statusPengajuan, ['disetujui', 'berjalan', 'selesai']);
        $stateLabels = [
            'selesai' => 'Selesai',
            'sedang_diproses' => 'Sedang diproses',
            'belum_diproses' => 'Belum diproses',
            'ditolak' => 'Perlu tindak lanjut',
        ];
    @endphp

    <section class="sipkl-peserta-header" aria-labelledby="peserta-welcome-title">
        <p class="sipkl-eyebrow">Ringkasan peserta</p>
        <h2 id="peserta-welcome-title" class="sipkl-peserta-name">Halo, {{ Auth::user()->name }}</h2>
        <p class="sipkl-peserta-sub">Pantau status pengajuan, dokumen, dan informasi terbaru dari satu halaman.</p>
    </section>

    @if (! $pengajuan)
        <section class="sipkl-card sipkl-empty-state" aria-labelledby="empty-pengajuan-title">
            <div class="sipkl-empty-icon" aria-hidden="true"><x-heroicon-o-document-plus /></div>
            <h2 id="empty-pengajuan-title" class="sipkl-card-title">Belum ada pengajuan</h2>
            <p>Mulai dengan menyiapkan data pribadi, rencana kegiatan, dan dokumen persyaratan untuk pengajuan PKL, magang, atau penelitian.</p>
            <a href="{{ \App\Filament\Peserta\Resources\PengajuanResource::getUrl('create') }}" class="sipkl-primary-action">
                Buat Pengajuan
            </a>
        </section>
    @else
        <section aria-labelledby="ringkasan-pengajuan-title">
            <div class="sipkl-section-heading">
                <div>
                    <p class="sipkl-eyebrow">Pengajuan terbaru</p>
                    <h2 id="ringkasan-pengajuan-title" class="sipkl-section-title">Ringkasan Pengajuan</h2>
                </div>
                @if ($this->getDetailUrl())
                    <a href="{{ $this->getDetailUrl() }}" class="sipkl-text-link">Lihat detail</a>
                @endif
            </div>

            <div class="sipkl-stat-grid">
                <article class="sipkl-stat-card sipkl-stat-card--status">
                    <div class="sipkl-stat-icon" aria-hidden="true"><x-heroicon-o-document-text /></div>
                    <p class="sipkl-stat-label">Status Pengajuan</p>
                    <p class="sipkl-stat-value">{{ $this->labelStatus($statusPengajuan) }}</p>
                    <p class="sipkl-stat-desc">{{ $this->descriptionStatus($statusPengajuan) }}</p>
                </article>
                <article class="sipkl-stat-card">
                    <div class="sipkl-stat-icon" aria-hidden="true"><x-heroicon-o-calendar-days /></div>
                    <p class="sipkl-stat-label">Periode</p>
                    <p class="sipkl-stat-value sipkl-stat-value--compact">
                        {{ optional($pengajuan->tanggal_mulai)->translatedFormat('d M Y') ?: 'Belum ditentukan' }}
                        <span aria-hidden="true">—</span>
                        {{ optional($pengajuan->tanggal_selesai)->translatedFormat('d M Y') ?: 'Belum ditentukan' }}
                    </p>
                </article>
                <article class="sipkl-stat-card">
                    <div class="sipkl-stat-icon" aria-hidden="true"><x-heroicon-o-folder /></div>
                    <p class="sipkl-stat-label">Dokumen Terverifikasi</p>
                    <p class="sipkl-stat-value">{{ $dokumenLengkap }} dari {{ $totalDokumen }}</p>
                    <p class="sipkl-stat-desc">
                        {{ $totalDokumen === 0 ? 'Belum ada dokumen yang tercatat.' : ($dokumenLengkap === $totalDokumen ? 'Semua dokumen telah dinyatakan lengkap.' : 'Masih ada dokumen yang menunggu verifikasi atau perbaikan.') }}
                    </p>
                </article>
                <article class="sipkl-stat-card">
                    <div class="sipkl-stat-icon" aria-hidden="true"><x-heroicon-o-building-office-2 /></div>
                    <p class="sipkl-stat-label">Bagian Penempatan</p>
                    <p class="sipkl-stat-value sipkl-stat-value--compact">{{ $pengajuan->bagian?->nama_bagian ?? 'Belum ditentukan' }}</p>
                </article>
            </div>
        </section>

        <div class="sipkl-two-col">
            <section class="sipkl-card" aria-labelledby="progress-title">
                <div class="sipkl-card-heading-row">
                    <div>
                        <p class="sipkl-eyebrow">Tahapan proses</p>
                        <h2 id="progress-title" class="sipkl-card-title">Perkembangan Pengajuan</h2>
                    </div>
                    <span class="sipkl-card-hint">Geser untuk melihat seluruh tahap</span>
                </div>
                <ol class="sipkl-stepper" aria-label="Tahapan pengajuan">
                    @foreach ($steps as $step)
                        @php
                            $state = $step['state'] ?? 'belum_diproses';
                            $stateLabel = $stateLabels[$state] ?? 'Belum diproses';
                        @endphp
                        <li class="sipkl-stepper-step is-{{ $state }}" @if($state === 'sedang_diproses') aria-current="step" @endif>
                            <div class="sipkl-stepper-line-wrap" aria-hidden="true">
                                <div class="sipkl-stepper-line {{ $loop->first ? 'is-transparent' : '' }} {{ $state === 'selesai' ? 'is-done' : '' }}"></div>
                                <div class="sipkl-stepper-dot">
                                    @if ($state === 'selesai')
                                        <x-heroicon-o-check class="sipkl-stepper-check" />
                                    @elseif ($state === 'sedang_diproses')
                                        <span class="sipkl-stepper-marker">•</span>
                                    @elseif ($state === 'ditolak')
                                        <span class="sipkl-stepper-marker">!</span>
                                    @endif
                                </div>
                                <div class="sipkl-stepper-line {{ $loop->last ? 'is-transparent' : '' }} {{ $state === 'selesai' ? 'is-done' : '' }}"></div>
                            </div>
                            <span class="sipkl-stepper-label">{{ $step['label'] }}</span>
                            <span class="sipkl-stepper-state">{{ $stateLabel }}</span>
                            @if (! empty($step['tanggal']))
                                <span class="sipkl-stepper-date">{{ $step['tanggal'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="sipkl-card" aria-labelledby="quick-actions-title">
                <p class="sipkl-eyebrow">Tautan utama</p>
                <h2 id="quick-actions-title" class="sipkl-card-title">Aksi Cepat</h2>
                <div class="sipkl-quick-actions">
                    <a href="{{ \App\Filament\Peserta\Resources\PengajuanResource::getUrl('create') }}" class="sipkl-quick-action">
                        <x-heroicon-o-plus-circle aria-hidden="true" />
                        <span><strong>Buat pengajuan baru</strong><small>Mulai pengajuan PKL, magang, atau penelitian.</small></span>
                    </a>
                    <a href="{{ $this->getDetailUrl() }}" class="sipkl-quick-action">
                        <x-heroicon-o-document-magnifying-glass aria-hidden="true" />
                        <span><strong>Lihat detail pengajuan</strong><small>Periksa data, tahapan, dan dokumen terkait.</small></span>
                    </a>
                    @if ($this->canEdit())
                        <a href="{{ $this->getEditUrl() }}" class="sipkl-quick-action">
                            <x-heroicon-o-pencil-square aria-hidden="true" />
                            <span><strong>Lengkapi atau perbaiki pengajuan</strong><small>Ubah data dan dokumen yang masih dapat diedit.</small></span>
                        </a>
                    @endif
                    @if ($this->getSuratBalasanUrl())
                        <a href="{{ $this->getSuratBalasanUrl() }}" target="_blank" rel="noopener" class="sipkl-quick-action">
                            <x-heroicon-o-arrow-down-tray aria-hidden="true" />
                            <span><strong>Unduh surat balasan</strong><small>Dibuka di tab baru.</small></span>
                        </a>
                    @endif
                    @if ($this->getSuratKeteranganUrl())
                        <a href="{{ $this->getSuratKeteranganUrl() }}" target="_blank" rel="noopener" class="sipkl-quick-action">
                            <x-heroicon-o-arrow-down-tray aria-hidden="true" />
                            <span><strong>{{ $this->getSuratKeteranganLabel() }}</strong><small>Dibuka di tab baru.</small></span>
                        </a>
                    @endif
                </div>
            </section>
        </div>

        <section class="sipkl-card" aria-labelledby="latest-info-title">
            <div class="sipkl-card-heading-row">
                <div>
                    <p class="sipkl-eyebrow">Pembaruan</p>
                    <h2 id="latest-info-title" class="sipkl-card-title">Informasi Terbaru</h2>
                </div>
                <a href="{{ \App\Filament\Peserta\Pages\NotifikasiSaya::getUrl() }}" class="sipkl-text-link">Lihat semua</a>
            </div>
            @if (empty($notifikasi))
                <p class="sipkl-muted">Belum ada pemberitahuan baru.</p>
            @else
                <div class="sipkl-info-list">
                    @foreach (array_slice($notifikasi, 0, 5) as $notif)
                        <article class="sipkl-info-item">
                            <div class="sipkl-info-icon" aria-hidden="true"><x-heroicon-o-bell /></div>
                            <div>
                                <p class="sipkl-info-text">{{ $notif['pesan'] ?? $notif['judul'] ?? 'Pembaruan pengajuan' }}</p>
                                <time class="sipkl-info-time" datetime="{{ \Carbon\Carbon::parse($notif['created_at'])->toIso8601String() }}">
                                    {{ \Carbon\Carbon::parse($notif['created_at'])->translatedFormat('d M Y') }} · {{ \Carbon\Carbon::parse($notif['created_at'])->format('H:i') }} WIB
                                </time>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($isDisetujui)
            <section class="sipkl-card sipkl-success-card" aria-labelledby="approved-title">
                <div class="sipkl-success-icon" aria-hidden="true"><x-heroicon-o-check-circle /></div>
                <h2 id="approved-title" class="sipkl-success-title">
                    {{ $statusPengajuan === 'selesai' ? 'Proses Pengajuan Selesai' : ($statusPengajuan === 'berjalan' ? 'Kegiatan Sedang Berjalan' : 'Pengajuan Disetujui') }}
                </h2>
                <p class="sipkl-success-desc">{{ $this->descriptionStatus($statusPengajuan) }}</p>
                <a href="{{ $this->getDetailUrl() }}" class="sipkl-primary-action">Lihat Detail Pengajuan</a>
            </section>
        @endif
    @endif

    <footer class="sipkl-footer">
        <span>Sistem Magang & Penelitian</span><br>
        PT Rajawali I Unit PG Krebet Baru · © {{ now()->year }}
    </footer>
</x-filament-panels::page>
