<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingContentRequest extends FormRequest
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
            'contact.phone' => ['nullable', 'string', 'max:30'],
            'contact.email' => ['nullable', 'email', 'max:150'],
            'contact.address' => ['nullable', 'string', 'max:200'],
            'about.title' => ['required', 'string', 'max:150'],
            'about.paragraph_one' => ['required', 'string', 'max:600'],
            'about.paragraph_two' => ['required', 'string', 'max:600'],
            'about.mission' => ['required', 'string', 'max:300'],
            'values' => ['required', 'array', 'size:3'],
            'values.*.title' => ['required', 'string', 'max:60'],
            'values.*.description' => ['required', 'string', 'max:250'],
            'services.title' => ['required', 'string', 'max:150'],
            'services.intro' => ['required', 'string', 'max:400'],
            'services.items' => ['required', 'array', 'size:6'],
            'services.items.*.title' => ['required', 'string', 'max:80'],
            'services.items.*.description' => ['required', 'string', 'max:250'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact.phone' => 'teléfono',
            'contact.email' => 'correo',
            'contact.address' => 'dirección',
            'about.title' => 'título',
            'about.paragraph_one' => 'primer párrafo',
            'about.paragraph_two' => 'segundo párrafo',
            'about.mission' => 'misión',
            'values.*.title' => 'título del valor',
            'values.*.description' => 'descripción del valor',
            'services.title' => 'título',
            'services.intro' => 'introducción',
            'services.items.*.title' => 'título del servicio',
            'services.items.*.description' => 'descripción del servicio',
        ];
    }
}
