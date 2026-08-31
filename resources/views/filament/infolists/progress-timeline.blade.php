@php
    $record = $getRecord();
    $steps = app(\App\Services\PengajuanTimelineService::class)->build($record);
    $colorMap = [
        'selesai' => ['dot' => 'bg-success-500', 'line' => 'bg-success-500', 'text' => 'text-success-600'],
        'sedang_diproses' => ['dot' => 'bg-warning-500 animate-pulse', 'line' => 'bg-gray-300 dark:bg-gray-600', 'text' => 'text-warning-600 font-semibold'],
        'belum_diproses' => ['dot' => 'bg-gray-300 dark:bg-gray-600', 'line' => 'bg-gray-300 dark:bg-gray-600', 'text' => 'text-gray-400'],
        'ditolak' => ['dot' => 'bg-danger-500', 'line' => 'bg-danger-500', 'text' => 'text-danger-600 font-semibold'],
    ];
@endphp

@if (! empty($steps))
    <div class="overflow-x-auto pb-1">
        {{-- Desktop / tablet lebar: horizontal --}}
        <div class="hidden md:flex items-start min-w-[720px]">
            @foreach ($steps as $key => $step)
                @php $c = $colorMap[$step['state']]; @endphp
                <div class="flex flex-col items-center flex-1 relative">
                    <div class="flex items-center w-full">
                        <div class="flex-1 h-0.5 {{ $loop->first ? 'bg-transparent' : $colorMap[$steps[array_keys($steps)[$loop->index - 1]]['state']]['line'] }}"></div>
                        <div class="w-4 h-4 rounded-full {{ $c['dot'] }} shrink-0 flex items-center justify-center">
                            @if ($step['state'] === 'selesai')
                                <x-heroicon-s-check class="w-2.5 h-2.5 text-white" />
                            @endif
                        </div>
                        <div class="flex-1 h-0.5 {{ $loop->last ? 'bg-transparent' : $c['line'] }}"></div>
                    </div>
                    <span class="mt-2 text-xs text-center {{ $c['text'] }}">{{ $step['label'] }}</span>
                    <span class="text-[10px] text-gray-400">
                        {{ match ($step['state']) {
                            'selesai' => 'Selesai',
                            'sedang_diproses' => 'Sedang Diproses',
                            'ditolak' => 'Ditolak',
                            default => 'Belum Diproses',
                        } }}
                    </span>
                </div>
            @endforeach
        </div>

        {{-- Mobile: vertical --}}
        <div class="md:hidden space-y-0">
            @foreach ($steps as $key => $step)
                @php $c = $colorMap[$step['state']]; @endphp
                <div class="flex gap-3">
                    <div class="flex flex-col items-center">
                        <div class="w-3 h-3 rounded-full {{ $c['dot'] }} shrink-0"></div>
                        @if (! $loop->last)
                            <div class="w-0.5 flex-1 {{ $c['line'] }} min-h-[24px]"></div>
                        @endif
                    </div>
                    <div class="pb-4">
                        <p class="text-sm {{ $c['text'] }}">{{ $step['label'] }}</p>
                        <p class="text-xs text-gray-400">
                            {{ match ($step['state']) {
                                'selesai' => 'Selesai',
                                'sedang_diproses' => 'Sedang Diproses',
                                'ditolak' => 'Ditolak',
                                default => 'Belum Diproses',
                            } }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
