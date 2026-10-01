<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'specialty_id' => Specialty::factory(),
            'license_number' => 'MP-'.fake()->unique()->numerify('#######'),
            'phone' => fake()->phoneNumber(),
            'bio' => fake()->paragraph(),
            'consultation_fee' => fake()->randomElement([30, 40, 50, 60, 80]),
            'slot_minutes' => 30,
        ];
    }

    /**
     * A doctor who registered the image of their signature.
     */
    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'signature_path' => 'doctor-signatures/firma-de-prueba.png',
        ]);
    }
}
