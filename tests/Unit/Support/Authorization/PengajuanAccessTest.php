<?php

namespace Tests\Unit\Support\Authorization;

use App\Enums\RoleSlug;
use App\Models\Bagian;
use App\Models\Pengajuan;
use App\Models\PenugasanPembimbing;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use App\Support\Authorization\PengajuanAccess;
use PHPUnit\Framework\TestCase;

class PengajuanAccessTest extends TestCase
{
    private function user(int $id, RoleSlug $roleSlug): User
    {
        $role = new Role();
        $role->setRawAttributes(['slug' => $roleSlug->value], true);
        $user = new User();
        $user->setRawAttributes(['id' => $id], true);
        $user->setRelation('role', $role);
        return $user;
    }

    public function test_access_is_scoped_by_business_relationship(): void
    {
        $pic = $this->user(1, RoleSlug::PIC);
        $head = $this->user(2, RoleSlug::KEPALA_BAGIAN);
        $otherHead = $this->user(3, RoleSlug::KEPALA_BAGIAN);
        $mentor = $this->user(4, RoleSlug::PEMBIMBING_LAPANGAN);
        $participant = $this->user(5, RoleSlug::PESERTA);

        $bagian = new Bagian();
        $bagian->setRawAttributes(['kepala_bagian_id' => 2], true);
        $peserta = new Peserta();
        $peserta->setRawAttributes(['user_id' => 5], true);
        $assignment = new PenugasanPembimbing();
        $assignment->setRawAttributes(['pembimbing_id' => 4], true);
        $pengajuan = new Pengajuan();
        $pengajuan->setRelation('bagianTujuan', $bagian);
        $pengajuan->setRelation('peserta', $peserta);
        $pengajuan->setRelation('penugasanPembimbing', $assignment);

        $this->assertTrue(PengajuanAccess::canView($pic, $pengajuan));
        $this->assertTrue(PengajuanAccess::canView($head, $pengajuan));
        $this->assertFalse(PengajuanAccess::canView($otherHead, $pengajuan));
        $this->assertTrue(PengajuanAccess::canView($mentor, $pengajuan));
        $this->assertTrue(PengajuanAccess::canView($participant, $pengajuan));
    }
}
