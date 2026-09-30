<?php

namespace Database\Factories;

use App\Enums\ConsultationSection;
use App\Models\Consultation;
use App\Models\ConsultationAddendum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationAddendum>
 */
class ConsultationAddendumFactory extends Factory
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
            'user_id' => null,
            'author_name' => fake()->name(),
            'section' => ConsultationSection::Diagnosis,
            'reason' => 'Error de transcripción',
            'content' => fake()->sentence(),
        ];
    }
}
