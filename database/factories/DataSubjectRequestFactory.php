<?php

namespace Database\Factories;

use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use App\Models\DataSubjectRequest;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataSubjectRequest>
 */
class DataSubjectRequestFactory extends Factory
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
            'user_id' => null,
            'type' => DataRequestType::Access,
            'details' => fake()->sentence(8),
            'status' => DataRequestStatus::Pending,
            'due_at' => now()->addWeekdays(10)->toDateString(),
        ];
    }

    /**
     * Indicate that the legal deadline already passed.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->subDays(3)->toDateString(),
        ]);
    }
}
