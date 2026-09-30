<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can manage accounts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can create accounts.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can update the account.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can activate or deactivate the account. Admins
     * cannot lock themselves out.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::Admin) && ! $user->is($model);
    }
}
