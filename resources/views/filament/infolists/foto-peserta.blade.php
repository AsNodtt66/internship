@php
    $dokumen = $getRecord()->dokumenPersyaratans()->where('jenis_dokumen', 'Pas Foto 3x4')->first();
    $ekstensi = $dokumen ? strtolower(pathinfo($dokumen->file_path, PATHINFO_EXTENSION)) : null;
    $isGambar = in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp']);
@endphp

@if ($dokumen)
    @if ($isGambar)
        <img
            src="{{ route('documents.persyaratan', $dokumen) }}"
            alt="Pas Foto Peserta"
            style="width:110px;height:140px;object-fit:cover;border-radius:8px;border:1px solid #E5E7EB;"
        >
    @else
        <a
            href="{{ route('documents.persyaratan', $dokumen) }}"
            target="_blank"
            style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:110px;height:140px;border:1px dashed #D1D5DB;border-radius:8px;text-decoration:none;color:#6B7280;font-size:11px;text-align:center;gap:6px;"
        >
            <x-heroicon-o-document style="width:28px;height:28px;" />
            Lihat File<br>(belum berupa foto)
        </a>
    @endif
@endif
