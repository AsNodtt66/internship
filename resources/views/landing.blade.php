<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PKL, Magang, dan Penelitian | PT Rajawali I Unit PG Krebet Baru</title>
    <meta name="description" content="Portal resmi pengajuan PKL, magang, dan penelitian di PT Rajawali I Unit PG Krebet Baru.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:600,700,900|inter:400,500,600,700|ibm-plex-mono:500" rel="stylesheet" />

    @vite(['resources/css/app.css'])

    <style>
        :root{
            --cane:#1B5A96;
            --cane-dark:#0E2C4B;
            --molasses:#8A4A22;
            --gold:#DDA53C;
            --husk:#F6F1E3;
            --husk-light:#FCFAF2;
            --ink:#1D2430;
        }
        html{scroll-behavior:smooth;}
        body{background:var(--husk);color:var(--ink);font-family:'Inter',ui-sans-serif,system-ui,sans-serif;}
        .font-display{font-family:'Fraunces',ui-serif,Georgia,serif; font-optical-sizing:auto;}
        .font-mono{font-family:'IBM Plex Mono',ui-monospace,monospace;}

        /* Subtle sugar-grain texture — used sparingly on hero & CTA sections */
        .grain{
            background-image: radial-gradient(rgba(29,36,48,0.5) 0.6px, transparent 0.6px);
            background-size: 14px 14px;
        }
        .grain-light{
            background-image: radial-gradient(rgba(252,250,242,0.35) 0.6px, transparent 0.6px);
            background-size: 14px 14px;
        }

        /* Perforated production-slip edge — the page's signature motif */
        .ticket{
            position:relative;
            background:var(--husk-light);
            border:1px solid rgba(34,32,26,0.12);
        }
        .ticket::before{
            content:"";
            position:absolute; left:0; right:0; top:0; height:0;
            border-top:2px dashed rgba(34,32,26,0.25);
        }
        .ticket-notch{
            position:absolute; width:22px; height:22px; border-radius:9999px;
            background:var(--husk); border:1px solid rgba(34,32,26,0.12);
            top:50%; transform:translateY(-50%);
        }

        @media (prefers-reduced-motion: no-preference){
            .rise{ opacity:0; transform:translateY(14px); animation:rise .6s ease-out forwards; }
            .rise-1{animation-delay:.05s} .rise-2{animation-delay:.15s} .rise-3{animation-delay:.25s}
            @keyframes rise{to{opacity:1; transform:translateY(0);}}
        }

        a:focus-visible, button:focus-visible, summary:focus-visible{
            outline:3px solid var(--cane-dark); outline-offset:3px;
        }
        .skip-link{position:fixed;left:1rem;top:1rem;z-index:100;transform:translateY(-180%);background:var(--husk-light);color:var(--cane-dark);padding:.75rem 1rem;border-radius:.5rem;font-weight:700;box-shadow:0 8px 24px rgba(14,44,75,.2);}
        .skip-link:focus{transform:translateY(0);}
        .on-dark:focus-visible{outline-color:#FDE68A;}
        @media (prefers-reduced-motion: reduce){html{scroll-behavior:auto;} .rise{opacity:1!important;transform:none!important;animation:none!important;}}

        .line-path{
            background-image: repeating-linear-gradient(90deg, rgba(31,61,43,0.35) 0 10px, transparent 10px 20px);
            height:2px;
        }
    </style>
</head>
<body class="min-h-screen">
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>

    {{-- ============ NAV ============ --}}
    <header class="sticky top-0 z-40 border-b-2 border-[var(--gold)]/70 bg-[var(--husk)]/90 backdrop-blur">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center" aria-label="Beranda portal magang">
                <img src="{{ asset('images/logo-rajawali.png') }}" alt="Logo PT Rajawali I - Unit PG Krebet Baru" class="h-11 w-auto">
            </a>

            <nav aria-label="Navigasi utama" class="hidden md:flex items-center gap-8 text-sm font-medium text-[var(--ink)]/80">
                <a href="#program" class="hover:text-[var(--cane-dark)]">Program</a>
                <a href="#alur" class="hover:text-[var(--cane-dark)]">Alur Pendaftaran</a>
                <a href="#bidang" class="hover:text-[var(--cane-dark)]">Bidang Magang</a>
                <a href="#syarat" class="hover:text-[var(--cane-dark)]">Syarat</a>
                <a href="#faq" class="hover:text-[var(--cane-dark)]">FAQ</a>
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('filament.peserta.auth.login') }}"
                   class="hidden sm:inline-block px-4 py-2 text-sm font-medium text-[var(--cane-dark)] hover:text-[var(--cane)]">
                    Masuk
                </a>
                <a href="{{ route('filament.peserta.auth.register') }}"
                   class="inline-block px-4 py-2 text-sm font-semibold rounded-md bg-[var(--cane)] text-[var(--husk-light)] hover:bg-[var(--cane-dark)] transition-colors">
                    Daftar Magang
                </a>
            </div>
        </div>
    </header>

    <main id="main-content" tabindex="-1">
    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden min-h-[560px] flex items-center">
        <div class="absolute inset-0">
            <img src="{{ asset('images/gedung-pg-krebet.png') }}"
                 alt="Gedung utama PT Rajawali I Unit PG Krebet Baru"
                 class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-[var(--cane-dark)]/80"></div>
            <div class="grain-light absolute inset-0 opacity-30" aria-hidden="true"></div>
        </div>

        <div class="relative max-w-6xl mx-auto px-6 pt-24 pb-20 md:pt-32 md:pb-28">
            <div class="max-w-xl rise rise-1">
                <p class="font-mono text-xs uppercase tracking-[0.2em] text-[var(--gold)] mb-5">
                    Program Magang &middot; PKL &amp; Penelitian
                </p>
                <h1 class="font-display font-black text-[var(--husk-light)] text-4xl sm:text-5xl leading-[1.08] mb-6">
                    Pengalaman kerja lapangan di lingkungan industri gula
                </h1>
                <p class="text-[17px] leading-relaxed text-[var(--husk-light)]/80 mb-9 max-w-md">
                    Ajukan PKL, magang, atau penelitian melalui satu portal. Pantau dokumen, proses persetujuan, penempatan, dan informasi kegiatan dari akun peserta.
                </p>
                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('filament.peserta.auth.register') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 rounded-md bg-[var(--gold)] !text-[var(--cane-dark)] font-semibold hover:brightness-95 transition-colors on-dark">
                        Buat Pengajuan
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                    <a href="#alur" class="on-dark text-sm font-medium text-[var(--husk-light)] underline underline-offset-4 decoration-[var(--gold)] decoration-2">
                        Lihat alur pendaftaran
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats strip — same "pencapaian" pattern as pgkrebetbaru.com, using real internship data --}}
    <section class="bg-[var(--gold)]">
        <div class="max-w-6xl mx-auto px-6 py-8 grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
            @php
                $statistik = [
                    ['angka' => 'Online', 'label' => 'Pengajuan dan pemantauan'],
                    ['angka' => '6', 'label' => 'Tahap utama proses'],
                    ['angka' => 'Terpantau', 'label' => 'Status dan dokumen'],
                ];
            @endphp
            @foreach ($statistik as $s)
                <div>
                    <p class="font-display font-black text-3xl sm:text-4xl text-[var(--cane-dark)]">{{ $s['angka'] }}</p>
                    <p class="text-xs sm:text-sm text-[var(--cane-dark)] mt-1">{{ $s['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============ TENTANG PROGRAM ============ --}}
    <section id="program" class="border-t border-[var(--ink)]/10 bg-[var(--husk-light)]">
        <div class="max-w-6xl mx-auto px-6 py-16 md:py-20">
            <div class="grid md:grid-cols-3 gap-10">
                <div>
                    <p class="font-mono text-xs uppercase tracking-[0.2em] text-[var(--molasses)] mb-3">Tentang Program</p>
                    <h2 class="font-display font-bold text-3xl text-[var(--cane-dark)] leading-tight">
                        Pilih jalur sesuai kebutuhan akademik
                    </h2>
                </div>
                <div class="md:col-span-2 grid sm:grid-cols-2 gap-6">
                    <div class="p-6 rounded-lg bg-white/60 border border-[var(--ink)]/10">
                        <p class="font-display font-semibold text-lg text-[var(--cane-dark)] mb-2">PKL (Praktik Kerja Lapangan)</p>
                        <p class="text-sm text-[var(--ink)]/70 leading-relaxed">
                            Untuk siswa atau mahasiswa yang membutuhkan pengalaman praktik kerja sesuai kompetensi dan bidang studinya.
                        </p>
                    </div>
                    <div class="p-6 rounded-lg bg-white/60 border border-[var(--ink)]/10">
                        <p class="font-display font-semibold text-lg text-[var(--cane-dark)] mb-2">Penelitian</p>
                        <p class="text-sm text-[var(--ink)]/70 leading-relaxed">
                            Untuk mahasiswa yang membutuhkan akses kegiatan atau data lapangan sesuai kebutuhan penelitian dan ketentuan perusahaan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ ALUR PENDAFTARAN ============ --}}
    <section id="alur" class="border-t border-[var(--ink)]/10">
        <div class="max-w-6xl mx-auto px-6 py-16 md:py-20">
            <p class="font-mono text-xs uppercase tracking-[0.2em] text-[var(--molasses)] mb-3">Alur Pendaftaran</p>
            <h2 class="font-display font-bold text-3xl text-[var(--cane-dark)] mb-3 max-w-xl">
                Tahapan pengajuan jelas dan dapat dipantau
            </h2>
            <p class="text-sm text-[var(--ink)]/75 mb-14 max-w-lg">
                Setiap pengajuan diproses bertahap. Status terbaru dapat dilihat langsung melalui akun peserta.
            </p>

            @php
                $tahapan = [
                    ['no' => '01', 'judul' => 'Ajukan Permohonan', 'desc' => 'Isi formulir pengajuan PKL/Penelitian secara online, lengkap dengan bidang tujuan dan periode magang.'],
                    ['no' => '02', 'judul' => 'Verifikasi Dokumen', 'desc' => 'Tim PIC memeriksa kelengkapan berkas persyaratan yang Anda unggah.'],
                    ['no' => '03', 'judul' => 'Persetujuan', 'desc' => 'Surat pengantar diproses melalui jalur disposisi pimpinan terkait.'],
                    ['no' => '04', 'judul' => 'Penempatan & Bimbingan', 'desc' => 'Anda ditempatkan di bagian tujuan dan didampingi pembimbing lapangan.'],
                    ['no' => '05', 'judul' => 'Evaluasi', 'desc' => 'Pembimbing lapangan menilai kinerja menjelang akhir masa magang.'],
                    ['no' => '06', 'judul' => 'Surat Keterangan', 'desc' => 'Anda menerima surat keterangan resmi setelah masa magang selesai.'],
                ];
            @endphp

            <div class="grid md:grid-cols-3 lg:grid-cols-6 gap-x-4 gap-y-10">
                @foreach ($tahapan as $i => $t)
                    <div class="relative">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="font-mono text-xs font-semibold text-[var(--husk-light)] bg-[var(--cane)] rounded-full h-7 w-7 flex items-center justify-center shrink-0">
                                {{ $t['no'] }}
                            </span>
                            @if (!$loop->last)
                                <span class="line-path hidden lg:block flex-1"></span>
                            @endif
                        </div>
                        <p class="font-display font-semibold text-[15px] text-[var(--cane-dark)] mb-1">{{ $t['judul'] }}</p>
                        <p class="text-[13px] text-[var(--ink)]/65 leading-relaxed">{{ $t['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ BIDANG MAGANG (dinamis dari data bagian) ============ --}}
    <section id="bidang" class="border-t border-[var(--ink)]/10 bg-[var(--cane-dark)]">
        <div class="max-w-6xl mx-auto px-6 py-16 md:py-20">
            <p class="font-mono text-xs uppercase tracking-[0.2em] text-[var(--gold)] mb-3">Bidang Magang</p>
            <h2 class="font-display font-bold text-3xl text-[var(--husk-light)] mb-12 max-w-xl">
                Pilih bagian yang sesuai dengan bidang studi
            </h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @php
                    $bagians = \App\Models\Bagian::orderBy('id')->pluck('nama_bagian');
                @endphp

                @forelse ($bagians as $nama)
                    <div class="p-6 rounded-lg border border-[var(--husk-light)]/15 hover:border-[var(--gold)]/60 hover:-translate-y-0.5 hover:shadow-[0_16px_30px_-18px_rgba(0,0,0,0.5)] transition-all duration-300">
                        <span class="font-mono text-[11px] text-[var(--gold)]">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <p class="font-display font-semibold text-lg text-[var(--husk-light)] mt-2">{{ $nama }}</p>
                    </div>
                @empty
                    <p class="text-[var(--husk-light)]/60 text-sm">Bidang magang akan segera diumumkan.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ============ SYARAT ============ --}}
    <section id="syarat" class="border-t border-[var(--ink)]/10 bg-[var(--husk-light)]">
        <div class="max-w-6xl mx-auto px-6 py-16 md:py-20 grid md:grid-cols-3 gap-12">
            <div>
                <p class="font-mono text-xs uppercase tracking-[0.2em] text-[var(--molasses)] mb-3">Syarat Pendaftaran</p>
                <h2 class="font-display font-bold text-3xl text-[var(--cane-dark)] leading-tight">
                    Siapkan berkas ini sebelum mengajukan
                </h2>
                <p class="text-sm text-[var(--ink)]/75 mt-4">
                    Berkas diunggah langsung saat pengajuan dibuat lewat akun peserta.
                </p>
            </div>
            <div class="md:col-span-2 grid sm:grid-cols-2 gap-x-8 gap-y-4">
                @foreach ([
                    'Surat pengantar resmi dari kampus/sekolah',
                    'Kartu Tanda Mahasiswa/Pelajar yang masih aktif',
                    'Proposal atau rencana kegiatan (untuk jalur Penelitian)',
                    'Pas foto terbaru',
                    'Fotokopi identitas diri (KTP/Kartu Pelajar)',
                    'Nomor kontak yang aktif untuk koordinasi',
                ] as $syarat)
                    <div class="flex items-start gap-3">
                        <span class="mt-1 h-4 w-4 rounded-full border-2 border-[var(--cane)] shrink-0"></span>
                        <p class="text-sm text-[var(--ink)]/80">{{ $syarat }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section id="faq" class="border-t border-[var(--ink)]/10">
        <div class="max-w-6xl mx-auto px-6 py-16 md:py-20">
            <p class="font-mono text-xs uppercase tracking-[0.2em] text-[var(--molasses)] mb-3">FAQ</p>
            <h2 class="font-display font-bold text-3xl text-[var(--cane-dark)] mb-10 max-w-xl">
                Pertanyaan umum
            </h2>

            <div class="max-w-2xl divide-y divide-[var(--ink)]/10 border-t border-b border-[var(--ink)]/10">
                @foreach ([
                    'Kapan pendaftaran magang dibuka?' => 'Pengajuan dapat dibuat melalui akun peserta. Waktu pemrosesan mengikuti kelengkapan dokumen, jadwal, dan ketersediaan bagian tujuan.',
                    'Apakah bisa mendaftar secara berkelompok?' => 'Bisa. Setiap anggota kelompok tetap perlu membuat akun dan pengajuan masing-masing agar tercatat di sistem.',
                    'Berapa lama proses verifikasi berlangsung?' => 'Setelah dokumen dikirim, PIC memeriksa kelengkapannya. Perubahan status dapat dipantau dari dashboard peserta.',
                    'Bagaimana jika masa magang perlu diperpanjang?' => 'Perpanjangan dapat diajukan melalui akun peserta menjelang akhir masa magang, dan akan diproses melalui persetujuan bagian terkait.',
                ] as $q => $a)
                    <details class="group py-5">
                        <summary class="flex items-center justify-between cursor-pointer list-none font-display font-medium text-[var(--cane-dark)]">
                            {{ $q }}
                            <span class="ml-4 shrink-0 text-[var(--molasses)] transition-transform group-open:rotate-45">+</span>
                        </summary>
                        <p class="mt-3 text-sm text-[var(--ink)]/70 leading-relaxed pr-8">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="relative overflow-hidden border-t border-[var(--ink)]/10 bg-[var(--cane)]">
        <div class="grain-light absolute inset-0 opacity-60 pointer-events-none" aria-hidden="true"></div>
        <div class="relative max-w-6xl mx-auto px-6 py-16 md:py-20 text-center">
            <h2 class="font-display font-bold text-3xl sm:text-4xl text-[var(--husk-light)] mb-5">
                Ajukan PKL, magang, atau penelitian
            </h2>
            <p class="text-[var(--husk-light)]/75 mb-8 max-w-md mx-auto">
                Buat akun peserta, lengkapi pengajuan, lalu pantau prosesnya secara daring.
            </p>
            <div class="flex items-center justify-center gap-4 flex-wrap">
                <a href="{{ route('filament.peserta.auth.register') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-md bg-[var(--gold)] !text-[var(--cane-dark)] font-semibold hover:brightness-95 transition">
                    Buat Akun Peserta
                </a>
            </div>
        </div>
    </section>

    </main>

    {{-- ============ FOOTER ============ --}}
    <footer class="bg-[var(--cane-dark)] text-[var(--husk-light)]/60">
        <div class="max-w-6xl mx-auto px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center rounded-md bg-[var(--husk-light)] px-3 py-1.5">
                    <img src="{{ asset('images/logo-rajawali.png') }}" alt="Logo PT Rajawali I - Unit PG Krebet Baru" class="h-7 w-auto">
                </span>
                <p class="text-xs leading-relaxed">
                    &copy; {{ now()->year }} PT Rajawali I<br class="sm:hidden">
                    <span class="hidden sm:inline">&mdash; </span>Unit PG Krebet Baru
                </p>
            </div>
            <div class="flex items-center gap-6 text-xs">
                <a href="{{ route('filament.peserta.auth.login') }}" class="on-dark hover:text-[var(--husk-light)]">Masuk Peserta</a>
                <a href="{{ route('filament.peserta.auth.register') }}" class="on-dark hover:text-[var(--husk-light)]">Daftar</a>
            </div>
        </div>
    </footer>

</body>
</html>
