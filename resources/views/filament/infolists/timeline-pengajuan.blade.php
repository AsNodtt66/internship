@php
    $riwayat = $getRecord()->riwayatStatus()->orderBy('created_at')->get();
@endphp

<ol class="sipkl-timeline">
    @foreach ($riwayat as $item)
        <li class="sipkl-timeline-item">
            <span class="sipkl-timeline-dot">
                <x-heroicon-o-check class="sipkl-timeline-check" />
            </span>
            <div>
                <p class="sipkl-timeline-title">
                    {{ $item->keterangan ?: str($item->status_baru)->headline() }}
                </p>
                <p class="sipkl-timeline-time">
                    {{ $item->created_at->translatedFormat('d M Y • H:i') }} WIB
                </p>
            </div>
        </li>
    @endforeach
</ol>
