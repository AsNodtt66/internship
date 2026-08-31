<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Lembar Disposisi - {{ $labelTahap }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 16px; color: #15803D; margin-bottom: 2px; }
        .sub { color: #6B7280; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        td { padding: 6px 4px; border-bottom: 1px solid #E5E7EB; }
        td.label { color: #6B7280; width: 180px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; background: #DCFCE7; color: #15803D; font-weight: bold; }
        .catatan { margin-top: 16px; padding: 10px; background: #F8FAFC; border-radius: 6px; }
    </style>
</head>
<body>
    <h1>Lembar Disposisi — {{ $labelTahap }}</h1>
    <div class="sub">PT Rajawali I Unit PG Krebet Baru — Internship Management System</div>

    <table>
        <tr><td class="label">Nomor Agenda</td><td>{{ $disposisi->pengajuan->nomor_agenda ?? '-' }}</td></tr>
        <tr><td class="label">Nama Peserta</td><td>{{ $disposisi->pengajuan->peserta?->user?->name }}</td></tr>
        <tr><td class="label">Bagian Tujuan</td><td>{{ $disposisi->pengajuan->bagianTujuan?->nama_bagian }}</td></tr>
        <tr><td class="label">Tahap Disposisi</td><td>{{ $labelTahap }}</td></tr>
        <tr><td class="label">Status</td><td><span class="badge">Ditandatangani</span></td></tr>
        <tr><td class="label">Ditandatangani Oleh</td><td>{{ $disposisi->penandatangan?->name }}</td></tr>
        <tr><td class="label">Tanggal & Waktu</td><td>{{ optional($disposisi->diproses_at)->translatedFormat('d M Y • H:i') }} WIB</td></tr>
    </table>

    @if ($disposisi->catatan)
        <div class="catatan">
            <strong>Catatan:</strong><br>
            {{ $disposisi->catatan }}
        </div>
    @endif

    <div class="sub" style="margin-top:24px;">Dicetak otomatis oleh sistem pada {{ now()->translatedFormat('d M Y H:i') }} WIB</div>
</body>
</html>
