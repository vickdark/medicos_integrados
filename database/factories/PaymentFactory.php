<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'appointment_id' => null,
            'amount' => fake()->randomElement([30, 40, 50, 60, 80, 120]),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => PaymentStatus::Paid,
            'concept' => fake()->randomElement(['Consulta médica', 'Control', 'Procedimiento', 'Examen de laboratorio']),
            'reference' => fake()->optional()->numerify('REF-######'),
            'paid_at' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'notes' => null,
            'recorded_by' => null,
        ];
    }

    /**
     * Indicate that the payment is still pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);
    }
}
