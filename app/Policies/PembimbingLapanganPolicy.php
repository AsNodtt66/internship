<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\PembimbingLapangan;
use App\Models\User;

class PembimbingLapanganPolicy
{
    private function canManage(User $user): bool
    {
        return $user->hasAnyRole([RoleSlug::PIC, RoleSlug::KEPALA_BAGIAN]);
    }

    public function viewAny(User $user): bool { return $this->canManage($user); }
    public function view(User $user, PembimbingLapangan $pembimbing): bool { return $this->canManage($user); }
    public function create(User $user): bool { return $this->canManage($user); }
    public function update(User $user, PembimbingLapangan $pembimbing): bool { return $this->canManage($user); }
    public function delete(User $user, PembimbingLapangan $pembimbing): bool { return $this->canManage($user); }
    public function deleteAny(User $user): bool { return $this->canManage($user); }
}
