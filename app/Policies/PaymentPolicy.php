<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can list payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Patient);
    }

    /**
     * Determine whether the user can record payments.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }
}
