<?php

namespace Database\Factories;

use App\Enums\ClinicalDocumentType;
use App\Models\ClinicalDocument;
use App\Models\Consultation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalDocument>
 */
class ClinicalDocumentFactory extends Factory
{
    /**
     * Define the model's default state: an exam order.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consultation_id' => Consultation::factory(),
            'type' => ClinicalDocumentType::ExamOrder,
            'data' => [
                'exams' => ['Hemograma completo', 'Glicemia en ayunas'],
                'priority' => 'routine',
                'indications' => 'Ayuno de 8 horas.',
            ],
            'issued_by' => null,
        ];
    }

    public function sickLeave(int $days = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ClinicalDocumentType::SickLeave,
            'data' => [
                'start_date' => now()->toDateString(),
                'days' => $days,
                'origin' => 'general_illness',
                'is_extension' => false,
                'notes' => null,
            ],
        ]);
    }
}
