<?php

declare(strict_types=1);

$failures = 0;
$warnings = 0;

$ok = static function (string $message): void {
    echo "[OK] {$message}\n";
};
$fail = static function (string $message) use (&$failures): void {
    $failures++;
    echo "[FAIL] {$message}\n";
};
$warn = static function (string $message) use (&$warnings): void {
    $warnings++;
    echo "[WARN] {$message}\n";
};

if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    $fail('PHP >= 8.2 diperlukan untuk baseline P3; ditemukan '.PHP_VERSION);
} elseif (version_compare(PHP_VERSION, '8.4.0', '<')) {
    $warn('PHP '.PHP_VERSION.' dapat menjalankan baseline P3, tetapi P4 modernization membutuhkan PHP >= 8.4');
} else {
    $ok('PHP '.PHP_VERSION.' (P4-ready)');
}

$requiredExtensions = ['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pdo', 'session', 'tokenizer', 'xml', 'xmlwriter'];
foreach ($requiredExtensions as $extension) {
    extension_loaded($extension) ? $ok("PHP extension {$extension}") : $fail("PHP extension {$extension} belum aktif");
}

if (extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite')) {
    $ok('Driver database PDO tersedia');
} else {
    $fail('Aktifkan minimal pdo_mysql atau pdo_sqlite');
}

foreach (['composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'artisan'] as $file) {
    is_file($file) ? $ok("{$file} tersedia") : $fail("{$file} tidak ditemukan");
}

is_file('.env') ? $ok('.env tersedia') : $warn('.env belum ada; salin dari .env.example');

foreach (['storage', 'bootstrap/cache'] as $path) {
    if (! is_dir($path)) {
        $fail("Directory {$path} tidak ditemukan");
    } elseif (is_writable($path)) {
        $ok("{$path} writable");
    } else {
        $warn("{$path} tidak writable oleh user saat ini");
    }
}

$commands = [
    'composer' => 'composer --version',
    'npm' => 'npm --version',
];

if (function_exists('exec')) {
    foreach ($commands as $name => $command) {
        $output = [];
        $exit = 1;
        @exec($command.' 2>&1', $output, $exit);
        $exit === 0 ? $ok($name.' '.trim(implode(' ', $output))) : $warn("{$name} tidak terdeteksi dari PATH");
    }

    $nodeOutput = [];
    $nodeExit = 1;
    @exec('node -p "process.versions.node" 2>&1', $nodeOutput, $nodeExit);
    if ($nodeExit !== 0) {
        $warn('node tidak terdeteksi dari PATH');
    } else {
        $nodeVersion = trim(implode('', $nodeOutput));
        $parts = array_map('intval', explode('.', $nodeVersion));
        $major = $parts[0] ?? 0;
        $minor = $parts[1] ?? 0;
        $vite8Compatible = ($major === 20 && $minor >= 19) || ($major === 22 && $minor >= 12) || $major > 22;
        $vite8Compatible
            ? $ok('node '.$nodeVersion.' (Vite 8 compatible)')
            : $warn('node '.$nodeVersion.' dapat tidak kompatibel dengan Vite 8; gunakan Node 20.19+ atau 22.12+');
    }
} else {
    $warn('PHP exec() disabled; versi Composer/Node/npm tidak diperiksa');
}

echo "\nSummary: {$failures} failure(s), {$warnings} warning(s).\n";
exit($failures === 0 ? 0 : 1);
