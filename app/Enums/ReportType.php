<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum ReportType: string
{
    use HasEnumOptions;

    case Appointments = 'appointments';
    case Income = 'income';
    case Consultations = 'consultations';

    public function label(): string
    {
        return match ($this) {
            self::Appointments => 'Citas',
            self::Income => 'Ingresos',
            self::Consultations => 'Consultas',
        };
    }
}
