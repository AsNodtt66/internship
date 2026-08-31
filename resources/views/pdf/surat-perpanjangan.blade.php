<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Perpanjangan PKL</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #111827; line-height: 1.6; }
        .kop { text-align: center; border-bottom: 3px solid #15803D; padding-bottom: 10px; margin-bottom: 24px; }
        .kop h1 { font-size: 15px; color: #15803D; margin: 0; }
        .kop p { margin: 2px 0; color: #6B7280; font-size: 11px; }
        .judul { text-align: center; margin-bottom: 24px; }
        .judul h2 { font-size: 14px; text-decoration: underline; margin: 0 0 4px; }
        .judul p { margin: 0; font-size: 11px; }
        table.data { width: 100%; margin: 16px 0; }
        table.data td { padding: 3px 4px; vertical-align: top; }
        table.data td.label { width: 180px; }
        .isi { text-align: justify; margin: 20px 0; }
        .ttd { margin-top: 60px; width: 100%; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>PT RAJAWALI I UNIT PG KREBET BARU</h1>
        <p>Internship Management System</p>
    </div>

    <div class="judul">
        <h2>SURAT PERPANJANGAN PKL/PENELITIAN</h2>
        <p>Nomor: {{ $nomorSurat }}</p>
    </div>

    <div class="isi">
        Sehubungan dengan hasil evaluasi Praktik Kerja Lapangan/Penelitian yang belum memenuhi standar,
        dengan ini disampaikan perpanjangan masa PKL/Penelitian untuk:
    </div>

    <table class="data">
        <tr><td class="label">Nama</td><td>: {{ $pengajuan->peserta?->user?->name }}</td></tr>
        <tr><td class="label">NIM</td><td>: {{ $pengajuan->peserta?->nim }}</td></tr>
        <tr><td class="label">Universitas</td><td>: {{ $pengajuan->peserta?->universitas }}</td></tr>
        <tr><td class="label">Bagian Penempatan</td><td>: {{ $pengajuan->bagianTujuan?->nama_bagian }}</td></tr>
        <tr><td class="label">Periode Sebelumnya</td><td>: {{ optional($pengajuan->tanggal_mulai)->format('d M Y') }} s/d {{ optional($pengajuan->tanggal_selesai)->format('d M Y') }}</td></tr>
        <tr><td class="label">Periode Perpanjangan</td><td>: {{ optional($perpanjangan->tanggal_mulai_baru)->format('d M Y') }} s/d {{ optional($perpanjangan->tanggal_selesai_baru)->format('d M Y') }}</td></tr>
        <tr><td class="label">Alasan</td><td>: {{ $perpanjangan->alasan }}</td></tr>
    </table>

    <div class="isi">
        Perpanjangan ini telah disetujui oleh Kepala Bagian Tujuan. Demikian surat ini dibuat untuk
        dipergunakan sebagaimana mestinya.
    </div>

    <table class="ttd">
        <tr>
            <td></td>
            <td>
                Malang, {{ now()->translatedFormat('d F Y') }}<br>
                PIC PKL<br><br><br><br>
                ( {{ Auth::user()->name }} )
            </td>
        </tr>
    </table>
</body>
</html>
