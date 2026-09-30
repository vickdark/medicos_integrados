<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the date window requested by the appointments calendar. Authorization
 * is handled by the controller through the appointment policy.
 */
class CalendarRangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
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
            'to.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
        ];
    }

    /**
     * Get the first day of the window.
     */
    public function rangeStart(): CarbonImmutable
    {
        return $this->date('from')->startOfDay()->toImmutable();
    }

    /**
     * Get the last day of the window, capped at 62 days after the first.
     */
    public function rangeEnd(): CarbonImmutable
    {
        return $this->date('to')->endOfDay()->toImmutable()->min($this->rangeStart()->addDays(62)->endOfDay());
    }
}
