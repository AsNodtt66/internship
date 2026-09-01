<?php

namespace Tests\Feature\Security;

use App\Models\Bagian;
use App\Models\Pengajuan;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_download_private_document(): void
    {
        [$owner, $pengajuan] = $this->participantWithApplication('owner@example.test');
        Storage::fake('documents')->put('pengajuan/owner/cv.pdf', '%PDF-test');
        $pengajuan->update(['file_cv' => 'pengajuan/owner/cv.pdf']);

        $response = $this->get(route('documents.pengajuan', [$pengajuan, 'file_cv']));

        $response->assertRedirect(route('login'));
    }

    public function test_participant_can_download_own_document_but_not_another_participants_document(): void
    {
        [$owner, $ownedApplication] = $this->participantWithApplication('owner@example.test');
        [$attacker, $otherApplication] = $this->participantWithApplication('attacker@example.test');

        $documents = Storage::fake('documents');
        $documents->put('pengajuan/owner/cv.pdf', '%PDF-owner');
        $documents->put('pengajuan/other/cv.pdf', '%PDF-other');
        $documents->assertExists('pengajuan/owner/cv.pdf');
        $ownedApplication->update(['file_cv' => 'pengajuan/owner/cv.pdf']);
        $otherApplication->update(['file_cv' => 'pengajuan/other/cv.pdf']);

        $response = $this->actingAs($owner)
            ->get(route('documents.pengajuan', [$ownedApplication, 'file_cv']));

        $response->assertOk();
        $cacheControl = array_map('trim', explode(',', (string) $response->headers->get('Cache-Control')));
        $this->assertContains('private', $cacheControl);
        $this->assertContains('no-store', $cacheControl);
        $this->assertContains('max-age=0', $cacheControl);

        $this->actingAs($attacker)
            ->get(route('documents.pengajuan', [$ownedApplication, 'file_cv']))
            ->assertForbidden();
    }

    public function test_unknown_document_field_returns_not_found_even_for_owner(): void
    {
        [$owner, $pengajuan] = $this->participantWithApplication('owner@example.test');

        $this->actingAs($owner)
            ->get(route('documents.pengajuan', [$pengajuan, 'password']))
            ->assertNotFound();
    }

    /** @return array{0:User,1:Pengajuan} */
    private function participantWithApplication(string $email): array
    {
        $role = Role::firstOrCreate(['slug' => 'peserta'], ['nama_role' => 'Peserta']);
        $user = User::factory()->create(['email' => $email, 'role_id' => $role->id]);
        $peserta = Peserta::create([
            'user_id' => $user->id,
            'universitas' => 'Universitas Test',
            'jurusan' => 'Teknik Informatika',
        ]);
        $bagian = Bagian::create(['nama_bagian' => 'Bagian '.uniqid()]);
        $pengajuan = Pengajuan::create([
            'peserta_id' => $peserta->id,
            'bagian_tujuan_id' => $bagian->id,
            'jenis_pengajuan' => 'PKL',
            'tanggal_mulai' => now()->addWeek()->toDateString(),
            'tanggal_selesai' => now()->addMonths(2)->toDateString(),
            'status' => 'draft',
        ]);

        return [$user, $pengajuan];
    }
}
