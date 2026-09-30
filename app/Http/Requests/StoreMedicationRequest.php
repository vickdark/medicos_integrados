<?php

namespace App\Http\Requests;

use App\Concerns\MedicationValidationRules;
use App\Models\Medication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicationRequest extends FormRequest
{
    use MedicationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Medication::class);
    }

    /**
     * Get the validation rules that apply to the request. The `inline` flag marks
     * a medication created from the consultation form, which stays on that page.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->medicationRules(),
            'inline' => ['nullable', 'boolean'],
        ];
    }
}
