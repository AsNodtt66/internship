@php
    $penilaian = $getRecord()->penilaian;
    $labelKeputusan = match ($penilaian->keputusan) {
        'perpanjang' => 'Perpanjang',
        'tidak_perpanjang' => 'Tidak Perpanjang',
        default => 'Belum dipilih peserta',
    };
    $warnaKeputusan = match ($penilaian->keputusan) {
        'perpanjang' => 'sipkl-badge-warning',
        'tidak_perpanjang' => 'sipkl-badge-success',
        default => '',
    };
@endphp

<div style="display:flex;gap:32px;align-items:center;flex-wrap:wrap;">
    <div>
        <div style="font-size:12px;color:#6B7280;">File PDF Penilaian</div>
        <a href="{{ route('documents.penilaian', $penilaian) }}" target="_blank" rel="noopener" style="color:#1B5A96;font-weight:600;">
            Buka atau unduh PDF
        </a>
    </div>
    <div>
        <div style="font-size:12px;color:#6B7280;">Diunggah Oleh</div>
        <div>{{ $penilaian->diuploadOleh?->name ?? '-' }}</div>
    </div>
    <div>
        <div style="font-size:12px;color:#6B7280;">Tanggal Unggah</div>
        <div>{{ optional($penilaian->diupload_at)->translatedFormat('d M Y • H:i') ?? '-' }}</div>
    </div>
    <div>
        <div style="font-size:12px;color:#6B7280;">Keputusan Peserta</div>
        <span class="sipkl-badge {{ $warnaKeputusan }}" @if(! $warnaKeputusan) style="background:#F3F4F6;color:#6B7280;" @endif>{{ $labelKeputusan }}</span>
    </div>
</div>
