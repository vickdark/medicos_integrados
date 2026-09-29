<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determine whether the user can list appointments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the appointment.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        return match ($user->role) {
            UserRole::Admin, UserRole::Receptionist => true,
            UserRole::Doctor => $appointment->doctor_id === $user->doctor?->id,
            UserRole::Patient => $appointment->patient_id === $user->patient?->id,
        };
    }

    /**
     * Determine whether the user can create or request appointments.
     */
    public function create(User $user): bool
    {
        return $user->isStaff() || $user->patient !== null;
    }

    /**
     * Determine whether the user can change the appointment status.
     */
    public function updateStatus(User $user, Appointment $appointment): bool
    {
        if (! $appointment->status->isActive()) {
            return false;
        }

        return match ($user->role) {
            UserRole::Admin, UserRole::Receptionist => true,
            UserRole::Doctor => $appointment->doctor_id === $user->doctor?->id,
            UserRole::Patient => $appointment->patient_id === $user->patient?->id,
        };
    }
}
