<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\Notifikasi;
use App\Models\User;

class NotifikasiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(RoleSlug::adminPanelRoles());
    }

    public function view(User $user, Notifikasi $notifikasi): bool
    {
        return $user->hasRole(RoleSlug::PIC) || $notifikasi->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function update(User $user, Notifikasi $notifikasi): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function delete(User $user, Notifikasi $notifikasi): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }
}
