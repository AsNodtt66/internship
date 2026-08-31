@php
    $record = $getRecord();
    $pesan = match ($record->status) {
        'selesai' => 'Selamat! PKL/Penelitian Anda telah dinyatakan selesai.',
        'berjalan' => 'Selamat! Pengajuan PKL/Magang Anda telah disetujui dan sedang berjalan.',
        default => 'Selamat! Pengajuan PKL/Magang Anda telah disetujui.',
    };
@endphp

<div class="sipkl-success-card">
    <div class="sipkl-success-icon">
        <x-heroicon-o-check-circle />
    </div>
    <p class="sipkl-success-title">Pengajuan Anda Disetujui!</p>
    <p class="sipkl-success-desc">{{ $pesan }}</p>
    <a
        href="{{ route('pengajuan.cetak-ringkasan', $record) }}"
        target="_blank"
        class="sipkl-success-btn"
    >
        Cetak Ringkasan
    </a>
</div>
