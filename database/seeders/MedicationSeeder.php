<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    /**
     * Seed a starting catalog of common medications.
     */
    public function run(): void
    {
        $medications = [
            ['Paracetamol', 'Tabletas', '500 mg'],
            ['Ibuprofeno', 'Tabletas', '400 mg'],
            ['Amoxicilina', 'Cápsulas', '500 mg'],
            ['Azitromicina', 'Tabletas', '500 mg'],
            ['Omeprazol', 'Cápsulas', '20 mg'],
            ['Losartán', 'Tabletas', '50 mg'],
            ['Metformina', 'Tabletas', '850 mg'],
            ['Loratadina', 'Tabletas', '10 mg'],
            ['Salbutamol', 'Inhalador', '100 mcg'],
            ['Diclofenaco', 'Ampolla', '75 mg'],
        ];

        foreach ($medications as [$name, $presentation, $concentration]) {
            Medication::query()->firstOrCreate(compact('name', 'presentation', 'concentration'));
        }
    }
}
