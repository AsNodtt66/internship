<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Pengajuan;
use App\Models\User;
use App\Support\Authorization\PengajuanAccess;

class PengajuanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            ...RoleSlug::administrativeRoles(),
            RoleSlug::KEPALA_BAGIAN,
            RoleSlug::PEMBIMBING_LAPANGAN,
            RoleSlug::PESERTA,
        ]);
    }

    public function view(User $user, Pengajuan $pengajuan): bool
    {
        return PengajuanAccess::canView($user, $pengajuan);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([...RoleSlug::administrativeRoles(), RoleSlug::PESERTA]);
    }

    public function update(User $user, Pengajuan $pengajuan): bool
    {
        if ($user->hasAnyRole(RoleSlug::administrativeRoles())) {
            return true;
        }

        return $user->hasRole(RoleSlug::PESERTA)
            && $pengajuan->peserta?->user_id === $user->id
            && in_array($pengajuan->status, ['draft', 'dokumen_ditolak'], true);
    }

    public function viewEvaluation(User $user, Pengajuan $pengajuan): bool
    {
        if ($user->hasRole(RoleSlug::PIC)) {
            return true;
        }

        return $user->hasRole(RoleSlug::PEMBIMBING_LAPANGAN)
            && $pengajuan->penugasanPembimbing?->pembimbing_id === $user->id;
    }

    public function issueCompletionDocument(User $user, Pengajuan $pengajuan): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function delete(User $user, Pengajuan $pengajuan): bool
    {
        return $user->hasAnyRole([RoleSlug::PIC, RoleSlug::KABAG_SDM, RoleSlug::GM]);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::PIC, RoleSlug::KABAG_SDM, RoleSlug::GM]);
    }
}
