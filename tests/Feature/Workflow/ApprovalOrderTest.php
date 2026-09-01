<?php

namespace Tests\Feature\Workflow;

use App\Filament\Pages\TugasSaya;
use App\Models\ApprovalWorkflow;
use App\Models\Bagian;
use App\Models\Pengajuan;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use App\Services\PengajuanWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ApprovalOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_cannot_be_processed_out_of_order(): void
    {
        $roles = collect([
            'peserta' => 'Peserta',
            'pic' => 'PIC',
            'gm' => 'GM',
            'kabag_sdm' => 'Kabag SDM',
            'staff_sdm' => 'Staff SDM',
            'kepala_bagian' => 'Kepala Bagian',
        ])->mapWithKeys(fn (string $name, string $slug) => [
            $slug => Role::create(['nama_role' => $name, 'slug' => $slug]),
        ]);

        $kepala = User::factory()->create(['role_id' => $roles['kepala_bagian']->id]);
        $bagian = Bagian::create(['nama_bagian' => 'Produksi', 'kepala_bagian_id' => $kepala->id]);
        $pesertaUser = User::factory()->create(['role_id' => $roles['peserta']->id]);
        $pic = User::factory()->create(['role_id' => $roles['pic']->id]);
        $kabag = User::factory()->create(['role_id' => $roles['kabag_sdm']->id]);

        $peserta = Peserta::create([
            'user_id' => $pesertaUser->id,
            'universitas' => 'Universitas Test',
            'jurusan' => 'Teknik',
        ]);

        $pengajuan = Pengajuan::create([
            'peserta_id' => $peserta->id,
            'bagian_tujuan_id' => $bagian->id,
            'jenis_pengajuan' => 'PKL',
            'tanggal_mulai' => now()->addWeek()->toDateString(),
            'tanggal_selesai' => now()->addMonths(2)->toDateString(),
            'status' => 'verifikasi_dokumen',
        ]);

        $this->actingAs($pic);
        $workflow = app(PengajuanWorkflowService::class);
        $pengajuan = $workflow->rekapDanMulaiApproval($pengajuan, 'AGENDA-ORDER');

        $secondStep = $pengajuan->approvalWorkflows()->where('urutan', 2)->firstOrFail();

        $this->actingAs($kabag);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Belum giliran tahapan ini');
        $workflow->tandatanganiLangkah($secondStep, $kabag);
    }

    public function test_task_page_resolves_the_active_approval_workflow_model(): void
    {
        $roles = collect([
            'peserta' => 'Peserta',
            'pic' => 'PIC',
            'gm' => 'GM',
            'kepala_bagian' => 'Kepala Bagian',
        ])->mapWithKeys(fn (string $name, string $slug) => [
            $slug => Role::create(['nama_role' => $name, 'slug' => $slug]),
        ]);

        $kepala = User::factory()->create(['role_id' => $roles['kepala_bagian']->id]);
        $bagian = Bagian::create(['nama_bagian' => 'Produksi', 'kepala_bagian_id' => $kepala->id]);
        $pesertaUser = User::factory()->create(['role_id' => $roles['peserta']->id]);
        $pic = User::factory()->create(['role_id' => $roles['pic']->id]);
        $gm = User::factory()->create(['role_id' => $roles['gm']->id]);
        $peserta = Peserta::create(['user_id' => $pesertaUser->id, 'universitas' => 'Universitas Test', 'jurusan' => 'Teknik']);
        $pengajuan = Pengajuan::create([
            'peserta_id' => $peserta->id,
            'bagian_tujuan_id' => $bagian->id,
            'jenis_pengajuan' => 'PKL',
            'tanggal_mulai' => now()->addWeek()->toDateString(),
            'tanggal_selesai' => now()->addMonths(2)->toDateString(),
            'status' => 'verifikasi_dokumen',
        ]);

        $this->actingAs($pic);
        $pengajuan = app(PengajuanWorkflowService::class)->rekapDanMulaiApproval($pengajuan, 'AGENDA-TUGAS');

        $this->actingAs($gm);
        $method = new \ReflectionMethod(TugasSaya::class, 'cariGiliranSaya');
        $method->setAccessible(true);
        $step = $method->invoke(new TugasSaya, $pengajuan, 'gm');

        $this->assertInstanceOf(ApprovalWorkflow::class, $step);
        $this->assertSame(1, $step->urutan);
    }
}
