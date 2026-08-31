<?php

require __DIR__.'/../vendor/autoload.php';

use App\Services\PengajuanWorkflowService;

$expected = [
    'kirimPengingatKeputusanPerpanjangan' => 1,
    'inputHasilAkhirManual' => 6,
    'selesaikanEvaluasiPerpanjangan' => 3,
    'uploadSuratKeteranganSelesai' => 4,
    'ajukanPermohonanPerpanjangan' => 3,
    'putuskanPerpanjangan' => 3,
];

$reflection = new ReflectionClass(PengajuanWorkflowService::class);

foreach ($expected as $method => $maxParameters) {
    if (! $reflection->hasMethod($method)) {
        fwrite(STDERR, "[FAIL] Missing workflow method: {$method}\n");
        exit(1);
    }

    $actual = $reflection->getMethod($method)->getNumberOfParameters();

    if ($actual !== $maxParameters) {
        fwrite(STDERR, "[FAIL] {$method}: expected {$maxParameters} parameters, found {$actual}.\n");
        exit(1);
    }

    fwrite(STDOUT, "[OK] {$method} contract ({$actual} params).\n");
}

fwrite(STDOUT, "Workflow contract smoke checks passed.\n");
