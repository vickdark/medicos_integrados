<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Determine whether the user can review the audit trail.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
