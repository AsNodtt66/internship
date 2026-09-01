<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$expectContains = static function (string $file, array $needles) use ($root, &$failures): void {
    $path = $root.'/'.$file;
    if (! is_file($path)) {
        $failures[] = "missing: {$file}";

        return;
    }
    $content = file_get_contents($path) ?: '';
    foreach ($needles as $needle) {
        if (! str_contains($content, $needle)) {
            $failures[] = "{$file}: missing expected marker {$needle}";
        }
    }
};

$expectNotContains = static function (string $file, array $needles) use ($root, &$failures): void {
    $path = $root.'/'.$file;
    if (! is_file($path)) {
        $failures[] = "missing: {$file}";

        return;
    }
    $content = file_get_contents($path) ?: '';
    foreach ($needles as $needle) {
        if (str_contains($content, $needle)) {
            $failures[] = "{$file}: forbidden marker still present {$needle}";
        }
    }
};

$requiredFiles = [
    '.playwright-version',
    'playwright.config.mjs',
    'database/seeders/TestingSeeder.php',
    'e2e/auth.setup.mjs',
    'e2e/security/authorization.spec.mjs',
    'e2e/security/private-documents.spec.mjs',
    'scripts/e2e/install-playwright.sh',
    'scripts/e2e/reset-test-db.sh',
    'tests/Feature/Safety/DestructiveLifecycleTest.php',
    'tests/Feature/Safety/RoleInvariantTest.php',
    'tests/Feature/Workflow/EvaluationDecisionRuleTest.php',
    'database/migrations/2026_08_31_171000_add_soft_deletes_to_identity_history.php',
];
foreach ($requiredFiles as $file) {
    if (! is_file($root.'/'.$file)) {
        $failures[] = "missing: {$file}";
    }
}

$expectContains('app/Models/User.php', ['SoftDeletes']);
$expectContains('app/Models/Peserta.php', ['SoftDeletes']);
$expectContains('app/Models/Pengajuan.php', ['SoftDeletes']);
$expectContains('app/Policies/UserPolicy.php', ['return false;', 'forceDelete']);
$expectContains('app/Policies/PesertaPolicy.php', ['return false;', 'forceDelete']);
$expectContains('app/Models/Role.php', ['Slug role sistem tidak boleh diubah', 'Role sistem tidak boleh dihapus']);
$expectNotContains('app/Filament/Resources/Users/UserResource.php', ['DeleteAction::make()', 'DeleteBulkAction::make()']);
$expectNotContains('app/Filament/Resources/Pesertas/Tables/PesertasTable.php', ['DeleteBulkAction::make()']);
$expectNotContains('app/Filament/Resources/Pesertas/Pages/EditPeserta.php', ['DeleteAction::make()']);
$expectNotContains('app/Filament/Resources/Roles/Tables/RolesTable.php', ['DeleteBulkAction::make()']);
$expectContains('app/Services/PengajuanWorkflowService.php', ['akhir `selesai` / `perlu_perpanjangan` dipilih MANUAL oleh PIC']);
$expectContains('.gitlab-ci.yml', ['mysql_integration:', 'playwright_chromium:', 'playwright_cross_browser:', 'ci_green_gate:']);
$expectNotContains('.gitlab-ci.yml', ['p7_strict_staging_gate:']);

if ($failures !== []) {
    fwrite(STDERR, "P8 testing audit FAILED:\n - ".implode("\n - ", $failures)."\n");
    exit(1);
}

fwrite(STDOUT, "P8 testing audit PASS\n");
