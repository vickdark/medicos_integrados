<?php

namespace App\Http\Requests;

use App\Enums\ConsultationSection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationAddendumRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('addAddendum', $this->route('consultation'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'section' => ['required', Rule::enum(ConsultationSection::class)],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
            'content' => ['required', 'string', 'min:5', 'max:5000'],
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
            'section.required' => 'Indica qué parte de la consulta aclaras.',
            'reason.required' => 'Indica el motivo de la aclaración.',
            'reason.min' => 'Describe el motivo con un poco más de detalle.',
            'content.required' => 'Escribe la aclaración.',
            'content.min' => 'Escribe la aclaración con un poco más de detalle.',
        ];
    }
}
