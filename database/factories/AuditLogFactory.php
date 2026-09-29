<?php

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $patient = Patient::factory();

        return [
            'user_id' => User::factory()->admin(),
            'patient_id' => $patient,
            'action' => AuditAction::Viewed,
            'auditable_type' => (new Patient)->getMorphClass(),
            'auditable_id' => $patient,
            'description' => 'Consultó la historia clínica',
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
