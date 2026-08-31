<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->hasRole(RoleSlug::PIC); }
    public function view(User $user, User $model): bool { return $user->hasRole(RoleSlug::PIC); }
    public function create(User $user): bool { return $user->hasRole(RoleSlug::PIC); }
    public function update(User $user, User $model): bool { return $user->hasRole(RoleSlug::PIC); }

    // P8: user accounts are deactivated, never hard-deleted from the UI.
    public function delete(User $user, User $model): bool { return false; }
    public function deleteAny(User $user): bool { return false; }
    public function forceDelete(User $user, User $model): bool { return false; }
    public function forceDeleteAny(User $user): bool { return false; }
    public function restore(User $user, User $model): bool { return false; }
}
