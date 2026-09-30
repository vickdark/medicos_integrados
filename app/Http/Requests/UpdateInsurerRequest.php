<?php

namespace App\Http\Requests;

use App\Models\Insurer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInsurerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('insurer'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique(Insurer::class)->ignore($this->route('insurer'))],
            'code' => ['nullable', 'string', 'max:20', Rule::unique(Insurer::class)->ignore($this->route('insurer'))],
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
            'name.unique' => 'Ya existe una aseguradora con ese nombre.',
            'code.unique' => 'Ya existe una aseguradora con ese código.',
        ];
    }
}
