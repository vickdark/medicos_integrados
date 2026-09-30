<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ClinicalHistoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewMedicalHistory', $this->route('patient'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
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
            'from.date_format' => 'La fecha inicial no es válida.',
            'to.date_format' => 'La fecha final no es válida.',
            'to.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
        ];
    }

    /**
     * First day of the requested period, or null for no lower bound.
     */
    public function periodStart(): ?CarbonImmutable
    {
        return $this->filled('from') ? CarbonImmutable::parse($this->string('from')->toString())->startOfDay() : null;
    }

    /**
     * Last day of the requested period, or null for no upper bound.
     */
    public function periodEnd(): ?CarbonImmutable
    {
        return $this->filled('to') ? CarbonImmutable::parse($this->string('to')->toString())->endOfDay() : null;
    }
}
