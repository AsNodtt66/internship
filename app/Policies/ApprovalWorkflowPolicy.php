<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\ApprovalWorkflow;
use App\Models\User;

class ApprovalWorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function view(User $user, ApprovalWorkflow $workflow): bool
    {
        if ($user->hasRole(RoleSlug::PIC) || $workflow->penandatangan_id === $user->id) {
            return true;
        }

        $expectedRole = match ((int) $workflow->urutan) {
            1 => RoleSlug::GM,
            2 => RoleSlug::KABAG_SDM,
            3 => RoleSlug::STAFF_SDM,
            4 => RoleSlug::KEPALA_BAGIAN,
            default => null,
        };

        if (! $expectedRole || ! $user->hasRole($expectedRole)) {
            return false;
        }

        if ($expectedRole === RoleSlug::KEPALA_BAGIAN) {
            return $workflow->pengajuan?->bagianTujuan?->kepala_bagian_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool { return $user->hasRole(RoleSlug::PIC); }
    public function update(User $user, ApprovalWorkflow $workflow): bool { return $user->hasRole(RoleSlug::PIC); }
    public function delete(User $user, ApprovalWorkflow $workflow): bool { return $user->hasRole(RoleSlug::PIC); }
    public function deleteAny(User $user): bool { return $user->hasRole(RoleSlug::PIC); }
}
