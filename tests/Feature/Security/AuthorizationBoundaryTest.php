<?php

namespace Tests\Feature\Security;

use App\Models\Bagian;
use App\Models\Pengajuan;
use App\Models\PenugasanPembimbing;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use App\Support\Authorization\PengajuanAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_kepala_bagian_only_sees_applications_in_owned_bagian(): void
    {
        $role = $this->role('kepala_bagian', 'Kepala Bagian');
        $kepalaA = User::factory()->create(['role_id' => $role->id]);
        $kepalaB = User::factory()->create(['role_id' => $role->id]);

        $bagianA = Bagian::create(['nama_bagian' => 'A', 'kepala_bagian_id' => $kepalaA->id]);
        $bagianB = Bagian::create(['nama_bagian' => 'B', 'kepala_bagian_id' => $kepalaB->id]);
        $applicationA = $this->applicationIn($bagianA);
        $applicationB = $this->applicationIn($bagianB);

        $ids = PengajuanAccess::scope(Pengajuan::query(), $kepalaA)->pluck('id')->all();

        $this->assertContains($applicationA->id, $ids);
        $this->assertNotContains($applicationB->id, $ids);
        $this->assertTrue($kepalaA->can('view', $applicationA));
        $this->assertFalse($kepalaA->can('view', $applicationB));
    }

    public function test_mentor_only_sees_assigned_applications(): void
    {
        $mentorRole = $this->role('pembimbing_lapangan', 'Pembimbing Lapangan');
        $picRole = $this->role('pic', 'PIC');
        $mentorA = User::factory()->create(['role_id' => $mentorRole->id]);
        $mentorB = User::factory()->create(['role_id' => $mentorRole->id]);
        $pic = User::factory()->create(['role_id' => $picRole->id]);

        $bagian = Bagian::create(['nama_bagian' => 'Teknologi']);
        $assigned = $this->applicationIn($bagian);
        $other = $this->applicationIn($bagian);

        PenugasanPembimbing::create([
            'pengajuan_id' => $assigned->id,
            'pembimbing_id' => $mentorA->id,
            'nama_pembimbing' => $mentorA->name,
            'status' => 'disetujui',
            'diusulkan_oleh' => $pic->id,
            'diusulkan_at' => now(),
            'ditetapkan_oleh' => $pic->id,
            'ditetapkan_at' => now(),
        ]);
        PenugasanPembimbing::create([
            'pengajuan_id' => $other->id,
            'pembimbing_id' => $mentorB->id,
            'nama_pembimbing' => $mentorB->name,
            'status' => 'disetujui',
            'diusulkan_oleh' => $pic->id,
            'diusulkan_at' => now(),
            'ditetapkan_oleh' => $pic->id,
            'ditetapkan_at' => now(),
        ]);

        $ids = PengajuanAccess::scope(Pengajuan::query(), $mentorA)->pluck('id')->all();

        $this->assertContains($assigned->id, $ids);
        $this->assertNotContains($other->id, $ids);
        $this->assertTrue($mentorA->can('view', $assigned));
        $this->assertFalse($mentorA->can('view', $other));
    }

    private function role(string $slug, string $name): Role
    {
        return Role::firstOrCreate(['slug' => $slug], ['nama_role' => $name]);
    }

    private function applicationIn(Bagian $bagian): Pengajuan
    {
        $participantRole = $this->role('peserta', 'Peserta');
        $participant = User::factory()->create(['role_id' => $participantRole->id]);
        $peserta = Peserta::create([
            'user_id' => $participant->id,
            'universitas' => 'Universitas Test',
            'jurusan' => 'Teknik',
        ]);

        return Pengajuan::create([
            'peserta_id' => $peserta->id,
            'bagian_tujuan_id' => $bagian->id,
            'jenis_pengajuan' => 'PKL',
            'tanggal_mulai' => now()->addWeek()->toDateString(),
            'tanggal_selesai' => now()->addMonths(2)->toDateString(),
            'status' => 'diajukan',
        ]);
    }
}
