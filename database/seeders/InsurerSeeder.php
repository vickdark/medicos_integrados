<?php

namespace Database\Seeders;

use App\Models\Insurer;
use Illuminate\Database\Seeder;

/**
 * Frequent EPS so the form has options from the start. The administrator keeps
 * the list up to date and fills in each entity's code.
 */
class InsurerSeeder extends Seeder
{
    /**
     * Seed the starting list of insurers.
     */
    public function run(): void
    {
        $insurers = [
            'Nueva EPS',
            'EPS Sura',
            'EPS Sanitas',
            'Salud Total EPS',
            'Compensar EPS',
            'Famisanar EPS',
            'Coosalud EPS',
            'Mutual Ser EPS',
            'Emssanar EPS',
            'Asmet Salud EPS',
            'Aliansalud EPS',
            'SOS - Servicio Occidental de Salud',
            'Savia Salud EPS',
            'Cajacopi EPS',
            'Capresoca EPS',
        ];

        foreach ($insurers as $name) {
            Insurer::query()->firstOrCreate(['name' => $name]);
        }
    }
}
