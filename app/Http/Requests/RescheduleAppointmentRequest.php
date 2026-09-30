<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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

                if ($scheduledAt->equalTo($appointment->scheduled_at)) {
                    $validator->errors()->add('scheduled_at', 'Elige una fecha u hora distinta a la actual.');

                    return;
                }

                if (! $appointment->doctor->isAvailableAt($scheduledAt)) {
                    $validator->errors()->add('scheduled_at', 'El médico no atiende en ese día u horario.');

                    return;
                }

                if ($appointment->doctor->hasConflictAt($scheduledAt, $appointment->getKey())) {
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
            'scheduled_at.after' => 'La cita debe reprogramarse a una fecha futura.',
        ];
    }
}
