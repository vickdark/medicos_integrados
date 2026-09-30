<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum ReportGroup: string
{
    use HasEnumOptions;

    case Doctor = 'doctor';
    case Specialty = 'specialty';

    public function label(): string
    {
        return match ($this) {
            self::Doctor => 'Por médico',
            self::Specialty => 'Por especialidad',
        };
    }
}
