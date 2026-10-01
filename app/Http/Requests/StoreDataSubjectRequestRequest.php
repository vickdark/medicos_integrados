<?php

namespace App\Http\Requests;

use App\Enums\DataRequestType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataSubjectRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->patient !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DataRequestType::class)],
            'details' => ['required', 'string', 'min:10', 'max:2000'],
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
            'type.required' => 'Selecciona qué quieres solicitar.',
            'details.required' => 'Cuéntanos tu solicitud.',
            'details.min' => 'Describe tu solicitud con al menos 10 caracteres.',
            'details.max' => 'La solicitud no debe superar los 2000 caracteres.',
        ];
    }
}
