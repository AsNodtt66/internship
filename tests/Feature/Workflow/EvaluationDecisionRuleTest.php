<?php

namespace Tests\Feature\Workflow;

use App\Models\Bagian;
use App\Models\Evaluasi;
use App\Models\Pengajuan;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use App\Services\PengajuanWorkflowService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationDecisionRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_score_does_not_automatically_override_pic_manual_decision(): void
    {
        $this->seed(RoleSeeder::class);

        $picRole = Role::where('slug', 'pic')->firstOrFail();
        $pesertaRole = Role::where('slug', 'peserta')->firstOrFail();

        $pic = User::factory()->create(['role_id' => $picRole->id, 'is_active' => true]);
        $participantUser = User::factory()->create(['role_id' => $pesertaRole->id, 'is_active' => true]);
        $peserta = Peserta::create([
            'user_id' => $participantUser->id,
            'universitas' => 'Test University',
            'jurusan' => 'Test Major',
        ]);
        $bagian = Bagian::create(['nama_bagian' => 'Test Bagian']);
        $pengajuan = Pengajuan::create([
            'peserta_id' => $peserta->id,
            'bagian_tujuan_id' => $bagian->id,
            'jenis_pengajuan' => 'PKL',
            'tanggal_mulai' => now()->subMonth()->toDateString(),
            'tanggal_selesai' => now()->addMonth()->toDateString(),
            'status' => 'berjalan',
        ]);
        $evaluasi = Evaluasi::create([
            'pengajuan_id' => $pengajuan->id,
            'jadwal_evaluasi' => now()->toDateString(),
        ]);

        $service = app(PengajuanWorkflowService::class);
        $result = $service->inputHasilAkhirManual(
            $evaluasi,
            $pic,
            'selesai',
            60.0, // intentionally below KKM=70
            'P8 regression: keputusan akhir mengikuti hasil evaluasi manual PIC.',
        );

        $this->assertSame('selesai', $result->hasil);
        $this->assertSame('selesai', $pengajuan->fresh()->status);
        $this->assertSame('60.00', (string) $result->nilai_akhir);
    }
}
