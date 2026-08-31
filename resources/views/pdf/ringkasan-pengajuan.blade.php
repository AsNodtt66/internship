<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Pengajuan {{ $pengajuan->nomor_agenda ?? $pengajuan->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 16px; color: #15803D; margin-bottom: 2px; }
        .sub { color: #6B7280; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        td { padding: 6px 4px; border-bottom: 1px solid #E5E7EB; }
        td.label { color: #6B7280; width: 180px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; background: #DCFCE7; color: #15803D; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Ringkasan Pengajuan PKL/Penelitian</h1>
    <div class="sub">PT Rajawali I Unit PG Krebet Baru — Internship Management System</div>

    <table>
        <tr><td class="label">Nomor Agenda</td><td>{{ $pengajuan->nomor_agenda ?? '-' }}</td></tr>
        <tr><td class="label">Status</td><td><span class="badge">{{ str($pengajuan->status)->headline() }}</span></td></tr>
        <tr><td class="label">Nama Peserta</td><td>{{ $pengajuan->peserta?->user?->name }}</td></tr>
        <tr><td class="label">NIM</td><td>{{ $pengajuan->peserta?->nim }}</td></tr>
        <tr><td class="label">Universitas</td><td>{{ $pengajuan->peserta?->universitas }}</td></tr>
        <tr><td class="label">Jenis Pengajuan</td><td>{{ $pengajuan->jenis_pengajuan }}</td></tr>
        <tr><td class="label">Bagian Tujuan</td><td>{{ $pengajuan->bagianTujuan?->nama_bagian }}</td></tr>
        <tr><td class="label">Periode</td><td>{{ optional($pengajuan->tanggal_mulai)->format('d M Y') }} — {{ optional($pengajuan->tanggal_selesai)->format('d M Y') }}</td></tr>
        <tr><td class="label">Pembimbing Lapangan</td><td>{{ $pengajuan->penugasanPembimbing?->pembimbing?->name ?? '-' }}</td></tr>
    </table>

    <div class="sub">Dicetak pada {{ now()->translatedFormat('d M Y H:i') }} WIB</div>
</body>
</html>
