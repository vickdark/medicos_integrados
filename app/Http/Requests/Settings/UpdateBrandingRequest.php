<?php

namespace App\Http\Requests\Settings;

use App\Actions\Branding\BrandPalette;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-branding');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'color' => [
                'required',
                'string',
                'regex:/^#[0-9a-fA-F]{6}$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && BrandPalette::isValid($value) && ! BrandPalette::hasEnoughContrast($value)) {
                        $fail('Elige un color más oscuro: con este el texto blanco sobre botones y etiquetas no se lee bien.');
                    }
                },
            ],
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
            'color.required' => 'Elige un color.',
            'color.regex' => 'El color debe tener el formato #RRGGBB.',
        ];
    }
}
