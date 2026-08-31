<?php

$root = dirname(__DIR__);
chdir($root);
$full = in_array('--full', $argv, true);
$failed = false;

function run(string $label, string $command): bool
{
    echo "\n== {$label} ==\n";
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, "[FAIL] {$label} (exit {$exitCode})\n");
        return false;
    }

    echo "[OK] {$label}\n";
    return true;
}

$directories = ['app', 'bootstrap', 'config', 'database', 'routes', 'tests', 'scripts'];
$phpFiles = [];

foreach ($directories as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $phpFiles[] = $file->getPathname();
        }
    }
}

sort($phpFiles);
echo 'Linting '.count($phpFiles)." PHP files...\n";
foreach ($phpFiles as $file) {
    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file), $output, $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, "[FAIL] PHP syntax: {$file}\n".implode("\n", $output)."\n");
        exit(1);
    }
    $output = [];
}
echo "[OK] PHP syntax\n";

$failed = ! run('P5 UI source audit', escapeshellarg(PHP_BINARY).' scripts/ui-audit.php') || $failed;
$failed = ! run('P6 performance source audit', escapeshellarg(PHP_BINARY).' scripts/performance-audit.php') || $failed;
$failed = ! run('P7 release source audit', escapeshellarg(PHP_BINARY).' scripts/release/release-candidate-audit.php') || $failed;
$failed = ! run('P7 upload security audit', escapeshellarg(PHP_BINARY).' scripts/release/upload-security-audit.php') || $failed;

if (! file_exists('vendor/autoload.php')) {
    fwrite(STDERR, "vendor/ belum tersedia. Jalankan composer install sebelum verification.\n");
    exit(1);
}

$php = escapeshellarg(PHP_BINARY);
$failed = ! run('P4 source compatibility', "{$php} scripts/upgrade/p4/compatibility-check.php") || $failed;
$failed = ! run('Policy authorization smoke', "{$php} scripts/smoke-policies.php") || $failed;
$failed = ! run('Workflow contract smoke', "{$php} scripts/smoke-contracts.php") || $failed;
$failed = ! run('Laravel route bootstrap', "{$php} artisan route:list --except-vendor") || $failed;
putenv('CACHE_STORE=array');
$failed = ! run('Laravel scheduler bootstrap', "{$php} artisan schedule:list") || $failed;

if ($full) {
    $failed = ! run('Laravel Pint', "{$php} vendor/bin/pint --test") || $failed;
    $failed = ! run('PHPUnit / Laravel tests', "{$php} artisan test") || $failed;
    $failed = ! run('Frontend production build', 'npm run build') || $failed;
}

exit($failed ? 1 : 0);
