<x-filament-widgets::widget>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <x-filament::section>
            <x-slot name="heading">Statistik Berdasarkan Bagian</x-slot>

            <div class="flex flex-col gap-3">
                @forelse ($this->getPerBagian() as $bagian)
                    <div class="grid grid-cols-[110px_1fr_28px] items-center gap-3">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 truncate">{{ $bagian['nama'] }}</span>
                        <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-full rounded-full bg-primary-500" style="width: {{ $bagian['persen'] }}%;"></div>
                        </div>
                        <span class="text-sm font-bold text-right tabular-nums">{{ $bagian['total'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada data pengajuan.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Top 5 Perguruan Tinggi</x-slot>

            <div class="flex flex-col divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($this->getTopUniversitas() as $i => $uni)
                    <div class="flex items-center gap-3 py-2.5 {{ $i === 0 ? 'pt-0' : '' }}">
                        <span class="flex items-center justify-center w-6 h-6 rounded-md bg-primary-50 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400 text-xs font-bold flex-shrink-0">
                            {{ $i + 1 }}
                        </span>
                        <span class="text-sm font-medium flex-1 truncate">{{ $uni['nama'] }}</span>
                        <span class="text-xs text-gray-500">{{ $uni['total'] }} peserta</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada data peserta.</p>
                @endforelse
            </div>
        </x-filament::section>

    </div>
</x-filament-widgets::widget>
