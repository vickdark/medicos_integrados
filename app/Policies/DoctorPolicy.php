<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Doctor;
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

    /**
     * Determine whether the user can see the profile of a doctor.
     */
    public function view(User $user, Doctor $doctor): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
