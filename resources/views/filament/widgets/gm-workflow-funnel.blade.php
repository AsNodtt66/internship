<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Status Alur Persetujuan
        </x-slot>
        <x-slot name="description">
            Posisi seluruh pengajuan yang sedang berjalan di tiap tahap disposisi
        </x-slot>

        <div class="flex flex-col gap-3">
            @foreach ($this->getTahapan() as $tahap)
                <div class="grid grid-cols-[130px_1fr_34px] items-center gap-3">
                    <span @class([
                        'text-sm font-medium truncate',
                        'text-warning-600 dark:text-warning-400 font-semibold' => $tahap['aktif'] ?? false,
                        'text-success-600 dark:text-success-400 font-semibold' => $tahap['final'] ?? false,
                        'text-gray-600 dark:text-gray-400' => empty($tahap['aktif']) && empty($tahap['final']),
                    ])>
                        {{ $tahap['label'] }}
                    </span>

                    <div class="h-3.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all"
                            style="width: {{ $tahap['persen'] }}%; background-color: {{ $tahap['warna'] }};"
                        ></div>
                    </div>

                    <span @class([
                        'text-sm font-bold text-right tabular-nums',
                        'text-warning-600 dark:text-warning-400' => $tahap['aktif'] ?? false,
                        'text-success-600 dark:text-success-400' => $tahap['final'] ?? false,
                    ])>
                        {{ $tahap['total'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
