<?php

namespace Tests\Feature\Performance;

use App\Models\ApprovalWorkflow;
use App\Models\Bagian;
use App\Models\Pengajuan;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use App\Services\PengajuanWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActiveApprovalAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_approval_stage_counts_are_calculated_with_one_database_query(): void
    {
        $role = Role::create(['nama_role' => 'Peserta', 'slug' => 'peserta']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $peserta = Peserta::create([
            'user_id' => $user->id,
            'universitas' => 'Universitas Test',
            'jurusan' => 'Teknik',
        ]);
        $bagian = Bagian::create(['nama_bagian' => 'Bagian Performance']);

        foreach ([1, 2, 2] as $index => $activeStep) {
            $pengajuan = Pengajuan::create([
                'peserta_id' => $peserta->id,
                'bagian_tujuan_id' => $bagian->id,
                'jenis_pengajuan' => 'PKL',
                'tanggal_mulai' => now()->addWeek()->toDateString(),
                'tanggal_selesai' => now()->addMonths(2)->toDateString(),
                'status' => 'proses_approval',
            ]);

            foreach (range($activeStep, 4) as $urutan) {
                ApprovalWorkflow::create([
                    'pengajuan_id' => $pengajuan->id,
                    'urutan' => $urutan,
                    'status' => 'menunggu',
                ]);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $counts = app(PengajuanWorkflowService::class)->hitungTahapAktif();
        $queries = DB::getQueryLog();

        $this->assertSame(1, $counts[1] ?? 0);
        $this->assertSame(2, $counts[2] ?? 0);
        $this->assertCount(1, $queries, 'Active-stage aggregation must stay a single SQL query.');
    }
}
