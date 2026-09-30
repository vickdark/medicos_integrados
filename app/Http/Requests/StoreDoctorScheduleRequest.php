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
            'days' => ['required_without:day_of_week', 'array', 'min:1'],
            'days.*' => ['integer', 'between:0,6', 'distinct'],
            'day_of_week' => ['required_without:days', 'integer', 'between:0,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ];
    }

    /**
     * Days of the week the block applies to.
     *
     * @return list<int>
     */
    public function dayList(): array
    {
        return array_values(array_map('intval', $this->has('days')
            ? (array) $this->input('days')
            : [$this->input('day_of_week')]));
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

                $overlapping = collect($this->dayList())
                    ->filter(fn (int $day): bool => $this->route('doctor')->schedules()
                        ->where('day_of_week', $day)
                        ->where('starts_at', '<', $this->input('ends_at'))
                        ->where('ends_at', '>', $this->input('starts_at'))
                        ->exists())
                    ->map(fn (int $day): string => DoctorSchedule::DAY_NAMES[$day]);

                if ($overlapping->isNotEmpty()) {
                    $validator->errors()->add('starts_at', 'El horario se cruza con otro bloque el día: '.$overlapping->implode(', ').'.');
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
            'days.required_without' => 'Elige al menos un día.',
            'ends_at.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
