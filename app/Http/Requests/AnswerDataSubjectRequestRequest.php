<?php

namespace App\Http\Requests;

use App\Enums\DataRequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnswerDataSubjectRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('answer', $this->route('dataRequest'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([DataRequestStatus::Resolved->value, DataRequestStatus::Rejected->value])],
            'response' => ['required', 'string', 'min:10', 'max:2000'],
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
            'status.required' => 'Indica si la solicitud se atiende o se rechaza.',
            'status.in' => 'Indica si la solicitud se atiende o se rechaza.',
            'response.required' => 'Escribe la respuesta para el paciente.',
            'response.min' => 'La respuesta debe tener al menos 10 caracteres.',
            'response.max' => 'La respuesta no debe superar los 2000 caracteres.',
        ];
    }
}
