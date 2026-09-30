<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can list patients.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can view the patient's profile.
     */
    public function view(User $user, Patient $patient): bool
    {
        return match ($user->role) {
            UserRole::Admin, UserRole::Receptionist => true,
            UserRole::Doctor => (bool) $user->doctor?->treats($patient),
            UserRole::Patient => $patient->user_id === $user->id,
        };
    }

    /**
     * Determine whether the user can view the patient's clinical history.
     */
    public function viewMedicalHistory(User $user, Patient $patient): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Doctor => (bool) $user->doctor?->treats($patient),
            UserRole::Patient => $patient->user_id === $user->id,
            UserRole::Receptionist => false,
        };
    }

    /**
     * Determine whether the user can register patients.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can update the patient's profile.
     * Patients may only update their own contact details.
     */
    public function update(User $user, Patient $patient): bool
    {
        return match ($user->role) {
            UserRole::Admin, UserRole::Receptionist => true,
            UserRole::Doctor => (bool) $user->doctor?->treats($patient),
            UserRole::Patient => $patient->user_id === $user->id,
        };
    }

    /**
     * Determine whether the patient can still fill in their preliminary medical
     * data. It is open until a doctor attends them for the first time.
     */
    public function fillPreliminaryData(User $user, Patient $patient): bool
    {
        return $user->hasRole(UserRole::Patient)
            && $patient->user_id === $user->id
            && ! $patient->hasBeenAttended();
    }

    /**
     * Determine whether the user can review who accessed the patient's record.
     */
    public function viewAuditTrail(User $user, Patient $patient): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * Determine whether the user can delete the patient.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
