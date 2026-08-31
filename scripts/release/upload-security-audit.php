<?php

$root = dirname(__DIR__, 2);
$failures = [];
$count = 0;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app'));
foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $lines = file($file->getPathname()) ?: [];
    foreach ($lines as $index => $line) {
        if (! str_contains($line, 'FileUpload::make(')) {
            continue;
        }
        $count++;
        $block = implode('', array_slice($lines, $index, 28));
        $relative = substr($file->getPathname(), strlen($root) + 1);
        $label = $relative.':'.($index + 1);

        if (! str_contains($block, 'acceptedFileTypes(') && ! str_contains($block, '->image(')) {
            $failures[] = "{$label} missing acceptedFileTypes()/image()";
        }
        if (! str_contains($block, 'maxSize(')) {
            $failures[] = "{$label} missing maxSize()";
        }
        if (str_contains($block, "->visibility('public')")) {
            $failures[] = "{$label} uses public visibility";
        }
        if (str_contains($block, 'preserveFilenames(')) {
            $failures[] = "{$label} preserves client filename";
        }
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "[FAIL] {$failure}\n");
    }
    exit(1);
}

echo "[PASS] {$count} FileUpload definitions have type/size baselines and no public/preserved-filename marker.\n";
