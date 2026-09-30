<?php

namespace App\Concerns;

use App\Models\Medication;
use Illuminate\Validation\Rule;

trait MedicationValidationRules
{
    /**
     * Get the validation rules used to validate medications. The same name may
     * repeat as long as the presentation or concentration differs.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function medicationRules(?Medication $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Medication::class)
                ->ignore($ignore)
                ->where(fn ($query) => $query
                    ->where('presentation', $this->input('presentation'))
                    ->where('concentration', $this->input('concentration')))],
            'presentation' => ['nullable', 'string', 'max:100'],
            'concentration' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
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
            'name.required' => 'Indica el nombre del medicamento.',
            'name.unique' => 'Ya existe este medicamento con la misma presentación y concentración.',
        ];
    }
}
