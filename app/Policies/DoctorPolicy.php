<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class DoctorPolicy
{
    /**
     * Determine whether the user can manage the medical staff.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can register doctors.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
