<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PerformanceCheck extends Command
{
    protected $signature = 'performance:check';

    protected $description = 'Validate P6 performance configuration and hot-path database indexes';

    /** @var array<string, array<int, string>> */
    private const EXPECTED_INDEXES = [
        'pengajuans' => [
            'pengajuan_status_created_idx',
            'pengajuan_status_selesai_idx',
            'pengajuan_bagian_status_idx',
            'pengajuan_peserta_created_idx',
        ],
        'approval_workflows' => ['approval_status_urutan_pengajuan_idx'],
        'riwayat_status' => ['riwayat_created_idx', 'riwayat_pengajuan_created_idx'],
        'notifikasis' => ['notifikasi_user_created_idx'],
        'perpanjangans' => ['perpanjangan_pengajuan_status_idx'],
        'dokumen_persyaratans' => ['dokumen_pengajuan_jenis_idx', 'dokumen_pengajuan_verifikasi_idx'],
    ];

    public function handle(): int
    {
        $this->components->info('P6 Performance Check');
        $this->line('Database: '.DB::connection()->getDriverName().' / '.DB::connection()->getDatabaseName());
        $this->line('Slow DB warning: '.config('performance.database_warn_ms').' ms cumulative/request');
        $this->line('Slow request warning: '.config('performance.request_warn_ms').' ms');
        $this->line('Server-Timing: '.(config('performance.server_timing') ? 'enabled' : 'disabled'));

        $missing = [];

        try {
            foreach (self::EXPECTED_INDEXES as $table => $expected) {
                if (! Schema::hasTable($table)) {
                    $missing[] = "{$table}: table missing";

                    continue;
                }

                $actual = collect(Schema::getIndexes($table))
                    ->pluck('name')
                    ->filter()
                    ->all();

                foreach ($expected as $index) {
                    if (! in_array($index, $actual, true)) {
                        $missing[] = "{$table}.{$index}";
                    }
                }
            }
        } catch (Throwable $e) {
            $this->components->error('Could not inspect indexes: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($missing !== []) {
            $this->components->error('P6 performance indexes are incomplete. Run migrations first.');
            foreach ($missing as $item) {
                $this->line('  - '.$item);
            }

            return self::FAILURE;
        }

        $this->components->success('Performance configuration and expected indexes are present.');

        return self::SUCCESS;
    }
}
