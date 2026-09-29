<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consultation_id' => Consultation::factory(),
            'medication' => fake()->randomElement(['Paracetamol', 'Amoxicilina', 'Omeprazol', 'Losartán', 'Loratadina']),
            'dosage' => fake()->randomElement(['500 mg', '250 mg', '20 mg', '50 mg', '10 mg']),
            'frequency' => fake()->randomElement(['Cada 8 horas', 'Cada 12 horas', 'Una vez al día']),
            'duration' => fake()->randomElement(['5 días', '7 días', '14 días', '1 mes']),
            'instructions' => fake()->optional()->sentence(),
        ];
    }
}
