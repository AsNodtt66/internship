<?php

namespace App\Policies;

use App\Enums\RoleSlug;
use App\Models\FormFieldDefinition;
use App\Models\User;

class FormFieldDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function view(User $user, FormFieldDefinition $field): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function update(User $user, FormFieldDefinition $field): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function delete(User $user, FormFieldDefinition $field): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole(RoleSlug::PIC);
    }
}
