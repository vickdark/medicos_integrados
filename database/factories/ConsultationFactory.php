<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
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
            'doctor_id' => Doctor::factory(),
            'appointment_id' => null,
            'consulted_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'reason' => fake()->randomElement(['Dolor abdominal', 'Tos persistente', 'Control de presión', 'Chequeo anual']),
            'symptoms' => fake()->sentence(),
            'diagnosis' => fake()->randomElement(['Gastritis', 'Bronquitis aguda', 'Hipertensión controlada', 'Paciente sano']),
            'treatment' => fake()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'weight_kg' => fake()->randomFloat(2, 45, 110),
            'height_cm' => fake()->randomFloat(2, 150, 195),
            'blood_pressure' => fake()->numberBetween(100, 140).'/'.fake()->numberBetween(60, 90),
            'temperature_c' => fake()->randomFloat(1, 36, 38.5),
            'heart_rate' => fake()->numberBetween(60, 100),
        ];
    }
}
