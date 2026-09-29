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
            return Arr::only($rules, self::SELF_SERVICE_FIELDS);
        }

        return $rules;
    }
}
