<?php

namespace App\Http\Requests;

use App\Concerns\PatientValidationRules;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class UpdatePatientRequest extends FormRequest
{
    use PatientValidationRules;

    /**
     * Fields a patient is allowed to update on their own record.
     *
     * @var list<string>
     */
    public const SELF_SERVICE_FIELDS = [
        'phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * Basic data a patient provides until the first consultation. Clinical
     * history is only registered by the medical staff.
     *
     * @var list<string>
     */
    public const PRELIMINARY_FIELDS = [
        'document_number',
        'birth_date',
        'gender',
        'blood_type',
    ];

    /**
     * Basic data that must be filled in while the patient can still provide it.
     *
     * @var list<string>
     */
    public const REQUIRED_PRELIMINARY_FIELDS = [
        'document_number',
        'birth_date',
        'gender',
        'phone',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('patient'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->patientRules($this->route('patient')->id);

        if ($this->user()->hasRole(UserRole::Patient)) {
            $isPreliminary = $this->user()->can('fillPreliminaryData', $this->route('patient'));

            $rules = Arr::only($rules, $isPreliminary
                ? [...self::SELF_SERVICE_FIELDS, ...self::PRELIMINARY_FIELDS]
                : self::SELF_SERVICE_FIELDS);

            foreach ($isPreliminary ? self::REQUIRED_PRELIMINARY_FIELDS : [] as $field) {
                $rules[$field] = ['required', ...array_values(array_filter($rules[$field], fn ($rule) => $rule !== 'nullable'))];
            }

            return $rules;
        }

        return $rules;
    }
}
