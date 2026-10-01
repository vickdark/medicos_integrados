<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\DataSubjectRequest;
use App\Models\User;

class DataSubjectRequestPolicy
{
    /**
     * Determine whether the user can review the requests of every patient.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can answer the request, only while it is pending.
     */
    public function answer(User $user, DataSubjectRequest $request): bool
    {
        return $user->hasRole(UserRole::Admin) && $request->isPending();
    }
}
