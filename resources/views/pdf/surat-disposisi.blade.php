<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Disposisi</title>
    <style>
        @page { margin: 25px 40px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #1a1a1a; }

        /* ==== KOP SURAT (placeholder, ganti dengan logo asli nanti) ==== */
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop .nama-perusahaan { font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .kop .unit { font-size: 14px; font-weight: bold; margin: 2px 0; }
        .kop .alamat { font-size: 10px; color: #444; margin: 2px 0; }

        .judul { text-align: center; margin-bottom: 20px; }
        .judul h2 { text-decoration: underline; margin: 0; font-size: 14px; text-transform: uppercase; }
        .judul .nomor { font-size: 11px; margin-top: 3px; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.data td { padding: 3px 6px; vertical-align: top; font-size: 12px; }
        table.data td.label { width: 160px; }
        table.data td.titik { width: 12px; }

        .isi { text-align: justify; line-height: 1.6; margin-bottom: 24px; }

        .status-box {
            border: 1px solid #000;
            padding: 10px 14px;
            margin-bottom: 24px;
            background-color: {{ $step->status === 'ditolak' ? '#fdecea' : '#eefaf0' }};
        }
        .status-box .label-status {
            font-weight: bold;
            text-transform: uppercase;
            color: {{ $step->status === 'ditolak' ? '#b3261e' : '#1e7e34' }};
        }

        .ttd-block { width: 260px; margin-left: auto; text-align: center; margin-top: 10px; }
        .ttd-block .kota-tanggal { margin-bottom: 55px; }
        .ttd-block .nama { font-weight: bold; text-decoration: underline; margin-top: 4px; }
        .ttd-block .jabatan { font-size: 11px; color: #333; }
        .ttd-note {
            font-size: 10px;
            font-style: italic;
            color: #555;
            border: 1px dashed #999;
            padding: 6px 10px;
            margin-top: 8px;
        }

        .footer-note { margin-top: 30px; font-size: 9.5px; color: #777; text-align: center; }
    </style>
</head>
<body>

    {{-- ============ KOP SURAT (PLACEHOLDER) ============ --}}
    <div class="kop">
        <p class="nama-perusahaan">PT PG Rajawali I</p>
        <p class="unit">Unit PG Krebet Baru</p>
        <p class="alamat">Jl. Raya Krebet, Bululawang, Malang, Jawa Timur &mdash; Telp. (0341) xxxxxxx</p>
    </div>

    <div class="judul">
        <h2>Surat Disposisi Persetujuan PKL / Penelitian</h2>
        <div class="nomor">Nomor Agenda: {{ $pengajuan->nomor_agenda ?? '-' }}</div>
    </div>

    <table class="data">
        <tr>
            <td class="label">Nama Peserta</td>
            <td class="titik">:</td>
            <td>{{ $pengajuan->peserta->user->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Universitas / Instansi</td>
            <td class="titik">:</td>
            <td>{{ $pengajuan->peserta->universitas ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Pengajuan</td>
            <td class="titik">:</td>
            <td>{{ $pengajuan->jenis_pengajuan }}</td>
        </tr>
        <tr>
            <td class="label">Bagian / Unit Tujuan</td>
            <td class="titik">:</td>
            <td>{{ $pengajuan->bagianTujuan->nama_bagian ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Periode</td>
            <td class="titik">:</td>
            <td>
                {{ optional($pengajuan->tanggal_mulai)->format('d M Y') }}
                s/d
                {{ optional($pengajuan->tanggal_selesai)->format('d M Y') }}
            </td>
        </tr>
        <tr>
            <td class="label">Tahap Disposisi</td>
            <td class="titik">:</td>
            <td>{{ $tahapKe }} dari {{ $totalTahap }} &mdash; {{ $jabatanPenandatangan }}</td>
        </tr>
    </table>

    <div class="isi">
        Sehubungan dengan pengajuan PKL/Penelitian tersebut di atas, dengan ini disampaikan bahwa
        tahap disposisi <strong>{{ $jabatanPenandatangan }}</strong> telah diproses dengan hasil sebagai berikut.
    </div>

    <div class="status-box">
        <div class="label-status">
            {{ $step->status === 'ditolak' ? 'DITOLAK' : 'DISETUJUI / DIKETAHUI DAN DITANDATANGANI' }}
        </div>
        @if($step->catatan)
            <div style="margin-top: 6px;">Catatan: {{ $step->catatan }}</div>
        @endif
    </div>

    <div class="ttd-block">
        <div class="kota-tanggal">
            Krebet Baru, {{ $step->diproses_at?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}
        </div>
        <div class="nama">{{ $penandatangan->name ?? '-' }}</div>
        <div class="jabatan">{{ $jabatanPenandatangan }}</div>

        <div class="ttd-note">
            Ditandatangani secara elektronik oleh {{ $penandatangan->name ?? '-' }},
            {{ $step->diproses_at?->translatedFormat('d F Y, H:i') ?? now()->translatedFormat('d F Y, H:i') }} WIB.
            Dokumen ini sah tanpa memerlukan tanda tangan basah.
        </div>
    </div>

    <div class="footer-note">
        Dokumen ini dihasilkan otomatis oleh Sistem Informasi PKL &amp; Penelitian PT PG Rajawali I Unit PG Krebet Baru.
    </div>

</body>
</html>