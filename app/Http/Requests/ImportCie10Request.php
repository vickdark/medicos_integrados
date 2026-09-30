<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportCie10Request extends FormRequest
{
    /**
     * Largest accepted file, in kilobytes. The full official table weighs a few MB.
     */
    public const MAX_FILE_KB = 51200;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-cie10');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:xlsx,xls,ods,csv,txt', 'max:'.self::MAX_FILE_KB],
            'dry_run' => ['nullable', 'boolean'],
            'deactivate_missing' => ['nullable', 'boolean'],
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
            'file.required' => 'Selecciona el archivo de la tabla CIE-10.',
            'file.extensions' => 'El archivo debe ser Excel (.xlsx, .xls, .ods) o CSV/TXT.',
            'file.max' => 'El archivo no puede superar los 50 MB.',
            'file.uploaded' => 'No se pudo subir el archivo; revisa que pese menos de 50 MB.',
        ];
    }
}
