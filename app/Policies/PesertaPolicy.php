<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Peserta;
use App\Models\User;

class PesertaPolicy
{
    public function viewAny(User $user): bool { return $user->hasRole(RoleSlug::PIC); }
    public function view(User $user, Peserta $peserta): bool { return $user->hasRole(RoleSlug::PIC); }
    public function create(User $user): bool { return $user->hasRole(RoleSlug::PIC); }
    public function update(User $user, Peserta $peserta): bool { return $user->hasRole(RoleSlug::PIC); }

    // P8: participant history must be retained; archive/deactivate the account instead.
    public function delete(User $user, Peserta $peserta): bool { return false; }
    public function deleteAny(User $user): bool { return false; }
    public function forceDelete(User $user, Peserta $peserta): bool { return false; }
    public function forceDeleteAny(User $user): bool { return false; }
    public function restore(User $user, Peserta $peserta): bool { return false; }
}
