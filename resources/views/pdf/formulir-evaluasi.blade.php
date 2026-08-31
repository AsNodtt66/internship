<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Formulir Evaluasi PKL - {{ $pengajuan->peserta?->user?->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 16px; color: #15803D; margin-bottom: 2px; text-align: center; }
        .sub { color: #6B7280; margin-bottom: 20px; text-align: center; }
        .bio td { padding: 4px 4px; }
        .bio td.label { color: #6B7280; width: 160px; }
        table.nilai { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.nilai th, table.nilai td { border: 1px solid #9CA3AF; padding: 8px; }
        table.nilai th { background: #DCFCE7; color: #15803D; text-align: left; }
        table.nilai td.no { width: 30px; text-align: center; }
        table.nilai td.nilai-kosong { width: 90px; }
        .catatan-nilai { color: #6B7280; font-size: 10px; margin-top: 6px; }
        .ttd { margin-top: 50px; width: 100%; }
        .ttd td { width: 50%; vertical-align: top; padding-top: 60px; text-align: center; border: none; }
    </style>
</head>
<body>
    <h1>Formulir Evaluasi PKL/Penelitian</h1>
    <div class="sub">PT Rajawali I Unit PG Krebet Baru — Internship Management System</div>

    <table class="bio">
        <tr><td class="label">Nomor Agenda</td><td>: {{ $pengajuan->nomor_agenda ?? '-' }}</td></tr>
        <tr><td class="label">Nama Peserta</td><td>: {{ $pengajuan->peserta?->user?->name }}</td></tr>
        <tr><td class="label">NIM / Universitas</td><td>: {{ $pengajuan->peserta?->nim }} / {{ $pengajuan->peserta?->universitas }}</td></tr>
        <tr><td class="label">Bagian Penempatan</td><td>: {{ $pengajuan->bagianTujuan?->nama_bagian }}</td></tr>
        <tr><td class="label">Periode PKL</td><td>: {{ optional($pengajuan->tanggal_mulai)->format('d M Y') }} — {{ optional($pengajuan->tanggal_selesai)->format('d M Y') }}</td></tr>
        <tr><td class="label">Pembimbing Lapangan</td><td>: {{ $pengajuan->penugasanPembimbing?->pembimbing?->name ?? '-' }}</td></tr>
    </table>

    <table class="nilai">
        <thead>
            <tr>
                <th class="no">No</th>
                <th>Unsur Yang Dinilai</th>
                <th class="nilai-kosong">Nilai*</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($aspekList as $i => $aspek)
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td>{{ $aspek }}</td>
                    <td></td>
                </tr>
            @endforeach
            <tr>
                <td class="no"></td>
                <td><strong>Jumlah</strong></td>
                <td></td>
            </tr>
            <tr>
                <td class="no"></td>
                <td><strong>Rata-rata</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <div class="catatan-nilai">*Nilai diisi angka 0 sampai dengan 100</div>

    <table class="ttd">
        <tr>
            <td></td>
            <td>
                Malang, {{ now()->translatedFormat('d F Y') }}<br>
                Pembimbing Lapangan<br><br><br><br>
                ( {{ $pengajuan->penugasanPembimbing?->pembimbing?->name ?? '.......................' }} )
            </td>
        </tr>
    </table>
</body>
</html>
