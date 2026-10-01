<?php

namespace Database\Factories;

use App\Models\ClinicalHistoryExport;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalHistoryExport>
 */
class ClinicalHistoryExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'issued_by' => null,
            'issued_by_name' => fake()->name(),
            'issued_by_role' => 'Médico',
            'period_from' => null,
            'period_to' => null,
            'period_label' => 'Historial completo',
            'consultation_ids' => [],
            'includes_notes' => true,
        ];
    }
}
