<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\Gender;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'document_type' => DocumentType::CitizenshipCard,
            'document_number' => fake()->unique()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'),
            'gender' => fake()->randomElement(Gender::cases()),
            'address' => fake()->address(),
            'blood_type' => fake()->randomElement(['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-']),
            'allergies' => fake()->optional()->randomElement(['Penicilina', 'Polen', 'Mariscos', 'Ibuprofeno']),
            'chronic_conditions' => fake()->optional()->randomElement(['Hipertensión', 'Diabetes tipo 2', 'Asma']),
            'medical_background' => fake()->optional()->sentence(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
        ];
    }

    /**
     * Indicate that the patient has a user account to access the portal.
     */
    public function withAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->state([
                'name' => "{$attributes['first_name']} {$attributes['last_name']}",
                'email' => $attributes['email'],
            ]),
        ]);
    }
}
