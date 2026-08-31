<x-filament-panels::page>
    @if (! $pengajuan)
        <section class="sipkl-card sipkl-empty-state" aria-labelledby="jadwal-empty-title">
            <div class="sipkl-empty-state-icon" aria-hidden="true">
                <x-heroicon-o-calendar-days />
            </div>
            <h2 id="jadwal-empty-title" class="sipkl-card-title">Belum ada jadwal kegiatan</h2>
            <p class="sipkl-muted">
                Jadwal akan tersedia setelah pengajuan Anda diproses dan periode kegiatan ditetapkan.
            </p>
        </section>
    @else
        <section aria-labelledby="jadwal-summary-title">
            <p class="sipkl-eyebrow">Ringkasan kegiatan</p>
            <h2 id="jadwal-summary-title" class="sipkl-section-title">Jadwal dan pendampingan</h2>
            <p class="sipkl-section-description">
                Gunakan halaman ini untuk memeriksa periode kegiatan, pembimbing lapangan, evaluasi, dan hasil penilaian terbaru.
            </p>

            <div class="sipkl-stat-grid">
                <div class="sipkl-stat-card">
                    <div class="sipkl-stat-icon" aria-hidden="true"><x-heroicon-o-calendar-days /></div>
                    <p class="sipkl-stat-label">Periode Kegiatan</p>
                    <p class="sipkl-stat-value" style="font-size:14px;">
                        {{ optional($pengajuan->tanggal_mulai)->translatedFormat('d M Y') ?? 'Belum ditetapkan' }}
                        —
                        {{ optional($pengajuan->tanggal_selesai)->translatedFormat('d M Y') ?? 'Belum ditetapkan' }}
                    </p>
                </div>
                <div class="sipkl-stat-card">
                    <div class="sipkl-stat-icon" aria-hidden="true"><x-heroicon-o-user-group /></div>
                    <p class="sipkl-stat-label">Pembimbing Lapangan</p>
                    <p class="sipkl-stat-value" style="font-size:14px;">{{ $pengajuan->penugasanPembimbing?->nama_tampil ?? 'Belum ditetapkan' }}</p>
                </div>
                <div class="sipkl-stat-card">
                    <div class="sipkl-stat-icon" aria-hidden="true"><x-heroicon-o-clipboard-document-check /></div>
                    <p class="sipkl-stat-label">Jadwal Evaluasi</p>
                    <p class="sipkl-stat-value" style="font-size:14px;">
                        {{ $pengajuan->evaluasi?->jadwal_evaluasi ? \Carbon\Carbon::parse($pengajuan->evaluasi->jadwal_evaluasi)->translatedFormat('d M Y') : 'Belum dijadwalkan' }}
                    </p>
                </div>
            </div>
        </section>

        @if ($pengajuan->penilaian)
            <section class="sipkl-card" aria-labelledby="penilaian-title">
                <h2 id="penilaian-title" class="sipkl-card-title">Hasil Penilaian</h2>
                <p class="sipkl-muted">
                    Diunggah {{ optional($pengajuan->penilaian->diupload_at)->translatedFormat('d M Y') ?? 'pada waktu yang belum tercatat' }}.
                </p>
                <a
                    href="{{ route('documents.penilaian', $pengajuan->penilaian) }}"
                    target="_blank"
                    rel="noopener"
                    class="sipkl-text-link"
                >
                    Buka atau unduh PDF penilaian
                    <span class="sipkl-sr-only">(dibuka di tab baru)</span>
                </a>

                @if ($pengajuan->penilaian->keputusan === null)
                    <p class="sipkl-muted" style="margin-top:12px;">
                        Keputusan perpanjangan belum dipilih. Buka detail pengajuan untuk menindaklanjuti saat opsi tersedia.
                    </p>
                @else
                    <p class="sipkl-muted" style="margin-top:12px;">
                        Keputusan Anda: <strong>{{ $pengajuan->penilaian->keputusan === 'perpanjang' ? 'Perpanjang kegiatan' : 'Selesaikan tanpa perpanjangan' }}</strong>.
                    </p>
                @endif
            </section>
        @endif

        @if ($pengajuan->evaluasi)
            <section class="sipkl-card" aria-labelledby="evaluasi-title">
                <h2 id="evaluasi-title" class="sipkl-card-title">Hasil Evaluasi</h2>
                @if ($pengajuan->evaluasi->dinilai_at)
                    <p class="sipkl-muted">
                        Nilai akhir: <strong>{{ $pengajuan->evaluasi->nilai_akhir }}</strong>
                        — Hasil: <strong>{{ $pengajuan->evaluasi->hasil === 'selesai' ? 'Selesai' : 'Perlu perpanjangan' }}</strong>.
                    </p>
                    <a href="{{ route('filament.peserta.resources.pengajuans.view', $pengajuan) }}" class="sipkl-text-link">
                        Lihat rincian nilai per aspek
                    </a>
                @elseif (filled($pengajuan->evaluasi->aspek_penilaian_default))
                    <p class="sipkl-muted">Penilaian sedang menunggu Pembimbing Lapangan atau PIC.</p>
                @else
                    <p class="sipkl-muted">Aspek penilaian belum dilengkapi. Buka detail pengajuan untuk melihat tindak lanjut yang tersedia.</p>
                @endif
            </section>
        @endif
    @endif
</x-filament-panels::page>
