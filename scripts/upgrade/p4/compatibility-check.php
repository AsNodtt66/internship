<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$expectModern = in_array('--expect-modern', $argv, true);
$failures = [];
$warnings = [];

function readFileOrFail(string $path): string
{
    $content = @file_get_contents($path);
    if ($content === false) {
        fwrite(STDERR, "[FAIL] Tidak bisa membaca {$path}\n");
        exit(1);
    }

    return $content;
}

function jsonFile(string $path): array
{
    $data = json_decode(readFileOrFail($path), true);
    if (! is_array($data)) {
        fwrite(STDERR, "[FAIL] JSON tidak valid: {$path}\n");
        exit(1);
    }

    return $data;
}

$composer = jsonFile($root.'/composer.json');
$package = jsonFile($root.'/package.json');
$appProvider = readFileOrFail($root.'/app/Providers/AppServiceProvider.php');
$adminPanel = readFileOrFail($root.'/app/Providers/Filament/AdminPanelProvider.php');
$pesertaPanel = readFileOrFail($root.'/app/Providers/Filament/PesertaPanelProvider.php');
$cache = readFileOrFail($root.'/config/cache.php');
$session = readFileOrFail($root.'/config/session.php');
$vite = readFileOrFail($root.'/vite.config.js');

if (! str_contains($adminPanel, 'PreventRequestForgery') || ! str_contains($pesertaPanel, 'PreventRequestForgery')) {
    $failures[] = 'Panel belum siap untuk Laravel 13 PreventRequestForgery.';
}
if (! str_contains($appProvider, 'connectionName')) {
    $failures[] = 'QueueBusy listener belum siap untuk property connectionName Laravel 13.';
}
if (! str_contains($cache, "'serializable_classes' => false")) {
    $failures[] = 'Cache serializable_classes hardening belum aktif.';
}
if (! str_contains($session, "'serialization' => env('SESSION_SERIALIZATION', 'json')")) {
    $failures[] = 'Session JSON serialization target belum dikonfigurasi.';
}
if (preg_match('/\barray_(first|last)\s*\(/', implode("\n", array_map(
    fn (string $file) => @file_get_contents($file) ?: '',
    glob($root.'/app/*.php') ?: []
)))) {
    $warnings[] = 'Periksa custom helper array_first/array_last untuk konflik symfony/polyfill-php85.';
}
if (str_contains($vite, 'rollupOptions') || str_contains($vite, 'esbuild:')) {
    $warnings[] = 'vite.config.js memakai opsi yang perlu review saat Vite 8/Rolldown.';
}
if (($package['type'] ?? null) !== 'module') {
    $failures[] = 'package.json harus type=module untuk konfigurasi Vite ESM.';
}

if ($expectModern) {
    $requirements = [
        ['composer require php', $composer['require']['php'] ?? '', '/\^8\.[45]/'],
        ['Laravel 13', $composer['require']['laravel/framework'] ?? '', '/\^13/'],
        ['Tinker 3', $composer['require']['laravel/tinker'] ?? '', '/\^3/'],
        ['Filament 5', $composer['require']['filament/filament'] ?? '', '/\^5/'],
        ['PHPUnit 12', $composer['require-dev']['phpunit/phpunit'] ?? '', '/\^12/'],
        ['Vite 8', $package['devDependencies']['vite'] ?? '', '/\^8/'],
        ['Laravel Vite Plugin 3', $package['devDependencies']['laravel-vite-plugin'] ?? '', '/\^3/'],
    ];
    foreach ($requirements as [$label, $actual, $pattern]) {
        if (! preg_match($pattern, (string) $actual)) {
            $failures[] = "{$label} belum pada target P4 (aktual: {$actual}).";
        }
    }
}

foreach ($warnings as $warning) {
    echo "[WARN] {$warning}\n";
}
foreach ($failures as $failure) {
    echo "[FAIL] {$failure}\n";
}

if ($failures !== []) {
    exit(1);
}

echo '[PASS] P4 source compatibility checks'.($expectModern ? ' + modern dependency constraints' : '').".\n";
