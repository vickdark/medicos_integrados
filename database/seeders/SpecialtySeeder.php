<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    /**
     * Seed the medical specialties offered by the clinic.
     */
    public function run(): void
    {
        $specialties = [
            'Medicina General' => 'Atención primaria, chequeos y diagnóstico inicial.',
            'Pediatría' => 'Cuidado de la salud de bebés, niños y adolescentes.',
            'Cardiología' => 'Diagnóstico y tratamiento de enfermedades del corazón.',
            'Dermatología' => 'Salud de la piel, cabello y uñas.',
            'Ginecología' => 'Salud del sistema reproductor femenino.',
            'Traumatología' => 'Lesiones del sistema músculo-esquelético.',
            'Neurología' => 'Trastornos del sistema nervioso.',
            'Endocrinología' => 'Trastornos hormonales y metabólicos.',
        ];

        foreach ($specialties as $name => $description) {
            Specialty::query()->updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
