<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Bagian;
use App\Models\User;

class BagianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function view(User $user, Bagian $bagian): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function update(User $user, Bagian $bagian): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function delete(User $user, Bagian $bagian): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }
}
