<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RescheduleAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('reschedule', $this->route('appointment'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'reason' => ['sometimes', 'required', 'string', 'max:255', Rule::prohibitedIf(! $this->canChangeDoctor())],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000', Rule::prohibitedIf(! $this->canChangeDoctor())],
            'doctor_id' => [
                'nullable',
                Rule::prohibitedIf(! $this->canChangeDoctor()),
                Rule::exists(Doctor::class, 'id'),
            ],
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

                /** @var Appointment $appointment */
                $appointment = $this->route('appointment');
                $scheduledAt = $this->date('scheduled_at');
                $doctor = $this->targetDoctor();

                if ($doctor->is($appointment->doctor) && $scheduledAt->equalTo($appointment->scheduled_at)) {
                    $validator->errors()->add('scheduled_at', 'Elige una fecha u hora distinta a la actual.');

                    return;
                }

                if (! $doctor->isAvailableAt($scheduledAt)) {
                    $validator->errors()->add('scheduled_at', 'El médico no atiende en ese día u horario.');

                    return;
                }

                if ($doctor->hasConflictAt($scheduledAt, $appointment->getKey())) {
                    $validator->errors()->add('scheduled_at', 'El médico ya tiene una cita en ese horario.');
                }
            },
        ];
    }

    /**
     * The doctor and the details of the appointment can be edited by the clinic, or
     * by the patient while it is still not confirmed.
     */
    public function canChangeDoctor(): bool
    {
        return $this->user()->can('editDetails', $this->route('appointment'));
    }

    /**
     * The doctor who will attend the appointment: the chosen one, or the current one.
     */
    public function targetDoctor(): Doctor
    {
        return $this->filled('doctor_id')
            ? Doctor::query()->findOrFail($this->integer('doctor_id'))
            : $this->route('appointment')->doctor;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'doctor_id.prohibited' => 'Ya no puedes cambiar el médico: la cita está confirmada.',
            'reason.prohibited' => 'Ya no puedes editar el motivo: la cita está confirmada.',
            'notes.prohibited' => 'Ya no puedes editar las notas: la cita está confirmada.',
            'scheduled_at.after' => 'La cita debe reprogramarse a una fecha futura.',
        ];
    }
}
