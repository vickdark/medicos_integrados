<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Appointment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => [
                Rule::requiredIf($this->user()->isStaff()),
                Rule::prohibitedIf(! $this->user()->isStaff()),
                Rule::exists(Patient::class, 'id'),
            ],
            'doctor_id' => ['required', Rule::exists(Doctor::class, 'id')],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $doctor = Doctor::query()->findOrFail($this->integer('doctor_id'));

                if (! $doctor->isAvailableAt($this->date('scheduled_at'))) {
                    $validator->errors()->add('scheduled_at', 'El médico no atiende en ese día u horario.');

                    return;
                }

                if ($doctor->hasConflictAt($this->date('scheduled_at'))) {
                    $validator->errors()->add('scheduled_at', 'El médico ya tiene una cita en ese horario.');
                }
            },
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
            'scheduled_at.after' => 'La cita debe programarse en una fecha futura.',
        ];
    }
}
