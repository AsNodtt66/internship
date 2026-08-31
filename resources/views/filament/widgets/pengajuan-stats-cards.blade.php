@php
    // Kelas Tailwind ditulis literal (bukan dirakit dari variabel) supaya
    // selalu ikut ter-compile oleh content scanner Tailwind, tidak peduli
    // konfigurasi content glob-nya. Jangan ganti jadi string dinamis lagi
    // (mis. "bg-{$color}-600") — itu penyebab kartu ini pernah tampil polos
    // tanpa warna/bentuk sama sekali.
    $palet = [
        'amber' => [
            'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
            'accent' => 'border-amber-500',
        ],
        'sky' => [
            'badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400',
            'accent' => 'border-sky-500',
        ],
        'emerald' => [
            'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
            'accent' => 'border-emerald-500',
        ],
    ];
@endphp

<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach ($this->getCards() as $card)
            @php $warna = $palet[$card['color']] ?? $palet['sky']; @endphp
            <div class="flex items-start gap-4 rounded-xl border-l-4 {{ $warna['accent'] }} bg-white p-5 shadow-sm ring-1 ring-gray-950/5 transition hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full {{ $warna['badge'] }}">
                    <x-filament::icon :icon="$card['icon']" class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                    <p class="mt-0.5 text-3xl font-bold leading-tight text-gray-950 dark:text-white">
                        {{ $card['value'] }}
                    </p>
                    <p class="mt-0.5 truncate text-xs text-gray-400 dark:text-gray-500">{{ $card['sublabel'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
