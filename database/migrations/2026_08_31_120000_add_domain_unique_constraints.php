<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce relationships that the domain models already treat as one-to-one.
     * Existing duplicate rows are never deleted implicitly: the migration stops
     * with an actionable error so production data can be reconciled explicitly.
     */
    public function up(): void
    {
        $this->assertNoDuplicates('pesertas', ['user_id']);
        $this->assertNoDuplicates('penugasan_pembimbings', ['pengajuan_id']);
        $this->assertNoDuplicates('surat_balasans', ['pengajuan_id']);
        $this->assertNoDuplicates('evaluasis', ['pengajuan_id']);
        $this->assertNoDuplicates('surat_keterangans', ['pengajuan_id']);
        $this->assertNoDuplicates('approval_workflows', ['pengajuan_id', 'urutan']);

        Schema::table('pesertas', function (Blueprint $table): void {
            $table->unique('user_id', 'pesertas_user_id_unique');
        });

        Schema::table('penugasan_pembimbings', function (Blueprint $table): void {
            $table->unique('pengajuan_id', 'penugasan_pembimbings_pengajuan_id_unique');
        });

        Schema::table('surat_balasans', function (Blueprint $table): void {
            $table->unique('pengajuan_id', 'surat_balasans_pengajuan_id_unique');
        });

        Schema::table('evaluasis', function (Blueprint $table): void {
            $table->unique('pengajuan_id', 'evaluasis_pengajuan_id_unique');
        });

        Schema::table('surat_keterangans', function (Blueprint $table): void {
            $table->unique('pengajuan_id', 'surat_keterangans_pengajuan_id_unique');
        });

        Schema::table('approval_workflows', function (Blueprint $table): void {
            $table->unique(['pengajuan_id', 'urutan'], 'approval_workflows_pengajuan_urutan_unique');
        });
    }

    public function down(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table): void {
            $table->dropUnique('approval_workflows_pengajuan_urutan_unique');
        });
        Schema::table('surat_keterangans', fn (Blueprint $table) => $table->dropUnique('surat_keterangans_pengajuan_id_unique'));
        Schema::table('evaluasis', fn (Blueprint $table) => $table->dropUnique('evaluasis_pengajuan_id_unique'));
        Schema::table('surat_balasans', fn (Blueprint $table) => $table->dropUnique('surat_balasans_pengajuan_id_unique'));
        Schema::table('penugasan_pembimbings', fn (Blueprint $table) => $table->dropUnique('penugasan_pembimbings_pengajuan_id_unique'));
        Schema::table('pesertas', fn (Blueprint $table) => $table->dropUnique('pesertas_user_id_unique'));
    }

    /** @param array<int, string> $columns */
    private function assertNoDuplicates(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $query = DB::table($table)
            ->select($columns)
            ->selectRaw('COUNT(*) AS duplicate_count')
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1');

        if ($query->exists()) {
            throw new \RuntimeException(sprintf(
                'Cannot add unique constraint to %s (%s): duplicate production data exists. Reconcile duplicates first, then rerun migrations.',
                $table,
                implode(', ', $columns),
            ));
        }
    }
};
