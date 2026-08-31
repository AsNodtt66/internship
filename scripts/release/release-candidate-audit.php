<?php

/**
 * P8 source-only readiness audit. Production/staging promotion is outside
 * the current CI scope; P8 proves testing structure and safety controls.
 */

$root = dirname(__DIR__, 2);
$failures = [];
$passes = [];

$requireFile = static function (string $path) use ($root, &$failures, &$passes): void {
    if (is_file($root.'/'.$path)) {
        $passes[] = "file: {$path}";
    } else {
        $failures[] = "missing required file: {$path}";
    }
};

foreach ([
    'config/release.php',
    'app/Console/Commands/ReleaseCandidateCheck.php',
    'tests/Feature/Security/AuthorizationBoundaryTest.php',
    'tests/Feature/Security/PrivateDocumentAuthorizationTest.php',
    'scripts/release/package-audit.php',
    'scripts/release/upload-security-audit.php',
    'load/k6/public-smoke.js',
    'load/k6/authenticated-read-smoke.js',
    'docs/P7-RELEASE-CANDIDATE.md',
    'docs/RELEASE-RUNBOOK.md',
    'docs/ROLLBACK-RUNBOOK.md',
    'docs/ASVS-VERIFICATION.md',
    'docs/LOAD-TESTING.md',
] as $path) {
    $requireFile($path);
}

$gitignore = is_file($root.'/.gitignore') ? (string) file_get_contents($root.'/.gitignore') : '';
foreach (['.env', '/vendor', '/node_modules', '/public/build'] as $pattern) {
    if (str_contains($gitignore, $pattern)) {
        $passes[] = "gitignore protects: {$pattern}";
    } else {
        $failures[] = "gitignore missing release protection: {$pattern}";
    }
}

$composer = json_decode((string) file_get_contents($root.'/composer.json'), true);
foreach (['verify:release', 'release:check'] as $script) {
    if (isset($composer['scripts'][$script])) {
        $passes[] = "composer script: {$script}";
    } else {
        $failures[] = "composer script missing: {$script}";
    }
}

$web = (string) file_get_contents($root.'/routes/web.php');
foreach (['throttle:private-documents', 'throttle:generated-reports', "middleware(['auth'"] as $needle) {
    if (str_contains($web, $needle)) {
        $passes[] = "route security: {$needle}";
    } else {
        $failures[] = "route security marker missing: {$needle}";
    }
}

$registry = (string) file_get_contents($root.'/app/Support/Documents/PrivateDocumentRegistry.php');
foreach (["str_contains(\$normalized, '://')", "in_array('..', \$segments, true)"] as $needle) {
    if (str_contains($registry, $needle)) {
        $passes[] = "private path hardening: {$needle}";
    } else {
        $failures[] = "private path hardening missing: {$needle}";
    }
}

$ci = (string) file_get_contents($root.'/.gitlab-ci.yml');
foreach (['p8_release_source_audit', 'mysql_integration', 'playwright_chromium', 'playwright_cross_browser', 'ci_green_gate'] as $job) {
    if (str_contains($ci, $job.':')) {
        $passes[] = "CI job: {$job}";
    } else {
        $failures[] = "CI job missing: {$job}";
    }
}

foreach ($passes as $pass) {
    echo "[PASS] {$pass}\n";
}
if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "[FAIL] {$failure}\n");
    }
    exit(1);
}

echo "\nP8 source readiness audit passed. Project-testing success is represented by the mandatory ci_green_gate job.\n";
