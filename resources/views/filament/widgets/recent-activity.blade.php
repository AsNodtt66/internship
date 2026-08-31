<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Widget Aktivitas Terbaru</x-slot>

        @php $aktivitas = $this->getAktivitas(); @endphp

        @if (empty($aktivitas))
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-500/10">
                    <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-8 w-8 text-primary-500" />
                </div>
                <p class="text-sm font-medium text-gray-950 dark:text-white">Aktivitas Kosong Hari Ini</p>
            </div>
        @else
            <ul class="space-y-4">
                @foreach ($aktivitas as $item)
                    <li class="flex items-start gap-2">
                        <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-primary-500"></span>
                        <div class="min-w-0">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium text-gray-950 dark:text-white">{{ $item['peserta'] }}</span>
                                — {{ $item['keterangan'] }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $item['waktu']?->diffForHumans() }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
