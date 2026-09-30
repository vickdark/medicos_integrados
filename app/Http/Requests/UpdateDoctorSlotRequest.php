<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorSlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage', [DoctorSchedule::class, $this->route('doctor')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slot_minutes' => ['required', 'integer', Rule::in(Doctor::SLOT_OPTIONS)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slot_minutes.in' => 'Elige una duración de cita válida.',
        ];
    }
}
