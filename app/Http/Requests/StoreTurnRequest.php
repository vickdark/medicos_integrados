<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use App\Enums\TurnStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Turn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A turn is issued either for today's appointment of a patient who just arrived,
 * or for a walk-in patient and the doctor who will see them.
 */
class StoreTurnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Turn::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'appointment_id' => ['nullable', Rule::exists(Appointment::class, 'id')],
            'patient_id' => ['required_without:appointment_id', 'nullable', Rule::exists(Patient::class, 'id')],
            'doctor_id' => ['required_without:appointment_id', 'nullable', Rule::exists(Doctor::class, 'id')],
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

                $appointment = $this->appointment();

                if ($appointment) {
                    if (! $appointment->scheduled_at->isToday()
                        || ! in_array($appointment->status, [AppointmentStatus::Requested, AppointmentStatus::Confirmed], true)) {
                        $validator->errors()->add('appointment_id', 'Solo se genera turno para citas activas de hoy.');

                        return;
                    }

                    if ($appointment->turns()->where('status', '!=', TurnStatus::Cancelled)->exists()) {
                        $validator->errors()->add('appointment_id', 'Esta cita ya tiene un turno.');

                        return;
                    }
                }

                $alreadyQueued = Turn::query()
                    ->today()
                    ->active()
                    ->where('patient_id', $this->patientId())
                    ->where('doctor_id', $this->doctorId())
                    ->exists();

                if ($alreadyQueued) {
                    $validator->errors()->add('patient_id', 'El paciente ya tiene un turno activo con este médico.');
                }
            },
        ];
    }

    public function appointment(): ?Appointment
    {
        return $this->filled('appointment_id')
            ? Appointment::query()->find($this->integer('appointment_id'))
            : null;
    }

    public function patientId(): int
    {
        return $this->appointment()?->patient_id ?? $this->integer('patient_id');
    }

    public function doctorId(): int
    {
        return $this->appointment()?->doctor_id ?? $this->integer('doctor_id');
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.required_without' => 'Selecciona el paciente.',
            'doctor_id.required_without' => 'Selecciona el médico que lo atenderá.',
        ];
    }
}
