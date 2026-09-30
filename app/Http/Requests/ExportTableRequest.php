<?php

namespace App\Http\Requests;

use App\Enums\ExportFormat;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ExportTableRequest extends TableQueryRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'format' => ['required', Rule::enum(ExportFormat::class)],
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
            ...parent::messages(),
            'format.required' => 'Indica el formato de exportación.',
            'format.enum' => 'El formato de exportación debe ser Excel o PDF.',
        ];
    }

    /**
     * Get the requested export format.
     */
    public function exportFormat(): ExportFormat
    {
        return $this->enum('format', ExportFormat::class);
    }
}
