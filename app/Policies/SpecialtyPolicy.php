<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Specialty;
use App\Models\User;

class SpecialtyPolicy
{
    /**
     * Determine whether the user can manage the specialties catalog.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can create specialties.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can update the specialty.
     */
    public function update(User $user, Specialty $specialty): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can delete the specialty. Specialties with doctors are kept.
     */
    public function delete(User $user, Specialty $specialty): bool
    {
        return $user->hasRole(UserRole::Admin) && $specialty->doctors()->doesntExist();
    }
}
