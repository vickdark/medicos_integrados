<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Medication;
use App\Models\User;

class MedicationPolicy
{
    /**
     * Determine whether the user can see the medications catalog.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Doctor);
    }

    /**
     * Determine whether the user can create medications.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Doctor);
    }

    /**
     * Determine whether the user can update the medication.
     */
    public function update(User $user, Medication $medication): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Doctor);
    }

    /**
     * Determine whether the user can delete the medication. Prescriptions keep
     * their own copy of the medication text, so deleting never alters them.
     */
    public function delete(User $user, Medication $medication): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Doctor);
    }
}
