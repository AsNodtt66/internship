<?php

namespace Tests\Feature\Workflow;

use App\Models\Bagian;
use App\Models\DokumenPersyaratan;
use App\Models\Pengajuan;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use App\Services\PengajuanWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SubmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private PengajuanWorkflowService $workflow;
    private User $pesertaUser;
    private User $pic;
    private Pengajuan $pengajuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflow = app(PengajuanWorkflowService::class);

        $pesertaRole = Role::create(['nama_role' => 'Peserta', 'slug' => 'peserta']);
        $picRole = Role::create(['nama_role' => 'PIC', 'slug' => 'pic']);
        Role::create(['nama_role' => 'GM', 'slug' => 'gm']);
        Role::create(['nama_role' => 'Kabag SDM', 'slug' => 'kabag_sdm']);
        Role::create(['nama_role' => 'Staff SDM', 'slug' => 'staff_sdm']);
        Role::create(['nama_role' => 'Kepala Bagian', 'slug' => 'kepala_bagian']);

        $bagian = Bagian::create(['nama_bagian' => 'Teknologi Informasi']);

        $this->pesertaUser = User::factory()->create(['role_id' => $pesertaRole->id]);
        $this->pic = User::factory()->create(['role_id' => $picRole->id]);

        $peserta = Peserta::create([
            'user_id' => $this->pesertaUser->id,
            'universitas' => 'Universitas Test',
            'jurusan' => 'Informatika',
        ]);

        $this->pengajuan = Pengajuan::create([
            'peserta_id' => $peserta->id,
            'bagian_tujuan_id' => $bagian->id,
            'jenis_pengajuan' => 'PKL',
            'tanggal_mulai' => now()->addWeek()->toDateString(),
            'tanggal_selesai' => now()->addMonths(2)->toDateString(),
            'status' => 'draft',
        ]);
    }

    public function test_participant_can_submit_draft_once(): void
    {
        $this->actingAs($this->pesertaUser);

        $result = $this->workflow->ajukan($this->pengajuan);

        $this->assertSame('diajukan', $result->fresh()->status);
        $this->assertNotNull($result->fresh()->diajukan_at);
        $this->assertDatabaseHas('riwayat_status', [
            'pengajuan_id' => $this->pengajuan->id,
            'status_sebelumnya' => 'draft',
            'status_baru' => 'diajukan',
        ]);

        $this->expectException(RuntimeException::class);
        $this->workflow->ajukan($result->fresh());
    }

    public function test_non_pic_cannot_verify_documents(): void
    {
        $dokumen = DokumenPersyaratan::create([
            'pengajuan_id' => $this->pengajuan->id,
            'jenis_dokumen' => 'CV',
            'file_path' => 'pengajuan/test/cv.pdf',
            'status_verifikasi' => 'menunggu',
        ]);

        $this->actingAs($this->pesertaUser);

        $this->expectException(RuntimeException::class);
        $this->workflow->verifikasiDokumen($dokumen, 'lengkap', $this->pesertaUser);
    }

    public function test_pic_must_give_reason_when_document_is_incomplete(): void
    {
        $dokumen = DokumenPersyaratan::create([
            'pengajuan_id' => $this->pengajuan->id,
            'jenis_dokumen' => 'CV',
            'file_path' => 'pengajuan/test/cv.pdf',
            'status_verifikasi' => 'menunggu',
        ]);

        $this->actingAs($this->pic);

        $this->expectException(RuntimeException::class);
        $this->workflow->verifikasiDokumen($dokumen, 'tidak_lengkap', $this->pic);
    }

    public function test_starting_approval_creates_exactly_four_ordered_steps(): void
    {
        $this->actingAs($this->pic);
        $this->pengajuan->update(['status' => 'verifikasi_dokumen']);

        $result = $this->workflow->rekapDanMulaiApproval($this->pengajuan->fresh(), 'AGENDA-001');

        $this->assertSame('proses_approval', $result->status);
        $this->assertSame('AGENDA-001', $result->nomor_agenda);
        $this->assertSame([1, 2, 3, 4], $result->approvalWorkflows()->orderBy('urutan')->pluck('urutan')->all());
        $this->assertSame(4, $result->approvalWorkflows()->count());
    }
}
