<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Doctor::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'specialty_id' => ['required', Rule::exists(Specialty::class, 'id')],
            'license_number' => ['required', 'string', 'max:50', Rule::unique(Doctor::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'consultation_fee' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
