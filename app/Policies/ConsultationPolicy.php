<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;

class ConsultationPolicy
{
    /**
     * Determine whether the user can view the consultation.
     */
    public function view(User $user, Consultation $consultation): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Doctor => $consultation->doctor_id === $user->doctor?->id
                || (bool) $user->doctor?->treats($consultation->patient),
            UserRole::Patient => $consultation->patient_id === $user->patient?->id,
            UserRole::Receptionist => false,
        };
    }

    /**
     * Determine whether the user can register a consultation for the patient.
     */
    public function create(User $user, Patient $patient): bool
    {
        return $user->hasRole(UserRole::Doctor)
            && (bool) $user->doctor?->treats($patient);
    }

    /**
     * Determine whether the user can open the prescription PDF. The prescribing
     * doctor gets the official copy; the administrator only gets a reference copy
     * marked as not valid.
     */
    public function downloadPrescription(User $user, Consultation $consultation): bool
    {
        return $user->hasRole(UserRole::Admin) || $this->issuePrescription($user, $consultation);
    }

    /**
     * Determine whether the user is the doctor who wrote the prescription, the only
     * one who can issue it officially and send it to the patient.
     */
    public function issuePrescription(User $user, Consultation $consultation): bool
    {
        return $user->hasRole(UserRole::Doctor)
            && $consultation->doctor_id === $user->doctor?->id;
    }

    /**
     * Determine whether the user can email the prescription to the patient.
     */
    public function emailPrescription(User $user, Consultation $consultation): bool
    {
        return $this->issuePrescription($user, $consultation);
    }

    /**
     * Determine whether the user can upload or remove attachments of the consultation.
     */
    public function manageAttachments(User $user, Consultation $consultation): bool
    {
        return $user->hasRole(UserRole::Doctor)
            && $consultation->doctor_id === $user->doctor?->id;
    }
}
