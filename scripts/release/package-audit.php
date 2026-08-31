<?php

$root = $argv[1] ?? dirname(__DIR__, 2);
$root = rtrim($root, DIRECTORY_SEPARATOR);
$failures = [];

$forbiddenFiles = ['.env', 'database/database.sqlite', 'storage/logs/laravel.log', 'bootstrap/cache/packages.php', 'bootstrap/cache/services.php', 'bootstrap/cache/config.php', 'bootstrap/cache/routes-v7.php'];
foreach ($forbiddenFiles as $path) {
    if (is_file($root.'/'.$path)) {
        $failures[] = $path;
    }
}

$forbiddenDirs = ['vendor', 'node_modules', '.git', 'public/build'];
foreach ($forbiddenDirs as $path) {
    if (is_dir($root.'/'.$path)) {
        $failures[] = $path.'/';
    }
}

$privateDocuments = $root.'/storage/app/private/documents';
if (is_dir($privateDocuments)) {
    $entries = array_values(array_diff(scandir($privateDocuments) ?: [], ['.', '..', '.gitignore']));
    if ($entries !== []) {
        $failures[] = 'storage/app/private/documents/ contains packaged files';
    }
}

$secretPatterns = [
    '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    '/APP_KEY=base64:[A-Za-z0-9+\/=]{20,}/',
    '/AWS_SECRET_ACCESS_KEY=\S+/',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getSize() > 2_000_000) {
        continue;
    }
    $relative = substr($file->getPathname(), strlen($root) + 1);
    if (str_starts_with($relative, 'docs/legacy/') || $relative === 'scripts/release/package-audit.php') {
        continue;
    }
    $contents = @file_get_contents($file->getPathname());
    if ($contents === false) {
        continue;
    }
    foreach ($secretPatterns as $pattern) {
        if (preg_match($pattern, $contents) === 1) {
            $failures[] = "secret-like content: {$relative}";
        }
    }
}

if ($failures !== []) {
    foreach (array_unique($failures) as $failure) {
        fwrite(STDERR, "[FAIL] {$failure}\n");
    }
    exit(1);
}

echo "[PASS] source-only package audit\n";
