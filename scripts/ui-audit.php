<?php

$root = dirname(__DIR__);
$failures = [];

function source(string $relative): string
{
    global $root;

    $path = $root.DIRECTORY_SEPARATOR.$relative;

    if (! is_file($path)) {
        throw new RuntimeException("File tidak ditemukan: {$relative}");
    }

    return file_get_contents($path) ?: '';
}

function expectContains(string $file, string $needle, string $message): void
{
    global $failures;

    if (! str_contains(source($file), $needle)) {
        $failures[] = "{$file}: {$message}";
    }
}

function expectNotContains(string $file, string $needle, string $message): void
{
    global $failures;

    if (str_contains(source($file), $needle)) {
        $failures[] = "{$file}: {$message}";
    }
}

expectContains('resources/views/landing.blade.php', '<html lang="id">', 'bahasa halaman harus dideklarasikan sebagai Indonesia.');
expectContains('resources/views/landing.blade.php', 'href="#main-content"', 'landing page harus mempunyai skip link.');
expectContains('resources/views/landing.blade.php', '<main id="main-content"', 'landing page harus mempunyai landmark main yang dapat dituju skip link.');
expectContains('resources/views/landing.blade.php', 'aria-label="Navigasi utama"', 'navigasi utama harus memiliki accessible name.');
expectContains('resources/css/filament/admin/theme.css', 'prefers-reduced-motion: reduce', 'theme harus menghormati preferensi reduced motion.');
expectContains('resources/css/filament/admin/theme.css', ':focus-visible', 'theme harus mempunyai focus treatment untuk komponen custom.');
expectContains('resources/views/filament/peserta/pages/dokumen-saya.blade.php', 'role="region"', 'tabel dokumen mobile harus berada dalam region yang dapat difokuskan.');
expectContains('resources/views/filament/peserta/pages/dokumen-saya.blade.php', '<caption class="sipkl-sr-only">', 'tabel dokumen harus memiliki caption aksesibel.');
expectContains('resources/views/filament/peserta/pages/dashboard.blade.php', 'aria-current="step"', 'tahap aktif harus diekspos ke assistive technology.');
expectContains('app/Support/Ui/PengajuanStatusPresenter.php', "'proses_approval' => 'Proses Persetujuan'", 'status pengajuan harus memakai istilah Indonesia yang konsisten.');
expectNotContains('resources/views/filament/peserta/widgets/quick-actions.blade.php', 'Quick Action', 'hindari istilah UI campuran Quick Action.');
expectNotContains('resources/views/filament/peserta/widgets/quick-actions.blade.php', 'Download ', 'gunakan Unduh pada copy peserta.');
expectNotContains('resources/views/landing.blade.php', 'kamu', 'landing page harus konsisten memakai sapaan formal Anda.');
expectNotContains('resources/views/landing.blade.php', 'magangmu', 'landing page harus konsisten memakai sapaan formal Anda.');


expectContains('app/Filament/Peserta/Resources/PengajuanResource.php', "->description('Pilih jalur pengajuan dan pahami ketentuannya.')", 'wizard pengajuan harus memberi orientasi pada setiap tahap.');
expectContains('app/Filament/Peserta/Resources/PengajuanResource.php', "->persistStepInQueryString('tahap')", 'tahap wizard harus dapat dipertahankan pada URL untuk wayfinding.');
expectContains('app/Filament/Peserta/Resources/PengajuanResource.php', '->previousAction(fn (Action $action) => $action->label(\'Kembali\'))', 'aksi wizard sebelumnya harus menggunakan bahasa Indonesia.');
expectContains('app/Filament/Peserta/Resources/PengajuanResource.php', '->nextAction(fn (Action $action) => $action->label(\'Lanjut\'))', 'aksi wizard berikutnya harus menggunakan bahasa Indonesia.');
expectContains('resources/views/filament/peserta/pages/notifikasi-saya.blade.php', '<time class="sipkl-info-time"', 'tanggal pemberitahuan harus memakai elemen time semantik.');
expectContains('resources/views/filament/peserta/pages/notifikasi-saya.blade.php', 'aria-live="polite"', 'daftar pemberitahuan harus mengumumkan pembaruan non-mendesak secara sopan.');
expectContains('resources/views/filament/peserta/pages/jadwal-pkl.blade.php', 'rel="noopener"', 'dokumen yang dibuka di tab baru harus memakai rel noopener.');
expectNotContains('app/Filament/Resources/Pengajuans/Tables/PengajuansTable.php', "->label('Rekap & Mulai Approval')", 'gunakan istilah Persetujuan pada aksi admin.');

if ($failures !== []) {
    fwrite(STDERR, "P5 UI audit gagal:\n - ".implode("\n - ", $failures)."\n");
    exit(1);
}

echo "[OK] P5 UI source audit\n";
