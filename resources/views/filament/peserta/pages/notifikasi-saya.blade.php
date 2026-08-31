<x-filament-panels::page>
    @php
        $items = $this->getNotifikasi();
        $unreadCount = $items->where('is_read', false)->count();
    @endphp

    <section aria-labelledby="notification-title">
        <div class="sipkl-section-heading-row">
            <div>
                <p class="sipkl-eyebrow">Pemberitahuan</p>
                <h2 id="notification-title" class="sipkl-section-title">Informasi terbaru untuk Anda</h2>
                <p class="sipkl-section-description">
                    @if ($unreadCount > 0)
                        Ada {{ $unreadCount }} pemberitahuan yang belum dibaca.
                    @else
                        Semua pemberitahuan terbaru sudah dibaca.
                    @endif
                </p>
            </div>

            @if ($unreadCount > 0)
                <button
                    type="button"
                    wire:click="tandaiSemuaDibaca"
                    class="sipkl-text-link sipkl-button-reset"
                >
                    Tandai semua dibaca
                </button>
            @endif
        </div>

        @if ($items->isEmpty())
            <div class="sipkl-card sipkl-empty-state" role="status">
                <div class="sipkl-empty-state-icon" aria-hidden="true"><x-heroicon-o-bell /></div>
                <h3 class="sipkl-card-title">Belum ada pemberitahuan</h3>
                <p class="sipkl-muted">Pembaruan status pengajuan dan tindak lanjut akan muncul di halaman ini.</p>
            </div>
        @else
            <div class="sipkl-card" aria-live="polite">
                <div class="sipkl-info-list">
                    @foreach ($items as $item)
                        <article class="sipkl-info-item sipkl-notification-item {{ $item->is_read ? 'is-read' : 'is-unread' }}">
                            <div class="sipkl-notification-content">
                                <div class="sipkl-info-icon" aria-hidden="true"><x-heroicon-o-bell /></div>
                                <div>
                                    <h3 class="sipkl-info-text sipkl-notification-title">{{ $item->judul }}</h3>
                                    <p class="sipkl-info-text sipkl-muted">{{ $item->pesan }}</p>
                                    <time class="sipkl-info-time" datetime="{{ $item->created_at->toIso8601String() }}">
                                        {{ $item->created_at->translatedFormat('d M Y • H:i') }}
                                    </time>
                                </div>
                            </div>

                            @if (! $item->is_read)
                                <button
                                    type="button"
                                    wire:click="tandaiDibaca({{ $item->id }})"
                                    class="sipkl-text-link sipkl-button-reset sipkl-notification-read-action"
                                    aria-label="Tandai pemberitahuan {{ $item->judul }} sebagai dibaca"
                                >
                                    Tandai dibaca
                                </button>
                            @else
                                <span class="sipkl-notification-state" aria-label="Sudah dibaca">Sudah dibaca</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</x-filament-panels::page>
