<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
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
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+1 month')->setTime(fake()->numberBetween(8, 17), 0),
            'status' => AppointmentStatus::Requested,
            'reason' => fake()->randomElement(['Control general', 'Dolor de cabeza', 'Chequeo anual', 'Fiebre', 'Seguimiento de tratamiento']),
            'notes' => null,
        ];
    }

    /**
     * Indicate that the appointment was confirmed by the clinic.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Confirmed,
        ]);
    }

    /**
     * Indicate that the appointment already took place.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Completed,
            'scheduled_at' => fake()->dateTimeBetween('-6 months', '-1 day')->setTime(fake()->numberBetween(8, 17), 0),
        ]);
    }

    /**
     * Indicate that the appointment was cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Cancelled,
        ]);
    }
}
