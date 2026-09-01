<?php

$checks = [
    'config/performance.php' => [
        "'database_warn_ms'",
        "'request_warn_ms'",
    ],
    'app/Http/Middleware/RequestPerformance.php' => [
        'performance.slow_request',
        'Server-Timing',
    ],
    'app/Providers/AppServiceProvider.php' => [
        'DB::whenQueryingForLongerThan',
        'performance.slow_database_request',
    ],
    'app/Filament/Pages/TugasSaya.php' => [
        'scopeGiliranSaya',
        'whereDoesntHave',
    ],
    'app/Services/PengajuanWorkflowService.php' => [
        'MIN(urutan) as active_urutan',
        'fromSub($activeStepPerSubmission',
    ],
    'database/migrations/2026_08_31_160000_add_performance_indexes.php' => [
        'pengajuan_status_created_idx',
        'approval_status_urutan_pengajuan_idx',
        'notifikasi_user_created_idx',
    ],
];

$failed = false;
foreach ($checks as $file => $needles) {
    $contents = @file_get_contents($file);
    if ($contents === false) {
        fwrite(STDERR, "[FAIL] Missing {$file}\n");
        $failed = true;

        continue;
    }

    foreach ($needles as $needle) {
        if (! str_contains($contents, $needle)) {
            fwrite(STDERR, "[FAIL] {$file} missing: {$needle}\n");
            $failed = true;
        }
    }
}

$pollingWidgets = [
    'app/Filament/Widgets/GmStatsOverview.php',
    'app/Filament/Widgets/GmMonthlyChartWidget.php',
    'app/Filament/Widgets/KepalaBagianStatsWidget.php',
    'app/Filament/Widgets/PengajuanStatsWidget.php',
    'app/Filament/Peserta/Widgets/PesertaStatsOverview.php',
    'app/Filament/Peserta/Widgets/PesertaPengajuanStatsWidget.php',
    'app/Filament/Peserta/Widgets/PesertaPengajuanStatusChartWidget.php',
];

foreach ($pollingWidgets as $file) {
    $contents = (string) @file_get_contents($file);
    if (! str_contains($contents, 'protected ?string $pollingInterval = null;')) {
        fwrite(STDERR, "[FAIL] Default 5s polling still enabled in {$file}\n");
        $failed = true;
    }
}

// Guard against the two regressions that caused the biggest query/memory growth.
$department = (string) file_get_contents('app/Filament/Widgets/GmDepartmentUniversityWidget.php');
if (str_contains($department, "Pengajuan::with('bagian')->get()") || str_contains($department, "Pengajuan::with('peserta')->get()")) {
    fwrite(STDERR, "[FAIL] GM distribution widget loads full Pengajuan collections.\n");
    $failed = true;
}

$tugas = (string) file_get_contents('app/Filament/Pages/TugasSaya.php');
if (str_contains($tugas, 'pengajuanIdsGiliranSaya')) {
    fwrite(STDERR, "[FAIL] Legacy N+1 approval-id resolver is still present.\n");
    $failed = true;
}

if ($failed) {
    exit(1);
}

echo "[OK] P6 performance source audit\n";
