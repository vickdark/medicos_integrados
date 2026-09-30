<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

/**
 * How the patient is covered in the Colombian health system, with the user type
 * codes of the RIPS.
 */
enum AffiliationType: string
{
    use HasEnumOptions;

    case ContributoryContributor = '01';
    case ContributoryBeneficiary = '02';
    case ContributoryAdditional = '03';
    case Subsidized = '04';
    case NotAffiliated = '05';
    case SpecialContributor = '06';
    case SpecialBeneficiary = '07';
    case DeprivedOfLiberty = '08';
    case OccupationalRisk = '09';
    case Soat = '10';
    case VoluntaryPlan = '11';
    case Private = '12';

    public function label(): string
    {
        return match ($this) {
            self::ContributoryContributor => 'Contributivo cotizante',
            self::ContributoryBeneficiary => 'Contributivo beneficiario',
            self::ContributoryAdditional => 'Contributivo adicional',
            self::Subsidized => 'Subsidiado',
            self::NotAffiliated => 'No afiliado',
            self::SpecialContributor => 'Especial o de excepción cotizante',
            self::SpecialBeneficiary => 'Especial o de excepción beneficiario',
            self::DeprivedOfLiberty => 'Persona privada de la libertad (Fondo Nacional de Salud)',
            self::OccupationalRisk => 'Riesgos laborales (ARL)',
            self::Soat => 'SOAT',
            self::VoluntaryPlan => 'Plan voluntario de salud',
            self::Private => 'Particular',
        };
    }
}
