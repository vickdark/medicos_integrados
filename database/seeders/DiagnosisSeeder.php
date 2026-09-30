<?php

namespace Database\Seeders;

use App\Models\Diagnosis;
use Illuminate\Database\Seeder;

/**
 * A small sample of frequent CIE-10 codes so the system works out of the box in
 * development. In production load the official table with `diagnoses:import`.
 */
class DiagnosisSeeder extends Seeder
{
    /**
     * Seed the sample codes.
     */
    public function run(): void
    {
        $diagnoses = [
            'A09X' => 'Diarrea y gastroenteritis de presunto origen infeccioso',
            'B353' => 'Tiña del pie',
            'B86X' => 'Escabiosis',
            'D509' => 'Anemia por deficiencia de hierro sin otra especificación',
            'E039' => 'Hipotiroidismo, no especificado',
            'E109' => 'Diabetes mellitus insulinodependiente sin mención de complicación',
            'E119' => 'Diabetes mellitus no insulinodependiente sin mención de complicación',
            'E669' => 'Obesidad, no especificada',
            'E785' => 'Hiperlipidemia no especificada',
            'F329' => 'Episodio depresivo, no especificado',
            'F411' => 'Trastorno de ansiedad generalizada',
            'G439' => 'Migraña, no especificada',
            'H109' => 'Conjuntivitis, no especificada',
            'H669' => 'Otitis media, no especificada',
            'I10X' => 'Hipertensión esencial (primaria)',
            'J00X' => 'Rinofaringitis aguda (resfriado común)',
            'J029' => 'Faringitis aguda, no especificada',
            'J039' => 'Amigdalitis aguda, no especificada',
            'J069' => 'Infección aguda de las vías respiratorias superiores, no especificada',
            'J189' => 'Neumonía, no especificada',
            'J209' => 'Bronquitis aguda, no especificada',
            'J304' => 'Rinitis alérgica, no especificada',
            'J459' => 'Asma, no especificada',
            'K219' => 'Enfermedad del reflujo gastroesofágico sin esofagitis',
            'K297' => 'Gastritis, no especificada',
            'K590' => 'Constipación',
            'L309' => 'Dermatitis, no especificada',
            'L700' => 'Acné vulgar',
            'M255' => 'Dolor en articulación',
            'M542' => 'Cervicalgia',
            'M545' => 'Lumbago no especificado',
            'N390' => 'Infección de vías urinarias, sitio no especificado',
            'R05X' => 'Tos',
            'R104' => 'Otros dolores abdominales y los no especificados',
            'R509' => 'Fiebre, no especificada',
            'R51X' => 'Cefalea',
            'T784' => 'Alergia no especificada',
            'Z000' => 'Examen médico general',
        ];

        foreach ($diagnoses as $code => $description) {
            Diagnosis::query()->updateOrCreate(['code' => $code], ['description' => $description]);
        }
    }
}
