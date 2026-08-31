@php
    $evaluasi = $getRecord()->evaluasi;
    $aspekList = $evaluasi->formulirPenilaians()->get();
@endphp

<div>
    @if ($aspekList->isNotEmpty())
        <table class="sipkl-table" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th>Aspek Penilaian</th>
                    <th>Skor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($aspekList as $item)
                    <tr>
                        <td>{{ $item->aspek_penilaian }}</td>
                        <td>{{ $item->skor }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div style="display:flex;gap:32px;align-items:center;flex-wrap:wrap;">
        <div>
            <div style="font-size:12px;color:#6B7280;">Nilai Akhir (rata-rata)</div>
            <div style="font-size:24px;font-weight:700;color:#15803D;">{{ $evaluasi->nilai_akhir }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:#6B7280;">Hasil</div>
            <span class="sipkl-badge {{ $evaluasi->hasil === 'selesai' ? 'sipkl-badge-success' : 'sipkl-badge-warning' }}">
                {{ $evaluasi->hasil === 'selesai' ? 'Selesai' : 'Perlu Perpanjangan' }}
            </span>
        </div>
        <div>
            <div style="font-size:12px;color:#6B7280;">Dinilai Oleh</div>
            <div>{{ $evaluasi->pembimbing?->name ?? $evaluasi->dinilaiOleh?->name }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:#6B7280;">Tanggal Dinilai</div>
            <div>{{ optional($evaluasi->dinilai_at)->translatedFormat('d M Y • H:i') }}</div>
        </div>
        @if ($evaluasi->file_bukti)
            <div>
                <div style="font-size:12px;color:#6B7280;">Bukti Formulir Fisik</div>
                <a href="{{ route('documents.evaluasi', $evaluasi) }}" target="_blank" style="color:#1B5A96;font-weight:600;">Lihat File</a>
            </div>
        @endif
    </div>

    @if ($evaluasi->catatan)
        <div style="margin-top:12px;padding:10px;background:#F8FAFC;border-radius:8px;">
            <strong>Catatan Pembimbing:</strong><br>
            {{ $evaluasi->catatan }}
        </div>
    @endif
</div>