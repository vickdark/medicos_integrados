<?php

namespace Database\Factories;

use App\Models\AppSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppSetting>
 */
class AppSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => fake()->word(),
        ];
    }

    /**
     * Indicate that the setting stores the brand color.
     */
    public function brandColor(string $color = '#2563EB'): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => AppSetting::BRAND_COLOR,
            'value' => $color,
        ]);
    }
}
