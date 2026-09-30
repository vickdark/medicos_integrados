<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

/**
 * Identification document types, with the codes used by the RIPS in Colombia.
 */
enum DocumentType: string
{
    use HasEnumOptions;

    case CitizenshipCard = 'CC';
    case IdentityCard = 'TI';
    case CivilRegistry = 'RC';
    case ForeignerId = 'CE';
    case Passport = 'PA';
    case SpecialStayPermit = 'PE';
    case TemporaryProtectionPermit = 'PT';
    case ForeignDocument = 'DE';
    case LiveBirthCertificate = 'CN';
    case SafeConduct = 'SC';
    case AdultWithoutId = 'AS';
    case MinorWithoutId = 'MS';

    public function label(): string
    {
        return match ($this) {
            self::CitizenshipCard => 'Cédula de ciudadanía',
            self::IdentityCard => 'Tarjeta de identidad',
            self::CivilRegistry => 'Registro civil',
            self::ForeignerId => 'Cédula de extranjería',
            self::Passport => 'Pasaporte',
            self::SpecialStayPermit => 'Permiso especial de permanencia',
            self::TemporaryProtectionPermit => 'Permiso por protección temporal',
            self::ForeignDocument => 'Documento extranjero',
            self::LiveBirthCertificate => 'Certificado de nacido vivo',
            self::SafeConduct => 'Salvoconducto',
            self::AdultWithoutId => 'Adulto sin identificación',
            self::MinorWithoutId => 'Menor sin identificación',
        };
    }
}
