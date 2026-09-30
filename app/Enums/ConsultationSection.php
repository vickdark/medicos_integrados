<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

/**
 * Part of a consultation a clarifying note refers to.
 */
enum ConsultationSection: string
{
    use HasEnumOptions;

    case Reason = 'reason';
    case Symptoms = 'symptoms';
    case VitalSigns = 'vital_signs';
    case Diagnosis = 'diagnosis';
    case Treatment = 'treatment';
    case Prescription = 'prescription';
    case Notes = 'notes';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Reason => 'Motivo de consulta',
            self::Symptoms => 'Síntomas y examen físico',
            self::VitalSigns => 'Signos vitales',
            self::Diagnosis => 'Diagnóstico',
            self::Treatment => 'Tratamiento / indicaciones',
            self::Prescription => 'Receta',
            self::Notes => 'Notas internas',
            self::Other => 'Otro',
        };
    }
}
