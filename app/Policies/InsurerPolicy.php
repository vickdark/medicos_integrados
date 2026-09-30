<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Insurer;
use App\Models\User;

class InsurerPolicy
{
    /**
     * Determine whether the user can manage the insurers catalog.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can create insurers.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can update the insurer.
     */
    public function update(User $user, Insurer $insurer): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can delete the insurer. Insurers assigned to
     * patients are kept.
     */
    public function delete(User $user, Insurer $insurer): bool
    {
        return $user->hasRole(UserRole::Admin) && $insurer->patients()->doesntExist();
    }
}
