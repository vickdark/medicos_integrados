<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Turn;
use App\Models\User;

class TurnPolicy
{
    /**
     * Determine whether the user can manage the day's queue. Doctors only see
     * their own queue.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist)
            || ($user->hasRole(UserRole::Doctor) && $user->doctor !== null);
    }

    /**
     * Determine whether the user can hand out turns.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    /**
     * Determine whether the user can call, finish, requeue or cancel the turn.
     */
    public function updateStatus(User $user, Turn $turn): bool
    {
        if (! $turn->status->isActive()) {
            return false;
        }

        return $user->hasRole(UserRole::Admin, UserRole::Receptionist)
            || ($user->hasRole(UserRole::Doctor) && $user->doctor?->id === $turn->doctor_id);
    }
}
