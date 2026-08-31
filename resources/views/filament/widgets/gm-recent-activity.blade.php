<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Aktivitas Terbaru</x-slot>
        <x-slot name="description">Perubahan status pengajuan paling baru</x-slot>

        <div class="flow-root">
            <ul class="-mb-6">
                @forelse ($this->getAktivitas() as $i => $item)
                    <li>
                        <div class="relative pb-6">
                            @if (! $loop->last)
                                <span class="absolute left-[7px] top-3 -ml-px h-full w-0.5 bg-gray-100 dark:bg-gray-800"></span>
                            @endif

                            <div class="relative flex items-start gap-3">
                                <span @class([
                                    'mt-1.5 h-3.5 w-3.5 flex-shrink-0 rounded-full ring-4 ring-white dark:ring-gray-900',
                                    'bg-danger-500' => $item['warna'] === 'danger',
                                    'bg-success-500' => $item['warna'] === 'success',
                                    'bg-warning-500' => $item['warna'] === 'warning',
                                    'bg-gray-400' => $item['warna'] === 'gray',
                                ])></span>

                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium text-gray-950 dark:text-white">{{ $item['peserta'] }}</span>
                                        — {{ $item['keterangan'] }}
                                        @if ($item['pelaku'])
                                            <span class="text-gray-400">(oleh {{ $item['pelaku'] }})</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-400">
                                        {{ $item['waktu']?->translatedFormat('d M Y, H:i') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <p class="text-sm text-gray-500">Belum ada aktivitas terbaru.</p>
                @endforelse
            </ul>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
