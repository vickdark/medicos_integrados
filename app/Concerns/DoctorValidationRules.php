<?php

namespace App\Concerns;

use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Validation\Rule;

trait DoctorValidationRules
{
    /**
     * Fields that belong to the doctor profile (not to the user account).
     *
     * @var list<string>
     */
    public const DOCTOR_FIELDS = ['specialty_id', 'license_number', 'phone', 'consultation_fee', 'bio'];

    /**
     * Get the validation rules used to validate doctor profiles.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function doctorRules(?int $doctorId = null): array
    {
        return [
            'specialty_id' => ['required', Rule::exists(Specialty::class, 'id')],
            'license_number' => ['required', 'string', 'max:50', Rule::unique(Doctor::class)->ignore($doctorId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'consultation_fee' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
