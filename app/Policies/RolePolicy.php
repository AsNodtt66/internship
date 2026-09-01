<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    // Core roles are seeded system configuration, not user-defined CRUD data.
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function delete(User $user, Role $role): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
