<?php

namespace Database\Factories;

use App\Models\Medication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Paracetamol', 'Amoxicilina', 'Omeprazol', 'Losartán', 'Loratadina']).' '.fake()->unique()->numberBetween(1, 9999),
            'presentation' => fake()->randomElement(['Tabletas', 'Cápsulas', 'Jarabe', 'Ampolla', 'Crema']),
            'concentration' => fake()->randomElement(['500 mg', '250 mg', '20 mg', '50 mg', '10 mg']),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
