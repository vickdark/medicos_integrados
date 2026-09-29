<?php

namespace App\Http\Requests;

use App\Models\DoctorSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDoctorScheduleRequest extends FormRequest
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
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
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

                $overlaps = $this->route('doctor')->schedules()
                    ->where('day_of_week', $this->integer('day_of_week'))
                    ->where('starts_at', '<', $this->input('ends_at'))
                    ->where('ends_at', '>', $this->input('starts_at'))
                    ->exists();

                if ($overlaps) {
                    $validator->errors()->add('starts_at', 'El horario se cruza con otro bloque del mismo día.');
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
            'ends_at.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
