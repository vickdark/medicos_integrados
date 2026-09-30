<?php

namespace Database\Factories;

use App\Enums\TurnStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Turn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turn>
 */
class TurnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'turn_date' => today(),
            'number' => fake()->unique()->numberBetween(1, 999),
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'appointment_id' => null,
            'status' => TurnStatus::Waiting,
        ];
    }

    public function called(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TurnStatus::Called,
            'called_at' => now(),
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TurnStatus::Done,
            'called_at' => now()->subMinutes(20),
            'finished_at' => now(),
        ]);
    }
}
