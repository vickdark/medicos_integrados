<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the search and filter parameters shared by every listing table.
 * Authorization is handled by each controller through its policy.
 */
class TableQueryRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:30'],
            'action' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', 'string', 'max:30'],
            'patient_id' => ['nullable', 'integer'],
            'pay' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
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
     * Get the trimmed search term, if any.
     */
    public function searchTerm(): ?string
    {
        $term = trim($this->string('search')->toString());

        return $term === '' ? null : $term;
    }

    /**
     * Get the active filters to send back to the page.
     *
     * @return array<string, string|int|null>
     */
    public function filters(): array
    {
        return [
            'search' => $this->searchTerm() ?? '',
            'status' => $this->string('status')->toString(),
            'action' => $this->string('action')->toString(),
            'role' => $this->string('role')->toString(),
            'patient_id' => $this->integer('patient_id') ?: null,
            'from' => $this->string('from')->toString(),
            'to' => $this->string('to')->toString(),
        ];
    }
}
