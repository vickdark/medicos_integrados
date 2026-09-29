<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;

class DoctorSchedulePolicy
{
    /**
     * Determine whether the user can view and add office hours for the doctor.
     */
    public function manage(User $user, Doctor $doctor): bool
    {
        return $user->hasRole(UserRole::Admin)
            || ($user->hasRole(UserRole::Doctor) && $user->doctor?->id === $doctor->id);
    }

    /**
     * Determine whether the user can remove the office hours block.
     */
    public function delete(User $user, DoctorSchedule $schedule): bool
    {
        return $this->manage($user, $schedule->doctor);
    }
}
