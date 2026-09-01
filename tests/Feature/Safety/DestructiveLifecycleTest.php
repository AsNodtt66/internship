<?php

namespace Tests\Feature\Safety;

use App\Models\Bagian;
use App\Models\Pengajuan;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use App\Policies\PesertaPolicy;
use App\Policies\UserPolicy;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestructiveLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_and_participant_delete_are_denied_by_policy(): void
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

        $this->assertFalse((new UserPolicy)->delete($pic, $participantUser));
        $this->assertFalse((new UserPolicy)->deleteAny($pic));
        $this->assertFalse((new PesertaPolicy)->delete($pic, $peserta));
        $this->assertFalse((new PesertaPolicy)->deleteAny($pic));
    }

    public function test_soft_deleting_participant_does_not_destroy_pengajuan_history(): void
    {
        $this->seed(RoleSeeder::class);
        $pesertaRole = Role::where('slug', 'peserta')->firstOrFail();
        $user = User::factory()->create(['role_id' => $pesertaRole->id, 'is_active' => true]);
        $peserta = Peserta::create([
            'user_id' => $user->id,
            'universitas' => 'Test University',
            'jurusan' => 'Test Major',
        ]);
        $bagian = Bagian::create(['nama_bagian' => 'Test Bagian']);
        $pengajuan = Pengajuan::create([
            'peserta_id' => $peserta->id,
            'bagian_tujuan_id' => $bagian->id,
            'jenis_pengajuan' => 'PKL',
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addMonth()->toDateString(),
            'status' => 'draft',
        ]);

        $peserta->delete();

        $this->assertSoftDeleted('pesertas', ['id' => $peserta->id]);
        $this->assertDatabaseHas('pengajuans', ['id' => $pengajuan->id]);
    }
}
