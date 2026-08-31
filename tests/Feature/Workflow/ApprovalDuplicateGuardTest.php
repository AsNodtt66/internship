<?php

namespace Tests\Feature\Workflow;

use App\Models\Bagian;
use App\Models\Pengajuan;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use App\Services\PengajuanWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ApprovalDuplicateGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_approval_step_cannot_be_signed_twice(): void
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
        $gm = User::factory()->create(['role_id' => $roles['gm']->id]);
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

        $workflow = app(PengajuanWorkflowService::class);
        $this->actingAs($pic);
        $pengajuan = $workflow->rekapDanMulaiApproval($pengajuan, 'AGENDA-DUP');
        $firstStep = $pengajuan->approvalWorkflows()->where('urutan', 1)->firstOrFail();

        $this->actingAs($gm);
        $workflow->tandatanganiLangkah($firstStep, $gm);

        $this->assertSame('ditandatangani', $firstStep->fresh()->status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sudah diproses sebelumnya');
        $workflow->tandatanganiLangkah($firstStep, $gm);
    }
}
