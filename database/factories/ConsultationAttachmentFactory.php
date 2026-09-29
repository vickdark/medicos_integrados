<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\ConsultationAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsultationAttachment>
 */
class ConsultationAttachmentFactory extends Factory
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
            'uploaded_by' => null,
            'original_name' => fake()->word().'.pdf',
            'path' => 'attachments/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 2_000_000),
            'description' => fake()->optional()->sentence(3),
        ];
    }
}
