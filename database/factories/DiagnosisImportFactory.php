<?php

namespace Database\Factories;

use App\Models\DiagnosisImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiagnosisImport>
 */
class DiagnosisImportFactory extends Factory
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
            'file_name' => 'CIE10.xlsx',
            'dry_run' => false,
            'deactivate_missing' => false,
            'read' => 100,
            'valid' => 100,
            'rejected' => 0,
            'inserted' => 100,
            'updated' => 0,
            'unchanged' => 0,
            'deactivated' => 0,
            'report_path' => null,
        ];
    }
}
