<x-filament-widgets::widget>
    <x-filament::section heading="Aksi Cepat">
        @php
            $pengajuan = $this->getPengajuan();
        @endphp

        @if (! $pengajuan)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Belum ada pengajuan yang dapat ditindaklanjuti.
            </p>
        @else
            <div class="flex flex-wrap gap-3">
                <x-filament::button tag="a" :href="$this->getDetailUrl()" icon="heroicon-o-eye" color="primary">
                    Lihat Detail Pengajuan
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Peserta\Pages\DokumenSaya::getUrl()"
                    icon="heroicon-o-folder"
                    color="gray"
                >
                    Status Dokumen
                </x-filament::button>

                @if ($this->getSuratBalasanUrl())
                    <x-filament::button
                        tag="a"
                        :href="$this->getSuratBalasanUrl()"
                        target="_blank"
                        rel="noopener"
                        icon="heroicon-o-arrow-down-tray"
                        color="success"
                    >
                        Unduh Surat Balasan
                    </x-filament::button>
                @endif

                @if ($this->getSuratKeteranganUrl())
                    <x-filament::button
                        tag="a"
                        :href="$this->getSuratKeteranganUrl()"
                        target="_blank"
                        rel="noopener"
                        icon="heroicon-o-arrow-down-tray"
                        color="success"
                    >
                        {{ $this->getSuratKeteranganLabel() }}
                    </x-filament::button>
                @endif

                @if ($this->canEdit())
                    <x-filament::button
                        tag="a"
                        :href="$this->getEditUrl()"
                        icon="heroicon-o-pencil-square"
                        color="warning"
                    >
                        Lengkapi Pengajuan
                    </x-filament::button>
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
