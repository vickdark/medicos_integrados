<?php

namespace Database\Factories;

use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specialty>
 */
class SpecialtyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Medicina General', 'Pediatría', 'Cardiología', 'Dermatología', 'Ginecología',
                'Traumatología', 'Neurología', 'Oftalmología', 'Psiquiatría', 'Endocrinología',
            ]).' '.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
        ];
    }
}
