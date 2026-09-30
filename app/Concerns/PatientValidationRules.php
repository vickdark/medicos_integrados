<?php

namespace App\Concerns;

use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Validation\Rule;

trait PatientValidationRules
{
    /**
     * Fields that belong to the patient record when it is filled from a user form.
     * The email is taken from the user account instead.
     *
     * @var list<string>
     */
    public const PATIENT_FIELDS = [
        'first_name',
        'last_name',
        'document_number',
        'phone',
        'birth_date',
        'gender',
        'address',
        'blood_type',
        'allergies',
        'chronic_conditions',
        'medical_background',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * Get the validation rules used to validate patient records.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function patientRules(?int $patientId = null): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'document_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique(Patient::class)->ignore($patientId),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'address' => ['nullable', 'string', 'max:255'],
            'blood_type' => ['nullable', Rule::in(['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'])],
            'allergies' => ['nullable', 'string', 'max:2000'],
            'chronic_conditions' => ['nullable', 'string', 'max:2000'],
            'medical_background' => ['nullable', 'string', 'max:5000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
