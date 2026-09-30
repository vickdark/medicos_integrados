<?php

namespace Database\Factories;

use App\Models\TourView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TourView>
 */
class TourViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tour' => TourView::PATIENT_ONBOARDING,
            'device_id' => (string) Str::uuid(),
        ];
    }
}
